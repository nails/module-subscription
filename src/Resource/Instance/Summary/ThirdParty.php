<?php

/**
 * Third Party Summary resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource\Instance\Summary;

use Nails\Subscription\Resource\Instance\Summary;

/**
 * Class ThirdParty
 *
 * @package Nails\Subscription\Resource\Instance\Summary
 */
class ThirdParty extends Summary
{
    /**
     * The summary sentences.
     */
    const SUMMARY_IN_TRIAL                      = '{{customer.label}} is subscribed to the <wrap>{{{package.label}}}</wrap> plan, which is paid <wrap>{{{package.termHuman}}}</wrap> – their free trial ends on <wrap>{{{instance.date_free_trial_end}}}</wrap>.';
    const SUMMARY_IN_PERIOD                     = '{{customer.label}} is subscribed to the <wrap>{{{package.label}}}</wrap> plan, which is paid <wrap>{{{package.termHuman}}}</wrap>.';
    const SUMMARY_WILL_RENEW                    = 'It will automatically renew on <wrap>{{{instance.date_subscription_end}}}</wrap> and their <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will be charged <wrap>{{{package.amount}}}</wrap>.';
    const SUMMARY_WILL_CHANGE                   = 'On <wrap>{{{instance.date_subscription_end}}}</wrap> they will change onto the <wrap>{{{newPackage.label}}}</wrap> package (which is paid <wrap>{{{newPackage.termHuman}}}</wrap>) and their <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will be charged <wrap>{{{newPackage.amount}}}</wrap>.';
    const SUMMARY_WILL_NOT_RENEW_NO_SOURCE      = 'Their subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap> as no payment source is available.';
    const SUMMARY_WILL_NOT_RENEW_EXPIRED_SOURCE = 'Their subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap> as their <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will have expired.';
    const SUMMARY_WILL_NOT_RENEW                = 'Their subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap>.';
    const SUMMARY_CANCELLED                     = 'At the customer\'s request, their subscription will come to an end on <wrap>{{{instance.date_subscription_end}}}</wrap>.';
}
