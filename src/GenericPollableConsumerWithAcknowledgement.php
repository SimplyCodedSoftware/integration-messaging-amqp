<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableConsumer;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableGateway;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class GenericPollableConsumerWithAcknowledgement
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GenericPollableConsumerWithAcknowledgement  implements ConsumerLifecycle
{
    /**
     * @var bool
     */
    private $isRunning = false;
    /**
     * @var string
     */
    private $consumerName;
    /**
     * @var PollableChannel
     */
    private $inputChannel;
    /**
     * @var GenericPollableGateway
     */
    private $gateway;

    /**
     * GenericPollableConsumer constructor.
     * @param string $consumerName
     * @param PollableChannel $inputChannel
     * @param GenericPollableGateway $gateway
     */
    private function __construct(string $consumerName, PollableChannel $inputChannel, GenericPollableGateway $gateway)
    {
        $this->consumerName = $consumerName;
        $this->inputChannel = $inputChannel;
        $this->gateway = $gateway;
    }

    /**
     * @param string                 $consumerName
     * @param PollableChannel        $inputChannel
     * @param GenericPollableGateway $gateway
     *
     * @return GenericPollableConsumerWithAcknowledgement
     */
    public static function createWith(string $consumerName, PollableChannel $inputChannel, GenericPollableGateway $gateway) : self
    {
        return new self($consumerName, $inputChannel, $gateway);
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->isRunning = true;

        while ($this->isRunning) {
            $receivedMessage = $this->inputChannel->receive();

            if ($receivedMessage) {
                $this->gateway->runFlow($receivedMessage);
                /** @var AcknowledgementCallback $acknowledgeCallback */
                $acknowledgeCallback = $receivedMessage->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);

                if ($acknowledgeCallback->isAutoAck()) {
                    $acknowledgeCallback->accept();
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->isRunning = false;
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
        return $this->consumerName;
    }
}