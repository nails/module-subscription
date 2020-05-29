<?php

/**
 * Migration:   1
 * Started:     29/05/2020
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleSubscription;

use Nails\Common\Console\Migrate\Base;
use Nails\Common\Helper\Date;

class Migration1 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD `previous_instance_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `cancel_reason`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD `next_instance_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `previous_instance_id`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD FOREIGN KEY (`previous_instance_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_instance` (`id`) ON DELETE SET NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD FOREIGN KEY (`next_instance_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_instance` (`id`) ON DELETE SET NULL;');
    }
}
