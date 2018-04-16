<?php

namespace Fixture;

use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableConsumer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class TurnOffConsumerMessageHandler
 * @package Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TurnOffConsumerMessageHandler implements MessageHandler
{
    /**
     * @var ConsumerLifecycle
     */
    private $consumer;
    /**
     * @var Message
     */
    private $receivedMessage;

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->receivedMessage = $message;
        $this->consumer->stop();
    }

    public function getReceivedMessage() : ?Message
    {
        return $this->receivedMessage;
    }

    /**
     * @param ConsumerLifecycle $pollableConsumer
     */
    public function setConsumer(ConsumerLifecycle $pollableConsumer) : void
    {
        $this->consumer = $pollableConsumer;
    }
}