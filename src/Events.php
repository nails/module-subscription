<?php

/**
 * Subscription module events
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Events
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription;

use Nails\Common\Events\Base;
use Nails\Common\Events\Subscription;
use Nails\Subscription\Event\Listener;
use Nails\Subscription\Exception\RenewalException\InstanceCannotRenewException;
use Nails\Subscription\Exception\RenewalException\InstanceFailedToRenewException;
use Nails\Subscription\Exception\RenewalException\InstanceShouldNotRenewException;
use Nails\Subscription\Resource;

/**
 * Class Events
 *
 * @package Nails\Subscription
 */
class Events extends Base
{
    /**
     * Fired when the first instance is created in a subscription
     *
     * @param Resource\Instance $oInstance The instance which was created
     */
    const CREATE_FIRST_INSTANCE = 'CREATE_FIRST_INSTANCE';

    /**
     * Fired when an instance renewal is processed but the renewal should not happen
     *
     * @param Resource\Instance               $oInstance  The instance being renewed
     * @param InstanceShouldNotRenewException $oException The exception which was thrown
     */
    const RENEWAL_INSTANCE_SHOULD_NOT_RENEW = 'RENEWAL_INSTANCE_SHOULD_NOT_RENEW';

    /**
     * Fired when an instance renewal is processed but the renewal cannot happen
     *
     * @param Resource\Instance            $oInstance  The instance being renewed
     * @param InstanceCannotRenewException $oException The exception which was thrown
     */
    const RENEWAL_INSTANCE_CANNOT_RENEW = 'RENEWAL_INSTANCE_CANNOT_RENEW';

    /**
     * Fired when an instance renewal is processed but fails to complete
     *
     * @param Resource\Instance              $oOldInstance The instance being renewed
     * @param Resource\Instance              $oNewInstance The new instance which was generated, if available
     * @param InstanceFailedToRenewException $oException   The exception which was thrown
     */
    const RENEWAL_INSTANCE_FAILED_TO_RENEW = 'RENEWAL_INSTANCE_FAILED_TO_RENEW';

    /**
     * Fired when an instance renewal is successfull
     *
     * @param Resource\Instance $oInstance The new instance which was generated
     */
    const RENEWAL_INSTANCE_RENEWED = 'RENEWAL_INSTANCE_RENEWED';

    /**
     * Fired when an instance renewal fails with an uncaught exception
     *
     * @param Resource\Instance      $oOldInstance The instance being renewed (if available)
     * @param Resource\Instance|null $oNewInstance The new instance which was generated (if available)
     * @param \Exception             $oException   The exception which was thrown
     */
    const RENEWAL_UNCAUGHT_EXCEPTION = 'RENEWAL_UNCAUGHT_EXCEPTION';

    // --------------------------------------------------------------------------

    /**
     * Autoloads event listeners
     *
     * @return Subscription[]
     */
    public function autoload(): array
    {
        return [
            new Listener\Invoice\Paid(),
        ];
    }
}
