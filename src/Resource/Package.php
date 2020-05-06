<?php

/**
 * Package resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Resource\DateTime;
use Nails\Common\Resource\Entity;
use Nails\Common\Resource\ExpandableField;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Subscription;

/**
 * Class Package
 *
 * @package Nails\Subscription\Resource
 */
class Package extends Entity
{
    /** @var string */
    public $label;

    /** @var string */
    public $billing_period;

    /** @var int */
    public $billing_duration;

    /** @var bool */
    public $is_active;

    /** @var DateTime|null */
    public $active_from;

    /** @var DateTime|null */
    public $active_to;

    /** @var bool */
    public $supports_free_trial;

    /** @var int */
    public $free_trial_duration;

    /** @var bool */
    public $supports_cooling_off;

    /** @var int */
    public $cooling_off_duration;

    /** @var bool */
    public $supports_automatic_renew;

    /** @var ExpandableField */
    public $costs;

    // --------------------------------------------------------------------------

    /**
     * Returns the package's costs
     *
     * @return ExpandableField
     * @throws FactoryException
     * @throws ModelException
     */
    public function costs(): ExpandableField
    {
        if (!$this->costs) {

            $this->costs = new ExpandableField();

            /** @var Subscription\Model\Package\Cost $oModel */
            $oModel            = Factory::model('PackageCost', Subscription\Constants::MODULE_SLUG);
            $this->costs->data = $oModel->getAll([
                'where' => [
                    ['package_id', $this->id],
                ],
            ]);

            $this->costs->count = count($this->costs->data);
        }

        return $this->costs;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the cost of a package for a given currency
     *
     * @param Currency $oCurrency The desired currency
     *
     * @return Package\Cost|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getCost(Currency $oCurrency): ?Subscription\Resource\Package\Cost
    {
        /** @var Subscription\Resource\Package\Cost[] $aCosts */
        $aCosts = $this->costs()->data;

        foreach ($aCosts as $oCost) {
            if ($oCost->currency === $oCurrency) {
                return $oCost;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the package is currently active
     *
     * @param \DateTime $oWhen The time to test against
     *
     * @return bool
     * @throws FactoryException
     */
    public function isActive(\DateTime $oWhen = null): bool
    {
        /** @var \DateTime $oWhen */
        $oWhen = $oWhen ?? Factory::factory('DateTime');

        return $this->is_active
            && (!$this->active_from || $this->active_from->getDateTimeObject() <= $oWhen)
            && (!$this->active_to || $this->active_to->getDateTimeObject() >= $oWhen);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the package supports a specific currency
     *
     * @param Currency $oCurrency The Currency to check
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function supportsCurrency(Currency $oCurrency): bool
    {
        /** @var Subscription\Resource\Package\Cost $oCost */
        foreach ($this->costs()->data as $oCost) {
            if ($oCost->currency === $oCurrency) {
                return true;
            }
        }
        return false;
    }
}
