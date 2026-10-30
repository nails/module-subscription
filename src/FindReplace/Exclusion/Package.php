<?php

namespace Nails\Subscription\FindReplace\Exclusion;

use Nails\FindReplace\Interfaces\Exclusion;

class Package implements Exclusion
{
    public function hidden(): array
    {
        return [
            NAILS_DB_PREFIX . 'subscription_log',
        ];
    }

    public function readOnly(): array
    {
        return [
            NAILS_DB_PREFIX . 'subscription_instance.currency',
            NAILS_DB_PREFIX . 'subscription_instance.log_group',
            NAILS_DB_PREFIX . 'subscription_package_cost.currency',
        ];
    }
}
