<?php

namespace Tests\FindReplace\Exclusion;

use Nails\Subscription\FindReplace\Exclusion\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testHidden(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'subscription_log',
            ],
            (new Package())->hidden()
        );
    }

    public function testReadOnly(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'subscription_instance.currency',
                NAILS_DB_PREFIX . 'subscription_instance.log_group',
                NAILS_DB_PREFIX . 'subscription_package_cost.currency',
            ],
            (new Package())->readOnly()
        );
    }
}
