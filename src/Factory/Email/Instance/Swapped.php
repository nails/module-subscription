<?php

/**
 * Subscription Email: Instance Swapped
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
 * Class Swapped
 *
 * @package Nails\Subscription\Factory\Email\Instance
 */
class Swapped extends Email
{
    protected $sType = self::class;
}
