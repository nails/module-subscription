<?php

/**
 * Subscription Email: Instance Terminated
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
 * Class Terminated
 *
 * @package Nails\Subscription\Factory\Email\Instance
 */
class Terminated extends Email
{
    protected $sType = self::class;
}
