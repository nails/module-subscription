<?php

/**
 * Instance model
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
use Nails\Invoice;
use Nails\Subscription\Constants;

/**
 * Class Instance
 *
 * @package Nails\Subscription\Model
 */
class Instance extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'subscription_instance';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Instance';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    // --------------------------------------------------------------------------

    /**
     * Instance constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->addExpandableField([
                'trigger'   => 'customer',
                'model'     => 'Customer',
                'provider'  => Invoice\Constants::MODULE_SLUG,
                'id_column' => 'customer_id',
            ])
            ->addExpandableField([
                'trigger'   => 'package',
                'model'     => 'Package',
                'provider'  => Constants::MODULE_SLUG,
                'id_column' => 'package_id',
            ])
            ->addExpandableField([
                'trigger'   => 'source',
                'model'     => 'Source',
                'provider'  => Invoice\Constants::MODULE_SLUG,
                'id_column' => 'source_id',
            ])
            ->addExpandableField([
                'trigger'   => 'invoice',
                'model'     => 'Invoice',
                'provider'  => Invoice\Constants::MODULE_SLUG,
                'id_column' => 'invoice_id',
            ]);
    }
}
