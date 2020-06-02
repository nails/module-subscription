<?php

/**
 * Log resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource;

use Nails\Common\Resource\Entity;

/**
 * Class Log
 *
 * @package Nails\Subscription\Resource
 */
class Log extends Entity
{
    /** @var string */
    public $log_group;

    /** @var string */
    public $log;
}
