<?php

/**
 * Package\Cost model
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Model
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Model\Package;

use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
use Nails\Subscription\Constants;

/**
 * Class Cost
 *
 * @package Nails\Subscription\Model\Package
 */
class Cost extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'subscription_package_cost';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'PackageCost';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    // --------------------------------------------------------------------------

    /**
     * Cost constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->addExpandableField([
                'trigger'   => 'package',
                'model'     => 'Package',
                'provider'  => Constants::MODULE_SLUG,
                'id_column' => 'package_id',
            ]);
    }
}
