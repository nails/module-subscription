<?php

/**
 * Subscription Email: Instance Cancelled
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Email
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Factory\Email\Instance;

use Nails\Email\Factory\Email;

/**
 * Class Cancelled
 *
 * @package Nails\Subscription\Factory\Email\Instance
 */
class Cancelled extends Email
{
    protected $sType = self::class;
}
