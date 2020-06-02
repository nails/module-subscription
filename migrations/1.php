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
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD `log_group` VARCHAR(50) NULL DEFAULT NULL AFTER `next_instance_id`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD FOREIGN KEY (`previous_instance_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_instance` (`id`) ON DELETE SET NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}subscription_instance` ADD FOREIGN KEY (`next_instance_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_instance` (`id`) ON DELETE SET NULL;');
        $this>query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}subscription_log` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `log_group` varchar(50) NOT NULL DEFAULT \'\',
                `log` mediumtext,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_log_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_log_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
    }
}
