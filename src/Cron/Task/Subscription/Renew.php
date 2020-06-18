<?php

/**
 * The Renew Cron task
 *
 * @package  App
 * @category Task
 */

namespace Nails\Subscription\Cron\Task\Subscription;

use Nails\Cron\Task\Base;

/**
 * Class Renew
 *
 * @package Nails\Subscription\Cron\Task\Subscription
 */
class Renew extends Base
{
    /**
     * The cron expression of when to run
     *
     * @var string
     */
    const CRON_EXPRESSION = '1 1 * * *';

    /**
     * The console command to execute
     *
     * @var string
     */
    const CONSOLE_COMMAND = 'subscription:renew';
}
