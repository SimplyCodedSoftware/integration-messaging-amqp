<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

/**
 * Class AmqpAcknowledgementCallback
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class AmqpAcknowledgementCallback implements AcknowledgementCallback
{
    /**
     * @var bool
     */
    private $isAutoAck = true;
    /**
     * @var bool
     */
    private $isAcked = false;
    /**
     * @var AmqpHeaders
     */
    private $amqpHeaders;

    /**
     * AmqpAcknowledgementCallback constructor.
     * @param AmqpHeaders $amqpHeaders
     */
    public function __construct(AmqpHeaders $amqpHeaders)
    {
        $this->amqpHeaders = $amqpHeaders;
    }

    /**
     * @inheritDoc
     */
    public function isAcknowledged(): bool
    {
        return $this->isAcked;
    }

    /**
     * @inheritDoc
     */
    public function isAutoAck(): bool
    {
        return $this->isAutoAck;
    }

    /**
     * @inheritDoc
     */
    public function disableAutoAck(): void
    {
        $this->isAutoAck = false;
    }

    /**
     * @inheritDoc
     */
    public function accept(): void
    {
        $this->amqpHeaders->getChannel()->basic_ack($this->amqpHeaders->getDeliveryTag(), false);
        $this->isAcked = true;
    }

    /**
     * @inheritDoc
     */
    public function reject(): void
    {
        $this->amqpHeaders->getChannel()->basic_nack($this->amqpHeaders->getDeliveryTag(),false, false);
        $this->isAcked = true;
    }

    /**
     * @inheritDoc
     */
    public function requeue(): void
    {
        $this->amqpHeaders->getChannel()->basic_nack($this->amqpHeaders->getDeliveryTag(), false, true);
        $this->isAcked = true;
    }
}