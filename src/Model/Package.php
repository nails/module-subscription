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
}
