<?php

/**
 * Package model
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Model
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Model;

use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Date;
use Nails\Common\Model\Base;
use Nails\Subscription\Constants;

/**
 * Class Package
 *
 * @package Nails\Subscription\Model
 */
class Package extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'subscription_package';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Package';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    /**
     * The supported billing periods
     *
     * @var string
     */
    const BILLING_PERIOD_DAY   = Date::PERIOD_DAY;
    const BILLING_PERIOD_MONTH = Date::PERIOD_MONTH;
    const BILLING_PERIOD_YEAR  = Date::PERIOD_YEAR;

    // --------------------------------------------------------------------------

    /**
     * Package constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->addExpandableField([
                'trigger'   => 'costs',
                'model'     => 'PackageCost',
                'provider'  => Constants::MODULE_SLUG,
                'type'      => static::EXPANDABLE_TYPE_MANY,
                'id_column' => 'package_id',
            ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the supported billing periods
     *
     * @return string[]
     */
    public function getBillingPeriods(): array
    {
        return [
            static::BILLING_PERIOD_DAY   => 'Day',
            static::BILLING_PERIOD_MONTH => 'Month',
            static::BILLING_PERIOD_YEAR  => 'Year',
        ];
    }
}
