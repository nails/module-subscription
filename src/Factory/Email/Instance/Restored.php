<?php

/**
 * Subscription Email: Instance Restored
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
 * Class Restored
 *
 * @package Nails\Subscription\Factory\Email\Instance
 */
class Restored extends Email
{
    protected $sType = self::class;

    // --------------------------------------------------------------------------

    /**
     * Returns test data to use when sending test emails
     *
     * @return array
     */
    public function getTestData(): array
    {
        //  @todo (Pablo 21/09/2020) - Add test data
        return [];
    }
}
