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
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Invoice\Exception\InvoiceException;
use Nails\Invoice\Exception\RequestException;
use Nails\Invoice\Factory\ChargeRequest;
use Nails\Invoice\Factory\ChargeResponse;
use Nails\Invoice\Factory\Invoice;
use Nails\Invoice\Resource\Customer;
use Nails\Invoice\Resource\Source;
use Nails\Subscription\Constants;
use Nails\Subscription\Exception\AlreadySubscribedException;
use Nails\Subscription\Exception\PaymentFailedException;
use Nails\Subscription\Exception\RedirectRequiredException;
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
     * @param Customer      $oCustomer   The customer to apply the subscription to
     * @param Package       $oPackage    The package to apply
     * @param Source        $oSource     The payment source to use
     * @param Currency      $oCurrency   The currency to charge in
     * @param string        $sSuccessUrl For redirect transactions, where to send the user on success
     * @param string        $sErrorUrl   For redirect transactions, where to send the user on error
     * @param DateTime|null $oStart      When to start the subscription
     *
     * @return Instance
     * @throws AlreadySubscribedException
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     * @throws PaymentFailedException
     * @throws RedirectRequiredException
     */
    public function create(
        Customer $oCustomer,
        Package $oPackage,
        Source $oSource,
        Currency $oCurrency,
        string $sSuccessUrl = '',
        string $sErrorUrl = '',
        DateTime $oStart = null
    ): Instance {

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        /** @var \DateTime $oStart */
        $oStart = $oStart ?? Factory::factory('DateTime');

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
        //  @todo (Pablo - 2020-02-18) - Calculate this; has the user had this package before (and paid for it)
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
                'date_free_trial_start'   => $oFreeTrialStart->format('Y-m-d H:i:s'),
                'date_free_trial_end'     => $oFreeTrialEnd->format('Y-m-d H:i:59'),
                'date_subscription_start' => $oSubscriptionStart->format('Y-m-d H:i:s'),
                'date_subscription_end'   => $oSubscriptionEnd->format('Y-m-d H:i:59'),
                'date_cooling_off_start'  => $oCoolingOffStart->format('Y-m-d H:i:s'),
                'date_cooling_off_end'    => $oCoolingOffEnd->format('Y-m-d H:i:59'),
                'is_automatic_renew'      => $oPackage->supports_automatic_renew,
            ],
            true
        );

        // --------------------------------------------------------------------------

        try {

            $this->chargeInvoice(
                $oInstance,
                $this->raiseInvoice(
                    $oInstance,
                    true //  @todo (Pablo - 2020-02-20) - Determine what price to charge
                ),
                $oSource,
                $sSuccessUrl,
                $sErrorUrl
            );

        } catch (RedirectRequiredException $e) {
            throw $e;

        } catch (\Throwable $e) {
            $this->terminate(
                $oInstance,
                sprintf(
                    'An exception ocurred during processing: %s with code $s; %s',
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            throw $e;
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
     * Raises an invoice for a subscription instance
     *
     * @param Instance $oInstance      The subscription instance to raise the invoice against
     * @param bool     $bIsNormalPrice Whether to charge the normal price or not
     *
     * @return \Nails\Invoice\Resource\Invoice
     * @throws FactoryException
     * @throws InvoiceException
     * @throws ModelException
     */
    protected function raiseInvoice(
        Instance $oInstance,
        bool $bIsNormalPrice = true
    ): \Nails\Invoice\Resource\Invoice {

        /** @var Invoice $oInvoiceBuilder */
        $oInvoiceBuilder = Factory::factory('Invoice', \Nails\Invoice\Constants::MODULE_SLUG);

        /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
        $oInvoice = $oInvoiceBuilder
            ->setCustomerId($oInstance->customer())
            ->setCurrency($oInstance->currency)
            ->setDated($oInstance->date_subscription_start)
            ->addItem(
                $this->getLineItem(
                    $oInstance->package(),
                    $this->getPackageCost(
                        $oInstance->package(),
                        $oInstance->currency
                    ),
                    $bIsNormalPrice
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

        return $oInvoice;
    }

    // --------------------------------------------------------------------------

    /**
     * Charge an invoice, if the time is right
     *
     * @param Instance                        $oInstance   The instance being charged
     * @param \Nails\Invoice\Resource\Invoice $oInvoice    The invoice to charge
     * @param Source                          $oSource     The Payment source to use
     * @param string                          $sSuccessUrl Where to go on successfull payment
     * @param string                          $sErrorUrl   Where to go on dailed payment
     *
     * @throws FactoryException
     * @throws ModelException
     * @throws PaymentFailedException
     * @throws RedirectRequiredException
     * @throws RequestException
     */
    protected function chargeInvoice(
        Instance $oInstance,
        \Nails\Invoice\Resource\Invoice $oInvoice,
        Source $oSource,
        string $sSuccessUrl,
        string $sErrorUrl
    ): void {

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        if ($oNow->format('Y-m-d') === $oInvoice->due->format('Y-m-d') && $oInvoice->totals->raw->grand) {

            /** @var ChargeRequest $oChargeRequest */
            $oChargeRequest = Factory::factory('ChargeRequest', \Nails\Invoice\Constants::MODULE_SLUG);
            $oChargeRequest
                ->setAutoRedirect(false)
                ->setSuccessUrl($sSuccessUrl)
                ->setErrorUrl($sErrorUrl)
                ->setSource($oSource);

            /** @var ChargeResponse $oChargeResponse */
            $oChargeResponse = $oInvoice
                ->charge($oChargeRequest);

            if ($oChargeResponse->isRedirect()) {

                throw (new RedirectRequiredException())
                    ->setRedirectUrl($oChargeResponse->getRedirectUrl())
                    ->setInstance($oInstance);

            } elseif ($oChargeResponse->isFailed()) {

                throw (new PaymentFailedException($oChargeResponse->getErrorMessage()))
                    ->setUserMessage($oChargeResponse->getErrorMessageUser())
                    ->setErrorCode($oChargeResponse->getErrorCode());
            }

        } elseif (!$oInvoice->totals->raw->grand) {
            $this->oInvoiceModel->setPaid($oInvoice->id);
        }
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
     * Prevent a subscription from renewing
     *
     * @param Instance $oInstance The subscription instance to cancel
     *
     * @return Instance
     */
    public function cancel(Instance $oInstance, string $sReason = null): Instance
    {
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        return $this->oInstanceModel->update(
            $oInstance->id,
            [
                'is_automatic_renew' => false,
                'cancel_reason'      => $sReason,
                'date_cancel'        => $oNow->format('Y-m-d H:i:s'),
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a subscription
     *
     * @param Instance $oInstance
     *
     * @return Instance
     */
    public function restore(Instance $oInstance): Instance
    {
        //  @todo (Pablo - 2020-02-20) - Restore a subscription
        //  @todo (Pablo - 2020-02-20) - Test if possible to restore
    }

    // --------------------------------------------------------------------------

    /**
     * Immediately terminate a subscription
     *
     * @param Instance    $oIbonstance The subscription instance to terminate
     * @param string|null $sReason     The reason for termination
     *
     * @return bool
     */
    public function terminate(Instance $oInstance, string $sReason = null): bool
    {
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        return $this->oInstanceModel->update(
            $oInstance->id,
            [
                'is_automatic_renew'    => false,
                'cancel_reason'         => $sReason,
                'date_cancel'           => $oNow->format('Y-m-d H:i:s'),
                'date_free_trial_end'   => $oNow->format('Y-m-d H:i:s'),
                'date_subscription_end' => $oNow->format('Y-m-d H:i:s'),
                'date_cooling_off_end'  => $oNow->format('Y-m-d H:i:s'),
            ]
        );
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
        } elseif ($oInstance->isInFreeTrial()) {
            return true;
        } else {

            $oInvoice = $oInstance->invoice();
            if (empty($oInvoice)) {
                return true;
            }

            switch ($oInvoice->state->id) {
                case $this->oInvoiceModel::STATE_PAID:
                case $this->oInvoiceModel::STATE_PAID_PROCESSING:
                    return true;
                    break;
            }
        }

        return false;
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
            'or_where' => [
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
            '`date_%2$s_start` <= "%1$s" AND `date_%2$s_end`>= "%1$s"',
            $oWhen->format('Y-m-d H:i:s'),
            $sColumn
        );
    }
}
