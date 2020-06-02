<?php

/**
 * Subscription invoice paid event listener
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Event Listener
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Event\Listener\Invoice;

use Nails\Common\Events\Subscription;
use Nails\Factory;
use Nails\Invoice\Events;
use Nails\Invoice\Resource\Invoice;
use Nails\Subscription\Constants;
use Nails\Subscription\Resource\Instance\CallbackData;

/**
 * Class Paid
 *
 * @package Nails\Subscription\Event\Listener\Invoice
 */
class Paid extends Subscription
{
    public function __construct()
    {
        $this
            ->setEvent([Events::INVOICE_PAID, Events::INVOICE_PAID_PROCESSING])
            ->setNamespace(\Nails\Invoice\Model\Invoice::getEventNamespace())
            ->setCallback([$this, 'execute']);
    }

    // --------------------------------------------------------------------------

    /**
     * @param Invoice $oInvoice
     */
    public function execute(Invoice $oInvoice)
    {
        /** @var \Nails\Subscription\Service\Subscription $oSubscription */
        $oSubscription = Factory::service('Subscription', Constants::MODULE_SLUG);
        /** @var CallbackData $oCallbackData */
        $oCallbackData = Factory::resource('InstanceCallbackData', Constants::MODULE_SLUG, []);

        /** @var Invoice\Item $oItem */
        foreach ($oInvoice->items->data as $oItem) {
            if ($oItem->callback_data->identifier ?? null === $oCallbackData::IDENTIFIER) {
                $oCallbackData->setFromCallbackData($oItem->callback_data);
                $oSubscription->confirmRenewal($oCallbackData->getInstance());
            }
        }
    }
}
