<?php

/**
 * RenewalException exception
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Exception
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Exception;

use Nails\Subscription\Resource\Instance;

/**
 * Class RenewalException
 *
 * @package Nails\Subscription\Exception
 */
class RenewalException extends SubscriptionException
{
    /** @var Instance */
    protected $oInstance;

    // --------------------------------------------------------------------------

    /**
     * Sets the instance this exception applies to
     *
     * @param Instance $oInstance
     *
     * @return $this
     */
    public function setInstance(Instance $oInstance): self
    {
        $this->oInstance = $oInstance;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance this exception applies to
     *
     * @return Instance|null
     */
    public function getInstance(): ?Instance
    {
        return $this->oInstance;
    }
}
