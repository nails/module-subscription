<?php

/**
 * CallbackData factory
 *
 * @package     Nails
 * @subpackage  module-subscription
 * @category    Factory
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/subscription
 */

namespace Nails\Subscription\Resource\Instance;

use Mustache_Engine;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Resource;
use Nails\Common\Service\DateTime;
use Nails\Currency\Resource\Currency;
use Nails\Factory;
use Nails\Invoice\Resource\Invoice\Item\Data\Callback;
use Nails\Subscription\Constants;
use Nails\Subscription\Exception\SubscriptionException;
use Nails\Subscription\Model\Package;
use Nails\Subscription\Resource\Instance;
use Nails\Subscription\Resource\Package\Cost;
use stdClass;

/**
 * Class CallbackData
 *
 * @package Nails\Subscription\Resource\Instance
 */
class CallbackData extends \Nails\Invoice\Factory\Invoice\Item\CallbackData implements \JsonSerializable
{
    const IDENTIFIER   = 'INSTANCE_PAYMENT';
    const TYPE_INITIAL = 'INSTANCE_INITIAL';
    const TYPE_RENEWAL = 'INSTANCE_RENEWAL';

    // --------------------------------------------------------------------------

    /** @var int|null */
    protected $instance_id;

    /** @var Instance|null */
    protected $instance;

    /** @var Instance|null */
    protected $previous_instance;

    // --------------------------------------------------------------------------

    /**
     * Populates the callback data based on the line item's callback data
     *
     * @param Callback $oData The line item's callback data
     */
    public function setFromCallbackData(Callback $oData)
    {
        $this->instance_id = $oData->instance_id ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the instance ID
     *
     * @param int $iInstanceId The ID to set
     */
    public function setInstanceId(int $iInstanceId): self
    {
        $this->instance_id = $iInstanceId;
        if ($this->instance && $this->instance->id !== $iInstanceId) {
            $this->instance = null;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the instance ID
     *
     * @return int|null
     */
    public function getInstanceId(): ?int
    {
        return $this->instance_id;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the instance
     *
     * @param Instance $instance The instance to set
     */
    public function setInstance(Instance $instance): self
    {
        $this->instance    = $instance;
        $this->instance_id = $instance->id;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the associated instance
     *
     * @return Instance|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getInstance(): ?Instance
    {
        if (!$this->instance && $this->instance_id) {
            /** @var \Nails\Subscription\Model\Instance $oModel */
            $oModel         = Factory::model('Instance', Constants::MODULE_SLUG);
            $this->instance = $oModel->getById($this->instance_id);
        }

        return $this->instance;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the associated instance's previous instance
     *
     * @return Instance|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getPreviousInstance(): ?Instance
    {
        if (!$this->previous_instance && $this->instance) {
            $this->previous_instance = $this->instance->previousInstance();
        }

        return $this->previous_instance;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the line item's type, if there's a previous instance in the chain
     * then this is a renewal payment
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->getInstance()->previous_instance_id
            ? static::TYPE_RENEWAL
            : static::TYPE_INITIAL;
    }

    // --------------------------------------------------------------------------

    /**
     * Serializes the class
     *
     * @return \stdClass
     */
    public function jsonSerialize()
    {
        return (object) [
            'identifier'  => static::IDENTIFIER,
            'type'        => $this->getType(),
            'instance_id' => $this->getInstance()->id ?? null,
        ];
    }
}
