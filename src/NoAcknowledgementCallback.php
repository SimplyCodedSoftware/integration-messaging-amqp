<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

/**
 * Class NullAcknowledgementCallback
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class NoAcknowledgementCallback implements AcknowledgementCallback
{
    private function __construct()
    {
    }

    /**
     * @return NoAcknowledgementCallback
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function isAcknowledged(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isAutoAck(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function disableAutoAck(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function accept(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function reject(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function requeue(): void
    {
        return;
    }
}