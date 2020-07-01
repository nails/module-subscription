<?php

/**
 * Instance resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Resource\DateTime;
use Nails\Common\Resource\Entity;
use Nails\Currency;
use Nails\Factory;
use Nails\Invoice;
use Nails\Subscription;

/**
 * Class Instance
 *
 * @package Nails\Subscription\Resource
 */
class Instance extends Entity
{
    /** @var int */
    public $customer_id;

    /** @var Invoice\Resource\Customer */
    public $customer;

    /** @var int */
    public $package_id;

    /** @var Package */
    public $package;

    /** @var int */
    public $source_id;

    /** @var Invoice\Resource\Source */
    public $source;

    /** @var int */
    public $invoice_id;

    /** @var Invoice\Resource\Invoice */
    public $invoice;

    /** @var Currency\Resource\Currency */
    public $currency;

    /** @var DateTime */
    public $date_free_trial_start;

    /** @var DateTime */
    public $date_free_trial_end;

    /** @var DateTime */
    public $date_subscription_start;

    /** @var DateTime */
    public $date_subscription_end;

    /** @var DateTime */
    public $date_cooling_off_start;

    /** @var DateTime */
    public $date_cooling_off_end;

    /** @var bool */
    public $is_automatic_renew;

    /** @var int */
    public $change_to_package_id;

    /** @var Package */
    public $change_to_package;

    /** @var DateTime */
    public $date_cancel;

    /** @var string */
    public $cancel_reason;

    /** @var int */
    public $previous_instance_id;

    /** @var self */
    public $previous_instance;

    /** @var int */
    public $next_instance_id;

    /** @var self */
    public $next_instance;

    /** @var string */
    public $log_group;

    // --------------------------------------------------------------------------

    /**
     * Instance constructor.
     *
     * @param array $mObj
     *
     * @throws Currency\Exception\CurrencyException
     * @throws FactoryException
     */
    public function __construct($mObj = [])
    {
        parent::__construct($mObj);

        /** @var Currency\Service\Currency $oCurrency */
        $oCurrency = Factory::service('Currency', Currency\Constants::MODULE_SLUG);

        $this->currency = $oCurrency->getByIsoCode($this->currency);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's customer
     *
     * @return Invoice\Resource\Customer|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function customer(): ?Invoice\Resource\Customer
    {
        if (!$this->customer && $this->customer_id) {

            /** @var Invoice\Model\Customer $oModel */
            $oModel = Factory::model('Customer', Invoice\Constants::MODULE_SLUG);

            $this->customer = $oModel->getById($this->customer_id);
        }

        return $this->customer;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's package
     *
     * @return Subscription\Resource\Package|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function package(): ?Subscription\Resource\Package
    {
        if (!$this->package && $this->package_id) {

            /** @var Subscription\Model\Package $oModel */
            $oModel = Factory::model('Package', Subscription\Constants::MODULE_SLUG);

            $this->package = $oModel->getById($this->package_id);
        }

        return $this->package;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's payment source
     *
     * @return Invoice\Resource\Source|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function source(): ?Invoice\Resource\Source
    {
        if (!$this->source && $this->source_id) {

            /** @var Invoice\Model\Source $oModel */
            $oModel = Factory::model('Source', Invoice\Constants::MODULE_SLUG);

            $this->source = $oModel->getById($this->source_id);
        }

        return $this->source;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's invoice
     *
     * @return Invoice\Resource\Invoice|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function invoice(): ?Invoice\Resource\Invoice
    {
        if (!$this->invoice && $this->invoice_id) {

            /** @var Invoice\Model\Invoice $oModel */
            $oModel = Factory::model('Invoice', Invoice\Constants::MODULE_SLUG);

            $this->invoice = $oModel->getById($this->invoice_id);
        }

        return $this->invoice;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance is in the free trial period
     *
     * @param \DateTime|null $oWhen A \DateTime to check, defaults to now
     *
     * @return bool
     * @throws FactoryException
     */
    public function isInFreeTrial(\DateTime $oWhen = null): bool
    {
        return $this->isInPeriod($oWhen, 'free_trial');
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance is in the subscription period
     *
     * @param \DateTime|null $oWhen A \DateTime to check, defaults to now
     *
     * @return bool
     * @throws FactoryException
     */
    public function isActive(\DateTime $oWhen = null)
    {
        return $this->isInPeriod($oWhen, 'subscription');
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance is in the cooling off period
     *
     * @param \DateTime|null $oWhen A \DateTime to check, defaults to now
     *
     * @return bool
     * @throws FactoryException
     */
    public function isCoolingOff(\DateTime $oWhen = null)
    {
        return $this->isInPeriod($oWhen, 'cooling_off');
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance is in a particular period
     *
     * @param \DateTime|null $oWhen     A \DateTime to check, defaults to now
     * @param string         $sProperty The property pair to check
     *
     * @return bool
     * @throws FactoryException
     */
    protected function isInPeriod(?\DateTime $oWhen, string $sProperty): bool
    {
        /** @var \DateTime $oWhen */
        $oWhen = $oWhen ?? Factory::factory('DateTime');

        return $this->{'date_' . $sProperty . '_start'}->getDateTimeObject() <= $oWhen
            && $this->{'date_' . $sProperty . '_end'}->getDateTimeObject() >= $oWhen;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance will automatically renew
     *
     * @return bool
     */
    public function isAutomaticRenew(): bool
    {
        return $this->is_automatic_renew;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the package which the subscription will change to at renewal
     *
     * @return Subscription\Resource\Package|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function changeToPackage(): ?Subscription\Resource\Package
    {
        if (!$this->change_to_package && $this->change_to_package_id) {

            /** @var Subscription\Model\Package $oModel */
            $oModel = Factory::model('Package', Subscription\Constants::MODULE_SLUG);

            $this->change_to_package = $oModel->getById($this->change_to_package_id);
        }

        return $this->change_to_package;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the instance is cancelled or not
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return !$this->isAutomaticRenew() && !empty($this->date_cancel);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a summary of the subscription
     *
     * @param bool $bThirdParty Whether the summary is being read by a third party or not
     *
     * @return Instance\Summary
     * @throws FactoryException
     */
    public function summary(bool $bThirdParty = false): Subscription\Resource\Instance\Summary
    {
        return $bThirdParty
            ? Factory::resource(
                'InstanceSummaryThirdParty',
                Subscription\Constants::MODULE_SLUG,
                ['oInstance' => $this]
            )
            : Factory::resource(
                'InstanceSummaryThirdParty',
                Subscription\Constants::MODULE_SLUG,
                ['oInstance' => $this]
            );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's previous instance
     *
     * @return self|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function previousInstance(): ?self
    {
        if (!$this->previous_instance && $this->previous_instance_id) {

            /** @var Subscription\Model\Instance $oModel */
            $oModel = Factory::model('Instance', Subscription\Constants::MODULE_SLUG);

            $this->previous_instance = $oModel->getById($this->previous_instance_id);
        }

        return $this->previous_instance;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance's next instance
     *
     * @return self|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function nextInstance(): ?self
    {
        if (!$this->next_instance && $this->next_instance_id) {

            /** @var Subscription\Model\Instance $oModel */
            $oModel = Factory::model('Instance', Subscription\Constants::MODULE_SLUG);

            $this->next_instance = $oModel->getById($this->next_instance_id);
        }

        return $this->next_instance;
    }
}
