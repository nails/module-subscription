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
use Nails\Common\Exception\ValidationException;
use Nails\Common\Factory\Logger;
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
use Nails\Subscription\Exception\RenewalException;
use Nails\Subscription\Exception\RenewalException\InstanceCannotRenewException;
use Nails\Subscription\Exception\RenewalException\InstanceShouldNotRenewException;
use Nails\Subscription\Exception\SubscriptionException;
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

    /** @var Logger */
    protected $oLogger;

    /** @var string */
    protected $sLogGroup;

    // --------------------------------------------------------------------------

    /**
     * Subscription constructor.
     *
     * @param \Nails\Subscription\Model\Instance|null $oInstanceModel The instance model to use
     * @param \Nails\Invoice\Model\Invoice|null       $oInvoiceModel  The invoice model to use
     * @param Logger|null                             $oLogger        The logger to use
     *
     * @throws FactoryException
     */
    public function __construct(
        \Nails\Subscription\Model\Instance $oInstanceModel = null,
        \Nails\Invoice\Model\Invoice $oInvoiceModel = null,
        Logger $oLogger = null
    ) {
        $this->oInstanceModel = $oInstanceModel ?? Factory::model('Instance', Constants::MODULE_SLUG);
        $this->oInvoiceModel  = $oInvoiceModel ?? Factory::model('Invoice', \Nails\Invoice\Constants::MODULE_SLUG);
        $this->oLogger        = $oLogger ?? Factory::factory('Logger');

        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        $this->oLogger->setFile('subscription-' . $oNow->format('Y-m-d') . '.php');

        //  Set an initial log group
        $this->setLogGroup(uniqid());
    }

    // --------------------------------------------------------------------------

    /**
     * Writes a line to the subscription log
     *
     * @param string $sLine The line to write
     *
     * @return $this
     * @throws FactoryException
     */
    public function log(string $sLine = ''): self
    {
        if ($this->sLogGroup && $sLine) {
            $sLine = sprintf(
                '[LOG GROUP: %s] – %s',
                $this->sLogGroup,
                $sLine
            );
        }
        $this->oLogger->line($sLine);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets a string to group logs together
     *
     * @param string $sLogGroup The grouping string
     *
     * @return $this;
     */
    public function setLogGroup(string $sLogGroup): self
    {
        $this->sLogGroup = $sLogGroup;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new subscription
     *
     * @param Customer      $oCustomer        The customer to apply the subscription to
     * @param Package       $oPackage         The package to apply
     * @param Source        $oSource          The payment source to use
     * @param Currency      $oCurrency        The currency to charge in
     * @param bool          $bCustomerPresent Whether the customer is present or not
     * @param string        $sSuccessUrl      For redirect transactions, where to send the user on success
     * @param string        $sErrorUrl        For redirect transactions, where to send the user on error
     * @param DateTime|null $oStart           When to start the subscription
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
        bool $bCustomerPresent,
        string $sSuccessUrl = '',
        string $sErrorUrl = '',
        DateTime $oStart = null
    ): Instance {

        /** @var \DateTime $oStart */
        $oStart = $oStart ?? Factory::factory('DateTime');

        // --------------------------------------------------------------------------

        $this
            ->log('Creating a new subscription')
            ->log('– Customer:   #' . $oCustomer->id)
            ->log('– Package:    #' . $oPackage->id)
            ->log('– Source:     #' . $oSource->id)
            ->log('– Currency:   ' . $oCurrency->code)
            ->log('– Start Date: ' . $oStart->format('Y-m-d H:i:s'));

        // --------------------------------------------------------------------------

        /**
         * Check if the customer is already subscribed, if they are then we cannot continue.
         */

        if ($this->isSubscribed($oCustomer, $oStart)) {
            $this->log('Aborting. Customer is already subscribed.');
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
        $this->log('User is able to use the package\'s free trial');

        // --------------------------------------------------------------------------

        /**
         * Calculate the various dates for the package. If it doesn't support a certain
         * feature (e.g. free trial) then those dates are 0 seconds long.
         */
        $this->log('Calculating dates:');
        if ($bCanUseFreeTrial) {
            [$oFreeTrialStart, $oFreeTrialEnd] = $this->calculateFreeTrialDates($oPackage, $oStart);
        } else {
            $oFreeTrialStart = clone $oStart;
            $oFreeTrialEnd   = clone $oStart;
        }
        [$oSubscriptionStart, $oSubscriptionEnd] = $this->calculateSubscriptionDates($oPackage, $oFreeTrialEnd);
        [$oCoolingOffStart, $oCoolingOffEnd] = $this->calculateCoolingOffDates($oPackage, $oStart);

        $this
            ->log('– Free trial start:   ' . $oFreeTrialStart->format('Y-m-d H:i:s'))
            ->log('– Free trial end:     ' . $oFreeTrialEnd->format('Y-m-d H:i:s'))
            ->log('– Subscription start: ' . $oSubscriptionStart->format('Y-m-d H:i:s'))
            ->log('– Subscription end:   ' . $oSubscriptionEnd->format('Y-m-d H:i:s'))
            ->log('– Cooling off start:  ' . $oCoolingOffStart->format('Y-m-d H:i:s'))
            ->log('– Cooling off end:    ' . $oCoolingOffEnd->format('Y-m-d H:i:s'));

        // --------------------------------------------------------------------------

        //  Validate the input, make sure we can use what is given
        $this
            ->validatePackage(
                $oPackage,
                $oCurrency
            )
            ->validateSource(
                $oSubscriptionStart,
                $oSource,
                $oCustomer
            );

        // --------------------------------------------------------------------------

        /**
         * Create the instance of the subscription. We want to record that the attempt
         * was made, regardless of the outcome of any invoice payment failures
         */

        $oInstance = $this->createInstance(
            $oCustomer,
            $oPackage,
            $oSource,
            $oCurrency,
            $oFreeTrialStart,
            $oFreeTrialEnd,
            $oSubscriptionStart,
            $oSubscriptionEnd,
            $oCoolingOffStart,
            $oCoolingOffEnd
        );

        // --------------------------------------------------------------------------

        try {

            $this->log('Payment flow: Begin');
            $this->chargeInvoice(
                $oInstance,
                $this->raiseInvoice(
                    $oInstance,
                    true //  @todo (Pablo - 2020-02-20) - Determine what price to charge
                ),
                $oSource,
                $bCustomerPresent,
                $sSuccessUrl,
                $sErrorUrl
            );

        } catch (RedirectRequiredException $e) {

            $this
                ->log('Payment flow: Caught Redirect')
                ->log('– ' . $e->getRedirectUrl());

            throw $e;

        } catch (\Throwable $e) {

            $this
                ->log('Payment flow: Uncaught exception')
                ->log('– Class: ' . get_class($e))
                ->log('– Error: ' . $e->getMessage())
                ->log('– Code:  ' . $e->getCode())
                ->log('– File:  ' . $e->getFile())
                ->log('– Line:  ' . $e->getLine());

            $this->terminate(
                $oInstance,
                sprintf(
                    'An exception ocurred during processing: %s with code %s; %s',
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            throw $e;

        } finally {
            $this->log('Payment flow: finished');
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
     * Validates the selected package can be purchased
     *
     * @param Package  $oPackage  The package being purchased
     * @param Currency $oCurrency The currency being used for the transaction
     *
     * @return Subscription
     * @throws FactoryException
     * @throws ModelException
     * @throws ValidationException
     */
    protected function validatePackage(Package $oPackage, Currency $oCurrency): Subscription
    {
        $this->log(sprintf(
            'Validating package #%s (%s)',
            $oPackage->id,
            $oPackage->label
        ));

        if (!$oPackage->isActive()) {
            $this->log('Invalid package: Not currently active');
            throw new ValidationException(
                sprintf(
                    'Package with ID #%s is not currently active',
                    $oPackage->id
                )
            );
        } elseif (!$oPackage->supportsCurrency($oCurrency)) {
            $this->log('Invalid package: Does not support payments in ' . $oCurrency->code);
            throw new ValidationException(
                sprintf(
                    'Package with ID #%s does not support payments in %s',
                    $oPackage->id,
                    $oCurrency->code
                )
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Validates the payment source can be used by the customer
     *
     * @param DateTime|null $oStart    When the subscription will be charged
     * @param Source        $oSource   The payment source to be charged
     * @param Customer      $oCustomer The customer being charged
     *
     * @return Subscription
     * @throws ValidationException
     */
    protected function validateSource(?DateTime $oStart, Source $oSource, Customer $oCustomer): Subscription
    {
        $this->log(sprintf(
            'Validating source #%s',
            $oSource->id
        ));

        $oStart = $oStart ?? Factory::factory('DateTime');

        if ($oSource->customer_id !== $oCustomer->id) {
            $this->log('Invalid source: Source does not belong to customer');
            throw new ValidationException(
                'Invalid payment source'
            );
        } elseif ($oSource->isExpired()) {
            $this->log('Invalid source: Source has expired');
            throw new ValidationException(
                'Payment source is expired'
            );
        } elseif ($oSource->isExpired($oStart)) {
            $this->log('Invalid source: Source will have expired at time of payment');
            throw new ValidationException(
                sprintf(
                    'Payment source expires %s; subscription will be billed %s',
                    $oSource->expiry->formatted,
                    toUserDate($oStart)
                )
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new instance
     *
     * @param Customer $oCustomer          The custoer to assign the instance too
     * @param Package  $oPackage           The package to assign to the instance
     * @param Source   $oSource            The payment source for the instance
     * @param Currency $oCurrency          The currency to charge in
     * @param DateTime $oFreeTrialStart    The free trial start date
     * @param DateTime $oFreeTrialEnd      The free trial end date
     * @param DateTime $oSubscriptionStart The subscription start date
     * @param DateTime $oSubscriptionEnd   The subscription end date
     * @param DateTime $oCoolingOffStart   The cooling off period start date
     * @param DateTime $oCoolingOffEnd     The cooling off period end date
     * @param Instance $oPreviousInstance  The previous instance in the chain, if applicable
     *
     * @return Instance
     * @throws ModelException
     */
    protected function createInstance(
        Customer $oCustomer,
        Package $oPackage,
        Source $oSource,
        Currency $oCurrency,
        DateTime $oFreeTrialStart,
        DateTime $oFreeTrialEnd,
        DateTime $oSubscriptionStart,
        DateTime $oSubscriptionEnd,
        DateTime $oCoolingOffStart,
        DateTime $oCoolingOffEnd,
        Instance $oPreviousInstance = null
    ): Instance {

        $this->log('Creating new instance:');
        $oInstance = $this->oInstanceModel->create(
            [
                'customer_id'             => $oCustomer->id,
                'package_id'              => $oPackage->id,
                'source_id'               => $oSource->id,
                'currency'                => $oCurrency->code,
                'date_free_trial_start'   => $oFreeTrialStart->format('Y-m-d H:i:s'),
                'date_free_trial_end'     => $oFreeTrialEnd->format('Y-m-d H:i:s'),
                'date_subscription_start' => $oSubscriptionStart->format('Y-m-d H:i:s'),
                'date_subscription_end'   => $oSubscriptionEnd->format('Y-m-d 23:59:59'),
                'date_cooling_off_start'  => $oCoolingOffStart->format('Y-m-d H:i:s'),
                'date_cooling_off_end'    => $oCoolingOffEnd->format('Y-m-d H:i:s'),
                'is_automatic_renew'      => $oPackage->supports_automatic_renew,
                'previous_instance_id'    => $oPreviousInstance->id ?? null,
                'log_group'               => $this->sLogGroup,
            ],
            true
        );

        if ($oInstance) {
            $this->log('– ID: #' . $oInstance->id);
        } else {
            $this->log('FAILED: ' . $this->oInstanceModel->lastError());
        }

        return $oInstance;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the subscription line item
     *
     * @param Instance $oInstance      The instance being charged
     * @param bool     $bIsNormalPrice Whether to charge the normal package price or not
     *
     * @return Invoice\Item
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function getLineItem(
        Instance $oInstance,
        bool $bIsNormalPrice = true
    ): Invoice\Item {

        $this->log('– Generating line item');

        $oPackage  = $oInstance->package();
        $oCurrency = $oInstance->currency;

        /** @var Invoice\Item $oItem */
        $oItem = Factory::factory('InvoiceItem', \Nails\Invoice\Constants::MODULE_SLUG);
        /** @var Instance\CallbackData $oCallbackData */
        $oCallbackData = Factory::resource('InstanceCallbackData', Constants::MODULE_SLUG, []);

        $oItem
            ->setLabel(
                sprintf(
                    'Subscription: %s',
                    $oPackage->label
                )
            )
            ->setUnit($oPackage->billing_period)
            ->setQuantity($oPackage->billing_duration)
            ->setUnitCost(
                $bIsNormalPrice
                    ? $oPackage->getCost($oCurrency)->value_normal
                    : $oPackage->getCost($oCurrency)->value_initial
            )
            ->setCallbackData(
                $oCallbackData
                    ->setInstance($oInstance)
            );

        $this->log('– Label:         ' . $oItem->getLabel());
        $this->log('– Unit:          ' . $oItem->getUnit());
        $this->log('– Quantity:      ' . $oItem->getQuantity());
        $this->log('– Unit Cost:     ' . $oItem->getUnitCost());
        $this->log('– Callback Data: ' . json_encode($oItem->getCallbackData()));

        return $oItem;
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

        $this->log('Raising a new invoice for instance #' . $oInstance->id);

        /** @var Invoice $oInvoiceBuilder */
        $oInvoiceBuilder = Factory::factory('Invoice', \Nails\Invoice\Constants::MODULE_SLUG);

        /** @var \Nails\Invoice\Resource\Invoice $oInvoice */
        $oInvoice = $oInvoiceBuilder
            ->setCustomerId($oInstance->customer())
            ->setCurrency($oInstance->currency)
            ->setDated($oInstance->date_subscription_start)
            ->addItem(
                $this->getLineItem(
                    $oInstance,
                    $bIsNormalPrice
                )
            )
            ->save();

        $this->log(sprintf(
            '– Associating invoice (#%s) with instance',
            $oInvoice->id
        ));
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
     * @param Instance                        $oInstance        The instance being charged
     * @param \Nails\Invoice\Resource\Invoice $oInvoice         The invoice to charge
     * @param Source                          $oSource          The Payment source to use
     * @param string                          $sSuccessUrl      Where to go on successfull payment
     * @param string                          $sErrorUrl        Where to go on dailed payment
     * @param bool                            $bForcePaymentNow Attempt payment now, even if it is not due
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
        bool $bCustomerPresent = false,
        string $sSuccessUrl = '',
        string $sErrorUrl = '',
        bool $bForcePaymentNow = false
    ): void {

        $this->log('Charging invoice #' . $oInvoice->id);
        $this->log('– Source:            ' . $oSource->id);
        $this->log('– Customer Present:  ' . json_encode($bCustomerPresent));
        $this->log('– Success URL:       ' . $sSuccessUrl);
        $this->log('– Error URL:         ' . $sErrorUrl);
        $this->log('– Force Payment Now: ' . json_encode($bForcePaymentNow));

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        if (
            ($bForcePaymentNow || $oNow->format('Y-m-d') === $oInvoice->due->format('Y-m-d'))
            && $oInvoice->totals->raw->grand
        ) {

            $this->log('Invoice to be paid now, building charge request');

            /** @var ChargeRequest $oChargeRequest */
            $oChargeRequest = Factory::factory('ChargeRequest', \Nails\Invoice\Constants::MODULE_SLUG);
            $oChargeRequest
                ->setAutoRedirect(false)
                ->setSuccessUrl($sSuccessUrl)
                ->setErrorUrl($sErrorUrl)
                ->setSource($oSource);

            $this->log('Executing charge request...');

            /** @var ChargeResponse $oChargeResponse */
            $oChargeResponse = $oInvoice
                ->charge($oChargeRequest);

            $this->log('Charge request executed successfully.');
            $this->log('Payment ID is #' . $oChargeResponse->getPayment()->id);

            if ($oChargeResponse->isRedirect()) {

                $this->log(sprintf(
                    'Payment is incomplete: Redirect required (%s)',
                    $oChargeResponse->getRedirectUrl()
                ));

                throw (new RedirectRequiredException('A redirect is required to complete payment.'))
                    ->setRedirectUrl($oChargeResponse->getRedirectUrl())
                    ->setInstance($oInstance);

            } elseif ($oChargeResponse->isFailed()) {

                $this->log(sprintf(
                    'Payment is incomplete: Failed (%s)',
                    $oChargeResponse->getErrorMessage()
                ));

                throw (new PaymentFailedException($oChargeResponse->getErrorMessage()))
                    ->setUserMessage($oChargeResponse->getErrorMessageUser())
                    ->setErrorCode($oChargeResponse->getErrorCode());

            } elseif ($oChargeResponse->isComplete()) {

                $this->log(sprintf(
                    'Payment is complete: Gateway transaction ID: %s',
                    $oChargeResponse->getPayment()->transaction_id
                ));

            } elseif ($oChargeResponse->isProcessing()) {

                $this->log(sprintf(
                    'Payments are processing: Gateway transaction ID: %s',
                    $oChargeResponse->getPayment()->transaction_id
                ));
            }

        } elseif (!$oInvoice->totals->raw->grand) {

            $this->log('Invoice is zero-value; marking as paid');
            $this->oInvoiceModel->setPaid($oInvoice->id);

        } else {

            $this->log('Invoice is not due to be paid now.');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renews an existing subscription instance
     *
     * @param Instance $oOldInstance     The subscription instance to renew
     * @param bool     $bCustomerPresent Whether the customer is present or not
     *
     * @return Instance
     */
    public function renew(Instance $oOldInstance, bool $bCustomerPresent): Instance
    {
        $oCustomer = $oOldInstance->customer();
        $oSource   = $oOldInstance->source();
        $oPackage  = $oOldInstance->changeToPackage() ?: $oOldInstance->package();

        // --------------------------------------------------------------------------

        $this
            ->log('Renewing an existing instance')
            ->log('– Instance:         #' . $oOldInstance->id)
            ->log('– Customer:         #' . $oCustomer->id)
            ->log('– Customer present: ' . json_encode($bCustomerPresent))
            ->log('– Source:           #' . $oSource->id)
            ->log('– New Package:      #' . $oPackage->id);

        // --------------------------------------------------------------------------

        $this
            ->instanceShouldRenew($oOldInstance)
            ->instanceCanRenew($oOldInstance);

        // --------------------------------------------------------------------------

        /**
         * Calculate instance dates, cooling off period and free trials no longer apply
         */
        $this->log('Calculating dates:');

        [$oSubscriptionStart, $oSubscriptionEnd] = $this->calculateSubscriptionDates(
            $oPackage,
            $oOldInstance->date_subscription_end->getDateTimeObject()
        );
        [$oFreeTrialStart, $oFreeTrialEnd] = [clone $oSubscriptionStart, clone $oSubscriptionStart];
        [$oCoolingOffStart, $oCoolingOffEnd] = [clone $oSubscriptionStart, clone $oSubscriptionStart];

        $this
            ->log('– Subscription start: ' . $oSubscriptionStart->format('Y-m-d H:i:s'))
            ->log('– Subscription end:   ' . $oSubscriptionEnd->format('Y-m-d H:i:s'))
            ->log('– Free trial start:   N/A (calculated as: ' . $oFreeTrialStart->format('Y-m-d H:i:s') . ')')
            ->log('– Free trial end:     N/A (calculated as: ' . $oFreeTrialEnd->format('Y-m-d H:i:s') . ')')
            ->log('– Cooling off start:  N/A (calculated as: ' . $oCoolingOffStart->format('Y-m-d H:i:s') . ')')
            ->log('– Cooling off end:    N/A (calculated as: ' . $oCoolingOffEnd->format('Y-m-d H:i:s') . ')');

        // --------------------------------------------------------------------------

        $oNewInstance = $this->createInstance(
            $oCustomer,
            $oPackage,
            $oSource,
            $oOldInstance->currency,
            $oFreeTrialStart,
            $oFreeTrialEnd,
            $oSubscriptionStart,
            $oSubscriptionEnd,
            $oCoolingOffStart,
            $oCoolingOffEnd,
            $oOldInstance
        );

        try {

            $this->log('Payment flow: Begin');
            $this->chargeInvoice(
                $oNewInstance,
                $this->raiseInvoice($oNewInstance),
                $oNewInstance->source(),
                $bCustomerPresent,
                '',
                '',
                true
            );

            return $oNewInstance;

        } catch (\Exception $e) {

            if ($e instanceof RedirectRequiredException) {

                $this
                    ->log('Payment flow: Caught Redirect')
                    ->log('– ' . $e->getRedirectUrl());

                throw (new RenewalException\InstanceFailedToRenewException($e->getMessage(), $e->getCode()))
                    ->setOriginalException($e)
                    ->setInstance($oOldInstance)
                    ->setNewInstance($oNewInstance);

            } elseif ($e instanceof PaymentFailedException) {

                $this
                    ->log('Payment flow: Caught payment failure')
                    ->log('– ' . $e->getMessage());

                throw (new RenewalException\InstanceFailedToRenewException($e->getMessage(), $e->getCode()))
                    ->setOriginalException($e)
                    ->setInstance($oOldInstance)
                    ->setNewInstance($oNewInstance);
            }

            $this
                ->log('Payment flow: Uncaught exception')
                ->log('– Class: ' . get_class($e))
                ->log('– Error: ' . $e->getMessage())
                ->log('– Code:  ' . $e->getCode())
                ->log('– File:  ' . $e->getFile())
                ->log('– Line:  ' . $e->getLine());

            throw $e;

        } finally {
            $this->log('Payment flow: finished');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Confirms a renewal and updates the instances in questions
     *
     * @param Instance $oInstance
     *
     * @throws FactoryException
     * @throws ModelException
     * @throws RenewalException\InstanceFailedToRenewException
     */
    public function confirmRenewal(Instance $oInstance): void
    {
        $sOriginalLogGroup = $this->sLogGroup;

        $this
            ->setLogGroup($oInstance->log_group)
            ->log('Confirming Renewal')
            ->log('Instance: #' . $oInstance->id);

        $this->log('Fetching associated invoice...');
        $oInvoice = $oInstance->invoice();

        if (empty($oInvoice)) {

            $this
                ->log('FAILED: Could not find associated invoice')
                ->setLogGroup($sOriginalLogGroup);

            throw new RenewalException\InstanceFailedToRenewException(
                'Could not find associated invoice'
            );
        }
        $this->log('Invoice: #' . $oInstance->id);

        if (!$this->invoiceIsPaid($oInvoice)) {

            $this
                ->log('FAILED: Invoice has not been paid')
                ->setLogGroup($sOriginalLogGroup);

            throw new RenewalException\InstanceFailedToRenewException(
                'Associated invoice has not been paid'
            );
        }

        $this->log('Fetching previous instance...');
        $oPreviousInstance = $oInstance->previousInstance();

        if (!empty($oPreviousInstance)) {

            $this
                ->log('– Previous instance: #' . $oInstance->id)
                ->log('Linking previous instance with current instance');

            $this->oInstanceModel->update(
                $oPreviousInstance->id,
                [
                    'next_instance_id' => $oInstance->id,
                ]
            );
        } else {
            $this->log('– No previous instance found');
        }

        $this->setLogGroup($sOriginalLogGroup);
    }

    // --------------------------------------------------------------------------

    /**
     * Tests Whether an instance _should_ renew
     *
     * @param Instance $oInstance
     *
     * @return $this
     * @throws FactoryException
     * @throws InstanceShouldNotRenewException
     * @throws ModelException
     */
    protected function instanceShouldRenew(Instance $oInstance): self
    {
        $this->log('Checking if instance should renew');

        if (!$oInstance->isAutomaticRenew()) {
            $e = new InstanceShouldNotRenewException(
                'Instance is configured to not renew'
            );
        } elseif (!empty($oInstance->nextInstance())) {
            $e = new InstanceShouldNotRenewException(
                'Instance has already been renewed'
            );
        }

        if (!empty($e)) {
            $this->log('Instance should not renew: ' . $e->getMessage());
            $e->setInstance($oInstance);
            throw $e;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Tests Whether an instance _can_ renew
     *
     * @param Instance $oInstance
     *
     * @return $this
     */
    protected function instanceCanRenew(Instance $oInstance): self
    {
        $this->log('Checking if instance can renew');

        $oNewPackage = $oInstance->changeToPackage() ?: $oInstance->package();
        $this->log(sprintf(
            '– New package: #%s (%s)',
            $oNewPackage->id,
            $oNewPackage->label
        ));

        try {

            $this
                ->validatePackage($oNewPackage, $oInstance->currency)
                ->validateSource(null, $oInstance->source(), $oInstance->customer());

        } catch (ValidationException $e) {
            $e = new InstanceCannotRenewException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
            $e->setInstance($oInstance);
            $this->log('Instance cannot renew: ' . $e->getMessage());
            throw $e;
        }

        return $this;
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
        $this->log('Cancelling instance: #' . $oInstance->id);

        if ($oInstance->isCancelled()) {
            $this->log('FAILED: Instance is already cancelled');
            throw new SubscriptionException(
                'Instance is already cancelled'
            );
        }

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        return $this->modify(
            $oInstance,
            [
                'is_automatic_renew' => false,
                'cancel_reason'      => $sReason,
                'date_cancel'        => $oNow->format('Y-m-d H:i:s'),
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Restores a cancelled subscription
     *
     * @param Instance $oInstance
     *
     * @return Instance
     */
    public function restore(Instance $oInstance): Instance
    {
        $this->log('Restoring instance: #' . $oInstance->id);

        if (!$oInstance->isCancelled()) {
            $this->log('FAILED: Instance is not in a cancelled state');
            throw new SubscriptionException(
                'Instance is not in a cancelled state'
            );
        }

        return $this->modify(
            $oInstance,
            [
                'is_automatic_renew' => true,
                'cancel_reason'      => null,
                'date_cancel'        => null,
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Immediately terminate a subscription
     *
     * @param Instance    $oInstance The subscription instance to terminate
     * @param string|null $sReason   The reason for termination
     *
     * @return Instance
     */
    public function terminate(Instance $oInstance, string $sReason = null): Instance
    {
        $this->log('Terminating instance: #' . $oInstance->id);
        if ($sReason) {
            $this->log('– Reason: ' . $sReason);
        }
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        return $this->modify(
            $oInstance,
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
     * @param array    $aData     Data to modify the subscription with
     *
     * @return Instance
     */
    public function modify(Instance $oInstance, array $aData): Instance
    {
        $this->log('Modifying instance: #' . $oInstance->id);
        foreach ($aData as $sKey => $mValue) {
            $this->log('– ' . $sKey . ': ' . json_encode($mValue));
        }

        if (!$this->oInstanceModel->update($oInstance->id, $aData)) {
            $this->log('FAILED: ' . $this->oInstanceModel->lastError());
            throw new SubscriptionException(
                sprintf(
                    'Failed to modify subscription. %s',
                    $this->oInstanceModel->lastError()
                )
            );
        }

        //  @todo (Pablo - 2020-05-06) - Prevent caching
        return $this->oInstanceModel->getById($oInstance->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the subscription to change to at the end of the billing term
     *
     * @param Instance $oInstance    The subscription instance to modify
     * @param Package  $oNewPackage  The new package to apply
     * @param bool     $bImmediately Whether to swap immediately
     *
     * @return Instance
     */
    public function swap(
        Instance $oInstance,
        Package $oNewPackage,
        bool $bImmediately = false
    ): Instance {

        $this
            ->log('Swapping an instance to a new package')
            ->log('– Instance:         #' . $oInstance->id)
            ->log('– New Package:      #' . $oNewPackage->id)
            ->log('– Swap Immediately: ' . json_encode($bAutoRenew));

        if ($bImmediately) {
            //  @todo (Pablo - 2020-05-06) - Handle swapping immediately
            $this->log('FAILED: Swapping immediately is not implemented');
            throw new SubscriptionException(
                'Swapping a subscription immediately is not currently implemented'
            );
        }

        if (!$oNewPackage->isActive($oInstance->date_subscription_end->getDateTimeObject())) {
            $this->log('FAILED: Desired package will not be active at time of renewal');
            throw new SubscriptionException(
                'Desired package will not be active at time of renewal'
            );
        }

        //  This test ensures that any previous swap request is reverted
        if ($oInstance->package_id === $oNewPackage->id) {
            $aData = [
                'is_automatic_renew'   => true,
                'change_to_package_id' => null,
            ];
        } else {
            $aData = [
                'is_automatic_renew'   => true,
                'package_id'           => $oInstance->id,
                'change_to_package_id' => $oNewPackage->id,
            ];
        }

        return $this->modify($oInstance, $aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets an instance's auto-renew flag
     *
     * @param Instance $oInstance  The instance to modify
     * @param bool     $bAutoRenew Whether auto-renew should be on or off
     *
     * @return Instance
     */
    public function setAutoRenew(Instance $oInstance, bool $bAutoRenew): Instance
    {
        $this
            ->log('Setting instance\'s auto renew status')
            ->log('– Instance: #' . $oInstance->id)
            ->log('– Status:   ' . json_encode($bAutoRenew));

        return $this->modify(
            $oInstance,
            [
                'is_automatic_renew' => $bAutoRenew,
            ]
        );
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

            return $this->invoiceIsPaid($oInvoice);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a customer's subscription instance, if any, for a particular date
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
     * Returns subscriptions which are due to renew on a particular date
     *
     * @param DateTime|null $oWhen           The date to fetch renewals for
     * @param bool          $bOnlyDueToRenew Filter out instances which are not due to renew, or have already been renewed
     *
     * @return Instance[]
     * @throws FactoryException
     */
    public function getRenewals(\DateTime $oWhen = null, bool $bOnlyDueToRenew = false): array
    {
        /** @var DateTime $oWhen */
        $oWhen = $oWhen ?? Factory::factory('DateTime');

        $aInstances = $this->oInstanceModel->getAll([
            'where' => [
                ['DATE(date_subscription_end)', $oWhen->format('Y-m-d')],
            ],
        ]);

        return $bOnlyDueToRenew
            ? $this->filterInstancesWhichWillNotRenew($aInstances)
            : $aInstances;
    }

    // --------------------------------------------------------------------------

    /**
     * Filters intances which will not renew
     * – Are set to not renew
     * – Have already renewed
     *
     * @param Instance[] $aInstances The instances to filter
     *
     * @return Instance[]
     */
    protected function filterInstancesWhichWillNotRenew(array $aInstances): array
    {
        return array_values(
            array_filter(
                $aInstances,
                function (Instance $oInstance) {

                    if (!$oInstance->isAutomaticRenew()) {
                        return false;
                    } elseif (!empty($oInstance->nextInstance())) {
                        return false;
                    }

                    return true;
                }
            )
        );
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

    // --------------------------------------------------------------------------

    /**
     * etermines whether an invoice is considered paid or not
     *
     * @param \Nails\Invoice\Resource\Invoice $oInvoice The invoice to check
     *
     * @return bool
     */
    protected function invoiceIsPaid(\Nails\Invoice\Resource\Invoice $oInvoice): bool
    {
        return in_array($oInvoice->state->id, [
            $this->oInvoiceModel::STATE_PAID,
            $this->oInvoiceModel::STATE_PAID_PROCESSING,
        ]);
    }
}
