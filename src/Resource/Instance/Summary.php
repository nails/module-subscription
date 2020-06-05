<?php

/**
 * Summary resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource\Instance;

use InvalidArgumentException;
use Mustache_Engine;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Resource;
use Nails\Common\Service\DateTime;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Subscription\Exception\SubscriptionException;
use Nails\Subscription\Model\Package;
use Nails\Subscription\Resource\Instance;
use Nails\Subscription\Resource\Package\Cost;
use stdClass;

/**
 * Class Summary
 *
 * @package Nails\Subscription\Resource\Instance
 */
class Summary extends Resource
{
    /**
     * Open and close tags to wrap items in the summary message, e.g:
     * You are subscribed to the <strong>{{{package.label}}}</strong> package.
     */
    const WRAP_OPEN  = '';
    const WRAP_CLOSE = '';

    /**
     * The summary sentences.
     */
    const SUMMARY_IN_TRIAL                      = 'You\'re subscribed to our <wrap>{{{package.label}}}</wrap> plan, which is paid <wrap>{{{package.termHuman}}}</wrap> – your free trial ends on <wrap>{{{instance.date_free_trial_end}}}</wrap>.';
    const SUMMARY_IN_PERIOD                     = 'You\'re subscribed to our <wrap>{{{package.label}}}</wrap> plan, which is paid <wrap>{{{package.termHuman}}}</wrap>.';
    const SUMMARY_WILL_RENEW                    = 'It will automatically renew on <wrap>{{{instance.date_subscription_end}}}</wrap> and your <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will be charged <wrap>{{{package.amount}}}</wrap>.';
    const SUMMARY_WILL_CHANGE                   = 'On <wrap>{{{instance.date_subscription_end}}}</wrap> we will change you onto the <wrap>{{{newPackage.label}}}</wrap> package (which is paid <wrap>{{{newPackage.termHuman}}}</wrap>) and your <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will be charged <wrap>{{{newPackage.amount}}}</wrap>.';
    const SUMMARY_WILL_NOT_RENEW_NO_SOURCE      = 'Your subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap> as no payment source is available.';
    const SUMMARY_WILL_NOT_RENEW_EXPIRED_SOURCE = 'Your subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap> as your <wrap>{{{source.brand}}}</wrap> card ending <wrap>{{{source.last_four}}}</wrap> will have expired.';
    const SUMMARY_WILL_NOT_RENEW                = 'Your subscription will end on <wrap>{{{instance.date_subscription_end}}}</wrap>. Thank you for being a customer.';
    const SUMMARY_CANCELLED                     = 'At your request, your subscription will come to an end on <wrap>{{{instance.date_subscription_end}}}</wrap>. Thank you for being a customer.';

    // --------------------------------------------------------------------------

    /** @var Instance */
    protected $oInstance;

    /** @var \DateTime */
    protected $oDate;

    // --------------------------------------------------------------------------

    /**
     * Sets the time reference to summarise the instance from
     *
     * @param \DateTime|null $oDate A date to reference from
     *
     * @return $this
     */
    public function setDate(?\DateTime $oDate): self
    {
        $this->oDate = $oDate;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns details about the subscription instance, used when rendering the summary message
     *
     * @return stdClass
     * @throws FactoryException
     */
    public function getInstanceDetails(): stdClass
    {
        /** @var DateTime $oDateTimeService */
        $oDateTimeService = Factory::service('DateTime');
        $sFormat          = $oDateTimeService->getUserDateFormat();

        return (object) [
            'id'                      => $this->oInstance->id,
            'date_free_trial_start'   => $this->oInstance->date_free_trial_start->format($sFormat),
            'date_free_trial_end'     => $this->oInstance->date_free_trial_end->format($sFormat),
            'date_subscription_start' => $this->oInstance->date_subscription_start->format($sFormat),
            'date_subscription_end'   => $this->oInstance->date_subscription_end->format($sFormat),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns details about the current package, used when rendering the summary message
     *
     * @return stdClass|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getPackageDetails(): ?stdClass
    {
        return $this->extractPackageDetails($this->oInstance->package());
    }

    // --------------------------------------------------------------------------

    /**
     * Returns details about the new package, used when rendering the summary message
     *
     * @return stdClass|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getNewPackageDetails(): ?stdClass
    {
        return $this->extractPackageDetails($this->oInstance->changeToPackage());
    }

    // --------------------------------------------------------------------------

    /**
     * Returns details about the supplied package
     *
     * @param \Nails\Subscription\Resource\Package|null $oPackage A package to summarise
     *
     * @return stdClass|null
     * @throws FactoryException
     * @throws ModelException
     */
    protected function extractPackageDetails(?\Nails\Subscription\Resource\Package $oPackage): ?stdClass
    {
        if ($oPackage === null) {
            return null;
        }

        $aCosts = $oPackage->costs()->data;
        if (count($aCosts) > 1) {
            $aCosts = array_filter(
                $aCosts,
                function (Cost $oCost) {
                    return $oCost->currency === $this->getCurrency();
                }
            );
        }

        /** @var Cost $oCost */
        $oCost = reset($aCosts);

        return (object) [
            'id'        => $oPackage->id,
            'label'     => $oPackage->label,
            'term'      => $oPackage->billing_period,
            'termHuman' => $this->getHumanBillingPeriod($oPackage->billing_period),
            //  @todo (Pablo - 2020-05-01) - Handle initial pricing
            'amount'    => $oCost->price_normal->formatted ?? 'nothing',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * When a package has multiple currencies, this method returns the appropriate currency to use
     *
     * @return Currency
     * @throws SubscriptionException
     */
    protected function getCurrency(): Currency
    {
        throw new SubscriptionException(
            sprintf(
                'Multiple currencies available; the %s method must be overloaded and return the currency to use',
                __METHOD__
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a human/sentence friendly version of the billing period
     *
     * @param string $sBillingPeriod
     *
     * @return string
     */
    protected function getHumanBillingPeriod(string $sBillingPeriod): string
    {
        switch ($sBillingPeriod) {
            case Package::BILLING_PERIOD_DAY:
                return 'daily';
                break;

            case Package::BILLING_PERIOD_MONTH:
                return 'monthly';
                break;

            case Package::BILLING_PERIOD_YEAR:
                return 'annually';
                break;

            default:
                throw new InvalidArgumentException(
                    sprintf(
                        '"%s" is not a supported billing period',
                        $sBillingPeriod
                    )
                );
                break;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns details about the payment source, used when rendering the summary message
     *
     * @return stdClass|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getSourceDetails(): ?stdClass
    {
        $oSource = $this->oInstance->source();
        return $oSource
            ? (object) [
                'id'        => $this->oInstance->source()->id,
                'brand'     => $this->oInstance->source()->brand,
                'last_four' => $this->oInstance->source()->last_four,
            ]
            : null;
    }

    // --------------------------------------------------------------------------

    /**
     * @return string
     * @throws FactoryException
     * @throws ModelException
     */
    public function __toString()
    {
        $aMessage = [];

        if ($this->oInstance->isInFreeTrial($this->oDate)) {
            $aMessage[] = static::SUMMARY_IN_TRIAL;

        } else {
            $aMessage[] = static::SUMMARY_IN_PERIOD;
        }

        if ($this->oInstance->isCancelled()) {
            $aMessage[] = static::SUMMARY_CANCELLED;

        } elseif ($this->oInstance->isAutomaticRenew()) {

            $oSource = $this->oInstance->source();
            if (empty($oSource)) {
                $aMessage[] = static::SUMMARY_WILL_NOT_RENEW_NO_SOURCE;

            } elseif ($oSource->isExpired($this->oInstance->date_subscription_end->getDateTimeObject())) {
                $aMessage[] = static::SUMMARY_WILL_NOT_RENEW_EXPIRED_SOURCE;

            } elseif ($this->oInstance->change_to_package_id && $this->oInstance->package()->id !== $this->oInstance->change_to_package_id) {
                $aMessage[] = static::SUMMARY_WILL_CHANGE;

            } else {
                $aMessage[] = static::SUMMARY_WILL_RENEW;
            }

        } else {
            $aMessage[] = static::SUMMARY_WILL_NOT_RENEW;
        }

        $sMessage = implode(' ', $aMessage);
        $sMessage = str_replace(['<wrap>', '</wrap>'], [static::WRAP_OPEN, static::WRAP_CLOSE], $sMessage);

        /** @var Mustache_Engine $oMustache */
        $oMustache = Factory::service('Mustache');

        return $oMustache->render(
            $sMessage,
            [
                'instance'   => $this->getInstanceDetails(),
                'source'     => $this->getSourceDetails(),
                'package'    => $this->getPackageDetails(),
                'newPackage' => $this->getNewPackageDetails(),
            ]
        );
    }
}
