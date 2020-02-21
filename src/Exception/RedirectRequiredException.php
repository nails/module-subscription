<?php

/**
 * RedirectRequiredException exception
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
 * Class RedirectRequiredException
 *
 * @package Nails\Subscription\Exception
 */
class RedirectRequiredException extends SubscriptionException
{
    /** @var string */
    protected $sRedirectUrl;

    /** @var Instance */
    protected $oInstance;

    // --------------------------------------------------------------------------

    /**
     * Set the instance attached to the exception
     *
     * @param Instance $oInstance The instance to attach
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
     * Get the attached instance
     *
     * @return Instance|null
     */
    public function getInstance(): ?Instance
    {
        return $this->oInstance;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the redirect URL
     *
     * @param string $sRedirectUrl The URL to redirect to
     *
     * @return $this
     */
    public function setRedirectUrl(string $sRedirectUrl): self
    {
        $this->sRedirectUrl = $sRedirectUrl;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the redirect URL
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->sRedirectUrl;
    }
}
