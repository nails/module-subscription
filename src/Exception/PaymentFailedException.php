<?php

/**
 * PaymentFailedException exception
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Exception
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Exception;

/**
 * Class PaymentFailedException
 *
 * @package Nails\Subscription\Exception
 */
class PaymentFailedException extends SubscriptionException
{
    /** @var string */
    protected $sUserMessage;

    /** @var string */
    protected $sErrorCode;

    // --------------------------------------------------------------------------

    /**
     * Sets the user facing error message
     *
     * @param string $sUserMessage
     *
     * @return $this
     */
    public function setUserMessage(string $sUserMessage): self
    {
        $this->sUserMessage = $sUserMessage;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the user facing error message
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return $this->sUserMessage;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the error code
     *
     * @param string $sErrorCode
     *
     * @return $this
     */
    public function setErrorCode(string $sErrorCode): self
    {
        $this->sErrorCode = $sErrorCode;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->sErrorCode;
    }
}
