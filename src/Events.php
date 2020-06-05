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

use Exception;
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
     * Fired when an instance renewal is successful
     *
     * @param Resource\Instance $oInstance The new instance which was generated
     */
    const RENEWAL_INSTANCE_RENEWED = 'RENEWAL_INSTANCE_RENEWED';

    /**
     * Fired when an instance renewal fails with an uncaught exception
     *
     * @param Resource\Instance      $oOldInstance The instance being renewed (if available)
     * @param Resource\Instance|null $oNewInstance The new instance which was generated (if available)
     * @param Exception              $oException   The exception which was thrown
     */
    const RENEWAL_UNCAUGHT_EXCEPTION = 'RENEWAL_UNCAUGHT_EXCEPTION';

    /**
     * Fired when an instance is swapped
     *
     * @param Resource\Instance $oInstance    The instance being swapped
     * @param Resource\Package  $oPackage     The old package
     * @param Resource\Package  $oPackage     The new package being applied
     * @param bool              $bImmediately Whether the swap was performed immediately or at the end of the term
     */
    const INSTANCE_SWAPPED = 'INSTANCE_SWAPPED';

    /**
     * Fired when an instance is modified
     *
     * @param Resource\Instance $oOriginalInstance The instance prior being modified
     * @param Resource\Instance $oModifiedInstance The instance after being modified
     */
    const INSTANCE_MODIFIED = 'INSTANCE_MODIFIED';

    /**
     * Fired when an instance is cancelled
     *
     * @param Resource\Instance $oCancelledInstance The instance being cancelled
     */
    const INSTANCE_CANCELLED = 'INSTANCE_CANCELLED';

    /**
     * Fired when an instance is restored
     *
     * @param Resource\Instance $oRestoredInstance The instance which was restored
     */
    const INSTANCE_RESTORED = 'INSTANCE_RESTORED';

    /**
     * Fired when an instance is terminated
     *
     * @param Resource\Instance $oTerminatedInstance The instance which was terminated
     */
    const INSTANCE_TERMINATED = 'INSTANCE_TERMINATED';

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
