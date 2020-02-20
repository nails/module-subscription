<?php

/**
 * Migration:   0
 * Started:     11/02/2020
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

class Migration0 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}subscription_instance` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) unsigned NOT NULL,
                `package_id` int(11) unsigned NOT NULL,
                `source_id` int(11) unsigned DEFAULT NULL,
                `invoice_id` int(11) unsigned DEFAULT NULL,
                `currency` char(3) NOT NULL DEFAULT \'\',
                `date_free_trial_start` datetime NOT NULL,
                `date_free_trial_end` datetime NOT NULL,
                `date_subscription_start` datetime NOT NULL,
                `date_subscription_end` datetime NOT NULL,
                `date_cooling_off_start` datetime NOT NULL,
                `date_cooling_off_end` datetime NOT NULL,
                `is_automatic_renew` tinyint(1) unsigned NOT NULL,
                `date_cancel` datetime DEFAULT NULL,
                `cancel_reason` varchar(150) NOT NULL DEFAULT \'\',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `customer_id` (`customer_id`),
                KEY `package_id` (`package_id`),
                KEY `source_id` (`source_id`),
                KEY `invoice_id` (`invoice_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_customer` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_4` FOREIGN KEY (`package_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_package` (`id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_5` FOREIGN KEY (`source_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_source` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_instance_ibfk_6` FOREIGN KEY (`invoice_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_invoice` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}subscription_package` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `label` varchar(150) DEFAULT NULL,
                `description` longtext,
                `billing_period` enum(\'' . Date::PERIOD_DAY . '\',\'' . Date::PERIOD_MONTH . '\',\'' . Date::PERIOD_YEAR . '\') DEFAULT NULL,
                `billing_duration` int(11) unsigned NOT NULL DEFAULT 1,
                `is_active` tinyint(1) unsigned NOT NULL DEFAULT 1,
                `active_from` datetime DEFAULT NULL,
                `active_to` datetime DEFAULT NULL,
                `supports_free_trial` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `free_trial_duration` int(11) unsigned DEFAULT NULL,
                `supports_cooling_off` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `cooling_off_duration` int(11) unsigned DEFAULT NULL,
                `supports_automatic_renew` tinyint(1) unsigned NOT NULL DEFAULT 1,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_package_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_package_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}subscription_package_cost` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `package_id` int(11) unsigned NOT NULL,
                `currency` char(3) NOT NULL DEFAULT \'\',
                `value_normal` int(11) unsigned NOT NULL DEFAULT 0,
                `value_initial` int(11) unsigned NOT NULL DEFAULT 0,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `package_id` (`package_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_package_cost_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_package_cost_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}subscription_package_cost_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `{{NAILS_DB_PREFIX}}subscription_package` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
    }
}
