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
use Nails\Invoice\Resource\Customer;
use Nails\Subscription\Resource\Instance;
use Nails\Subscription\Resource\Package;

/**
 * Class Subscription
 *
 * @package Nails\Subscription\Service
 */
class Subscription
{
    /**
     * Creates a new subscription
     *
     * @param Customer $oCustomer The customer to apply the subscription to
     * @param Package  $oPackage  The package to apply
     *
     * @return Instance
     */
    public function create(Customer $oCustomer, Package $oPackage): Instance
    {

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
        return (bool) $this->get($oCustomer, $oWhen);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a customer's subscription instance, if any
     *
     * @param Customer      $oCustomer The customer to check
     * @param DateTime|null $oWhen     The time period to check
     *
     * @return Instance|null
     */
    public function get(Customer $oCustomer, DateTime $oWhen = null): ?Instance
    {

    }
}
