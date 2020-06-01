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
    /** @var \Exception */
    protected $oOriginalException;

    /** @var Instance */
    protected $oInstance;

    /** @var Instance */
    protected $oNewInstance;

    // --------------------------------------------------------------------------

    /**
     * Sets the original exception which triggered this exception
     *
     * @param \Exception $oOriginalException
     */
    public function setOriginalException(\Exception $oOriginalException): self
    {
        $this->oOriginalException = $oOriginalException;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the original exception
     *
     * @return \Exception|null
     */
    public function getOriginalException(): ?\Exception
    {
        return $this->oOriginalException;
    }

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

    // --------------------------------------------------------------------------

    /**
     * Sets the new instance this exception applies to
     *
     * @param Instance $oInstance
     *
     * @return $this
     */
    public function setNewInstance(Instance $oInstance): self
    {
        $this->oNewInstance = $oInstance;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the new instance this exception applies to
     *
     * @return Instance|null
     */
    public function getNewInstance(): ?Instance
    {
        return $this->oNewInstance;
    }
}
