<?php

/**
 * Subscription service
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Service
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Service;

use DateTime;
use Nails\Common\Helper;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Invoice\Factory\ChargeRequest;
use Nails\Invoice\Factory\ChargeResponse;
use Nails\Invoice\Factory\Invoice;
use Nails\Invoice\Resource\Customer;
use Nails\Invoice\Resource\Source;
use Nails\Subscription\Constants;
use Nails\Subscription\Exception\AlreadySubscribedException;
use Nails\Subscription\Resource\Instance;
use Nails\Subscription\Resource\Package;

/**
 * Class Subscription
 *
 * @package Nails\Subscription\Service
 */
class Subscription
{
    /** @var \Nails\Subscription\Model\Instance */
    protected $oInstanceModel;

    /** @var \Nails\Invoice\Model\Invoice */
    protected $oInvoiceModel;

    // --------------------------------------------------------------------------

    /**
     * Subscription constructor.
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    public function __construct()
    {
        $this->oInstanceModel = Factory::model('Instance', Constants::MODULE_SLUG);
        $this->oInvoiceModel  = Factory::model('Invoice', \Nails\Invoice\Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new subscription
     *
     * @param Customer $oCustomer The customer to apply the subscription to
     * @param Package  $oPackage  The package to apply
     * @param Source   $oSource   The payment source to use
     * @param Currency $oCurrency The currency to charge in
     * @param DateTime $oStart    When to start the subscription
     *
     * @return Instance
     */
    public function create(
        Customer $oCustomer,
        Package $oPackage,
        Source $oSource,
        Currency $oCurrency,
        DateTime $oStart = null
    ): Instance {

        /** @var \DateTime $oStart */
        $oStart = $oStart ?? \Nails\Factory::factory('DateTime');

        // --------------------------------------------------------------------------

        /**
         * Check if the customer is already subscribed, if they are then we cannot continue.
         */

        if ($this->isSubscribed($oCustomer, $oStart)) {
            throw new AlreadySubscribedException(
                sprintf(
                    'Customer #%s is already subscribed',
                    $oCustomer->id
                )
            );
        }

        // --------------------------------------------------------------------------

        /**
         * Determine whether this customer has used their free trial, if supported.
         */
        //  @todo (Pablo - 2020-02-18) - Calculate this
        $bCanUseFreeTrial = true;

        // --------------------------------------------------------------------------

        /**
         * Calculate the various dates for the package. If it doesn't support a certain
         * feature (e.g. free trial) then those dates are 0 seconds long.
         */
        if ($bCanUseFreeTrial) {
            [$oFreeTrialStart, $oFreeTrialEnd] = $this->calculateFreeTrialDates($oPackage, $oStart);
        } else {
            $oFreeTrialStart = clone $oStart;
            $oFreeTrialEnd   = clone $oStart;
        }
        [$oSubscriptionStart, $oSubscriptionEnd] = $this->calculateSubscriptionDates($oPackage, $oFreeTrialEnd);
        [$oCoolingOffStart, $oCoolingOffEnd] = $this->calculateCoolingOffDates($oPackage, $oStart);

        // --------------------------------------------------------------------------

        /**
         * Validate the input, make sure we can use what we need
         */

        //  @todo (Pablo - 2020-02-18) - Validate package is active
        //  @todo (Pablo - 2020-02-18) - Validate currency is supported by package
        //  @todo (Pablo - 2020-02-18) - Validate source has not expired
        //  @todo (Pablo - 2020-02-18) - Validate source will be valid at the start of the subscription
        //  @todo (Pablo - 2020-02-18) - Validate source belongs to the customer

        // --------------------------------------------------------------------------

        /**
         * Create the instance of the subscription. We want to record that the attempt
         * was made, regardless of the outcome of any invoice payment failures
         */
        /** @var Instance $oInstance */
        $oInstance = $this->oInstanceModel->create(
            [
                'customer_id'             => $oCustomer->id,
                'package_id'              => $oPackage->id,
                'source_id'               => $oSource->id,
                'currency'                => $oCurrency->code,
                'date_free_trial_start'   => $oFreeTrialStart->format('Y-m-d H:i:59'),
                'date_free_trial_end'     => $oFreeTrialEnd->format('Y-m-d H:i:59'),
                'date_subscription_start' => $oSubscriptionStart->format('Y-m-d H:i:59'),
                'date_subscription_end'   => $oSubscriptionEnd->format('Y-m-d H:i:59'),
                'date_cooling_off_start'  => $oCoolingOffStart->format('Y-m-d H:i:59'),
                'date_cooling_off_end'    => $oCoolingOffEnd->format('Y-m-d H:i:59'),
                'is_automatic_renew'      => $oPackage->supports_automatic_renew,
            ],
            true
        );

        // --------------------------------------------------------------------------

        /**
         * Raise the invoice associated with this instance. Next we'll determine if
         * it needs charged, or if we're leaving that for the cron job (i.e at the
         * end of the free trial)
         */
        /** @var Invoice $oInvoiceBuilder */
        $oInvoiceBuilder = Factory::factory('Invoice', \Nails\Invoice\Constants::MODULE_SLUG);

        //  @todo (Pablo - 2020-02-18) - Determine which value to use (initial or normal)

        /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
        $oInvoice = $oInvoiceBuilder
            ->setCustomerId($oCustomer)
            ->setCurrency($oCurrency)
            ->setDated($oSubscriptionStart)
            ->addItem(
                $this->getLineItem(
                    $oPackage,
                    $this->getPackageCost(
                        $oPackage,
                        $oCurrency
                    )
                )
            )
            ->save();

        $this->oInstanceModel->update(
            $oInstance->id,
            [
                'invoice_id' => $oInvoice->id,
            ]
        );
        $oInstance->invoice = $oInvoice;

        // --------------------------------------------------------------------------

        /**
         * If the invoice is due now, attempt to pay it. If it is zero value then mark
         * it as paid, if it is due to be paid in the future, then leave it as is -
         * the cron will catch it
         */
        //  @todo (Pablo - 2020-02-18) - Determine if the invoice is due to be paid now
        $bIsDueNow = false;

        if ($bIsDueNow && $oInvoice->totals->raw->grand) {

            try {
                /** @var ChargeRequest $oChargeRequest */
                $oChargeRequest = Factory::factory('ChargeRequest', \Nails\Invoice\Constants::MODULE_SLUG);
                $oChargeRequest->setSource($oSource);

                /** @var ChargeResponse $oChargeResponse */
                $oChargeResponse = $oInvoice
                    ->charge($oChargeRequest);

                //  @todo (Pablo - 2020-02-18) - Facilitate failures

            } catch (\Exception $e) {
                //  @todo (Pablo - 2020-02-18) - handle the failure
            }

        } elseif (!$oInvoice->totals->raw->grand) {
            $this->oInvoiceModel->setPaid($oInvoice->id);
        }

        // --------------------------------------------------------------------------

        return $oInstance;
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the Free Trial start and end dates
     *
     * @param Package  $oPackage The package being applied
     * @param DateTime $oStart   The desired start time
     *
     * @return DateTime[]
     */
    protected function calculateFreeTrialDates(Package $oPackage, DateTime $oStart): array
    {
        $oEnd = clone $oStart;

        if ($oPackage->supports_free_trial) {
            $oEnd = Helper\Date::addPeriod(
                $oEnd,
                Helper\Date::PERIOD_DAY,
                $oPackage->free_trial_duration
            );
        }

        return [
            $oStart,
            $oEnd,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the Subscription start and end dates
     *
     * @param Package  $oPackage The package being applied
     * @param DateTime $oStart   The desired start time
     *
     * @return DateTime[]
     */
    protected function calculateSubscriptionDates(Package $oPackage, DateTime $oStart): array
    {
        $oEnd = clone $oStart;
        $oEnd = Helper\Date::addPeriod(
            $oEnd,
            $oPackage->billing_period,
            $oPackage->billing_duration
        );

        return [
            $oStart,
            $oEnd,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the Cooling Off start and end dates
     *
     * @param Package  $oPackage The package being applied
     * @param DateTime $oStart   The desired start time
     *
     * @return DateTime[]
     */
    protected function calculateCoolingOffDates(Package $oPackage, DateTime $oStart): array
    {
        $oEnd = clone $oStart;

        if ($oPackage->supports_cooling_off) {
            $oEnd = Helper\Date::addPeriod(
                $oEnd,
                Helper\Date::PERIOD_DAY,
                $oPackage->cooling_off_duration
            );
        }

        return [
            $oStart,
            $oEnd,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the subscription line item
     *
     * @param Package  $oPackage  The package being applied
     * @param Currency $oCurrency The currency being used for this transaction
     *
     * @return Invoice\Item
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function getLineItem(
        Package $oPackage,
        Package\Cost $oCost,
        bool $bIsNormalPrice = true
    ): Invoice\Item {

        /** @var Invoice\Item $oItem */
        $oItem = Factory::factory('InvoiceItem', \Nails\Invoice\Constants::MODULE_SLUG);
        $oItem
            ->setLabel(
                sprintf(
                    'Susbcription: %s',
                    $oPackage->label
                )
            )
            ->setUnit($oPackage->billing_period)
            ->setQuantity($oPackage->billing_duration)
            ->setUnitCost(
                $bIsNormalPrice
                    ? $oCost->value_normal
                    : $oCost->value_initial
            );

        return $oItem;
    }

    // --------------------------------------------------------------------------

    /**
     * Determine the cost of the package
     *
     * @param Package  $oPackage  The package to analyse
     * @param Currency $oCurrency The currency being used
     *
     * @return Package\Cost
     * @throws \Nails\Common\Exception\FactoryException
     * @throws \Nails\Common\Exception\ModelException
     */
    protected function getPackageCost(Package $oPackage, Currency $oCurrency): Package\Cost
    {
        $aCosts = array_filter(
            $oPackage->costs()->data,
            function (Package\Cost $oItem) use ($oCurrency) {
                return $oItem->currency->code === $oCurrency->code;
            }
        );

        return reset($aCosts);
    }

    // --------------------------------------------------------------------------

    /**
     * Renews an existing subscription instance
     *
     * @param Instance $oInstance The subscription instance to renew
     *
     * @return Instance
     */
    public function renew(Instance $oInstance): Instance
    {
        //  @todo (Pablo - 2020-02-18) - Renew an instance
    }

    // --------------------------------------------------------------------------

    /**
     * Cancel an existing subscription instance
     *
     * @param Instance $oInstance The subscription instance to cancel
     *
     * @return Instance
     */
    public function cancel(Instance $oInstance): Instance
    {
        //  @todo (Pablo - 2020-02-18) - Cancel a subscription
    }

    // --------------------------------------------------------------------------

    /**
     * Modify an existing subscription instance
     *
     * @param Instance $oInstance The subscription instance to modify
     *
     * @return Instance
     */
    public function modify(Instance $oInstance): Instance
    {
        //  @todo (Pablo - 2020-02-18) - Modify a subscription
    }

    // --------------------------------------------------------------------------

    /**
     * Changes a subscription from one package onto another
     *
     * @param Instance $oInstance The subscription instance to modify
     * @param Package  $oPackage  The new package to apply
     *
     * @return Instance
     */
    public function swap(Instance $oInstance, Package $oPackage): Instance
    {
        //  @todo (Pablo - 2020-02-18) - Swap a subscription
    }

    // --------------------------------------------------------------------------

    /**
     * Determine whether a user is subscribed
     *
     * @param Customer      $oCustomer The customer to check
     * @param DateTime|null $oWhen     The time period to check
     *
     * @return bool
     */
    public function isSubscribed(Customer $oCustomer, DateTime $oWhen = null): bool
    {
        $oInstance = $this->get($oCustomer, $oWhen);
        if (empty($oInstance)) {
            return false;
        }

        $oInvoice = $oInstance->invoice();

        /**
         * A subscription instance is considered subscribed if:
         * - The invoice is paid
         * - The invoice is unpaid, but the free trial is current
         */

        //  @todo (Pablo - 2020-02-18) - Determine whether the instance is valid
        $bIsValid = true;

        return $bIsValid;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a customer's subscription instance, if any, for a particular
     *
     * @param Customer      $oCustomer The customer to check
     * @param DateTime|null $oWhen     The time period to check
     *
     * @return Instance|null
     */
    public function get(Customer $oCustomer, DateTime $oWhen = null): ?Instance
    {
        /** @var DateTime $oWhen */
        $oWhen = $oWhen ?? Factory::factory('DateTime');

        $aInstance = $this->oInstanceModel->getAll([
            'where'    => [
                ['customer_id', $oCustomer->id],
            ],
            'where_or' => [
                $this->generateSqlBetween($oWhen, 'free_trial'),
                $this->generateSqlBetween($oWhen, 'subscription'),
            ],
        ]);

        return reset($aInstance) ?: null;
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a BETWEEN statement for a timestamp and a column pair
     *
     * @param DateTime $oWhen   The timestamp to test
     * @param string   $sColumn The column pair to test
     *
     * @return string
     */
    protected function generateSqlBetween(DateTime $oWhen, string $sColumn): string
    {
        return sprintf(
            '"%1$s" BETWEEN date_%2$s_start AND date_%2$s_end',
            $oWhen->format('Y-m-d H:i:s'),
            $sColumn
        );
    }
}
