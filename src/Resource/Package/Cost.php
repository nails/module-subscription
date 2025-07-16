<?php

/**
 * Cost resource
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Resource
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource\Package;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Currency;
use Nails\Factory;
use Nails\Subscription\Resource;
use stdClass;

/**
 * Class Cost
 *
 * @package Nails\Subscription\Resource
 */
class Cost extends Entity
{
    /** @var int */
    public $package_id;

    /** @var Resource\Package */
    public $package;

    /** @var Currency\Resource\Currency */
    public $currency;

    /** @var int */
    public $value_normal;

    /** @var int */
    public $value_initial;

    /** @var Currency\Resource\Price */
    public $price_normal;

    /** @var Currency\Resource\Price */
    public $price_initial;

    // --------------------------------------------------------------------------

    /**
     * Cost constructor.
     *
     * @throws Currency\Exception\CurrencyException
     * @throws FactoryException
     */
    public function __construct(self|stdClass|array $resource = [], ?Base $model = null)
    {
        parent::__construct($resource, $model);

        /** @var Currency\Service\Currency $oCurrency */
        $oCurrency = Factory::service('Currency', Currency\Constants::MODULE_SLUG);

        $this->currency = $oCurrency->getByIsoCode($this->currency);

        $this->price_normal = new Currency\Resource\Price([
            'price'    => $this->value_normal,
            'currency' => $this->currency,
        ]);

        $this->price_initial = new Currency\Resource\Price([
            'price'    => $this->value_initial,
            'currency' => $this->currency,
        ]);
    }
}
