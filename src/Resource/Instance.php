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

use Nails\Common\Resource\DateTime;
use Nails\Common\Resource\Entity;
use Nails\Invoice\Resource\Customer;

/**
 * Class Instance
 *
 * @package Nails\Subscription\Resource
 */
class Instance extends Entity
{
    /** @var int */
    public $customer_id;

    /** @var Customer */
    public $customer;

    /** @var int */
    public $package_id;

    /** @var Package */
    public $package;

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
}
