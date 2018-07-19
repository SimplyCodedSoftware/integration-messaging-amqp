<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class MessageDrivenConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageDrivenConsumer implements ConsumerLifecycle
{
    /**
     * @var MessageDrivenChannelAdapter
     */
    private $messageDrivenChannelAdapter;
    /**
     * @var MessageHandler
     */
    private $messageHandler;
    /**
     * @var string
     */
    private $endpointName;

    /**
     * MessageDrivenConsumer constructor.
     * @param string $endpointName
     * @param MessageDrivenChannelAdapter $messageDrivenChannelAdapter
     * @param MessageHandler $messageHandler
     */
    private function __construct(string $endpointName, MessageDrivenChannelAdapter $messageDrivenChannelAdapter, MessageHandler $messageHandler)
    {
        $this->endpointName = $endpointName;
        $this->messageDrivenChannelAdapter = $messageDrivenChannelAdapter;
        $this->messageHandler = $messageHandler;
    }

    /**
     * @param string $endpointName
     * @param MessageDrivenChannelAdapter $messageDrivenChannelAdapter
     * @param MessageHandler $messageHandler
     * @return MessageDrivenConsumer
     */
    public static function create(string $endpointName, MessageDrivenChannelAdapter $messageDrivenChannelAdapter, MessageHandler $messageHandler) : self
    {
        return new self($endpointName, $messageDrivenChannelAdapter, $messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->messageDrivenChannelAdapter->startMessageDrivenConsumer($this->messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->endpointName;
    }
}