<?php

/**
 * Package resource
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
use Nails\Common\Resource\ExpandableField;

/**
 * Class Package
 *
 * @package Nails\Subscription\Resource
 */
class Package extends Entity
{
    /** @var string */
    public $label;

    /** @var string */
    public $billing_period;

    /** @var int */
    public $billing_duration;

    /** @var bool */
    public $is_active;

    /** @var DateTime|null */
    public $active_from;

    /** @var DateTime|null */
    public $active_to;

    /** @var bool */
    public $supports_free_trial;

    /** @var int */
    public $free_trial_duration;

    /** @var bool */
    public $supports_cooling_off;

    /** @var int */
    public $cooling_off_duration;

    /** @var bool */
    public $supports_automatic_renew;

    /** @var ExpandableField */
    public $costs;
}
