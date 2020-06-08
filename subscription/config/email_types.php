<?php

/**
 * Subscription Email: Email Types
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Email
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

use Nails\Subscription\Factory\Email;

$config['email_types'] = [
    (object) [
        'slug'            => Email\Instance\Cancelled::class,
        'name'            => 'Subscription: Instance Cancelled',
        'description'     => 'Email sent when a subscription instance is cancelled',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/cancelled',
        'template_footer' => '',
        'default_subject' => 'Your subscription has been cancelled',
    ],
    (object) [
        'slug'            => Email\Instance\Created::class,
        'name'            => 'Subscription: Instance Created',
        'description'     => 'Email sent when a subscription instance is created',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/',
        'template_footer' => '',
        'default_subject' => 'Your subscription',
    ],
    (object) [
        'slug'            => Email\Instance\Modified::class,
        'name'            => 'Subscription: Instance Modified',
        'description'     => 'Email sent when a subscription instance is modified',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/modified',
        'template_footer' => '',
        'default_subject' => 'Your subscription has changed',
    ],
    (object) [
        'slug'            => Email\Instance\Renew\Cannot::class,
        'name'            => 'Subscription: Instance Renewal: Cannot happen',
        'description'     => 'Email sent when a subscription instances reaches its renewal date but cannot be renewed',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/renew/cannot',
        'template_footer' => '',
        'default_subject' => 'Your subscription failed to renew',
    ],
    (object) [
        'slug'            => Email\Instance\Renew\Failed::class,
        'name'            => 'Subscription: Instance Renewal: Failed',
        'description'     => 'Email sent when a subscription instance fails to renew',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/renew/failed',
        'template_footer' => '',
        'default_subject' => 'Your subscription failed to renew',
    ],
    (object) [
        'slug'            => Email\Instance\Renew\FailedSca::class,
        'name'            => 'Subscription: Instance Renewal: Failed due to SCA',
        'description'     => 'Email sent when a subscription instance fails to renew due to SCA ',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/renew/failed_sca',
        'template_footer' => '',
        'default_subject' => 'Your subscription failed to renew',
    ],
    (object) [
        'slug'            => Email\Instance\Renew\Ok::class,
        'name'            => 'Subscription: Instance Renewal: OK',
        'description'     => 'Email sent when a subscription instance renews successfully',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/renew/ok',
        'template_footer' => '',
        'default_subject' => 'Your subscription has renewed',
    ],
    (object) [
        'slug'            => Email\Instance\Renew\WillNot::class,
        'name'            => 'Subscription: Instance Renewal: Will Not Happen',
        'description'     => 'Email sent when a subscription instance reaches its renewal date but will not be renewed',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/renew/will_not',
        'template_footer' => '',
        'default_subject' => 'Your subscription failed to renew',
    ],
    (object) [
        'slug'            => Email\Instance\Restored::class,
        'name'            => 'Subscription: Instance Restored',
        'description'     => 'Email sent when a subscription instance is restored',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/restored',
        'template_footer' => '',
        'default_subject' => 'Your subscription has been restored',
    ],
    (object) [
        'slug'            => Email\Instance\Swapped::class,
        'name'            => 'Subscription: Instance Swapped',
        'description'     => 'Email sent when a subscription instance is swapped',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/swapped',
        'template_footer' => '',
        'default_subject' => 'Your subscription has been changed',
    ],
    (object) [
        'slug'            => Email\Instance\Terminated::class,
        'name'            => 'Subscription: Instance Terminated',
        'description'     => 'Email sent when a subscription instance is terminated',
        'can_unsubscribe' => false,
        'template_header' => '',
        'template_body'   => 'subscription/email/instance/terminated',
        'template_footer' => '',
        'default_subject' => 'Your subscription has ended',
    ],
];
