<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Amqp;

use Fixture\TurnOffConsumerMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpInboundAdapterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableConsumer;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class AmqpInboundAdapterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundAdapterBuilderTest extends AmqpMessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_receving_message_from_inbound_adapter()
    {
        $amqpInboundAdapterBuilder = AmqpInboundAdapterBuilder::createWith("endpoint", "amqpChannel", "outputChannel");

        $directChannel = DirectChannel::create();
        $messageHandler = TurnOffConsumerMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $pollableChannel = $this->buildAmqpQueueFromBuilder($this->createDirectAmqpQueueBuilder());
        $amqpInboundAdapter = $amqpInboundAdapterBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                "amqpChannel" => $pollableChannel,
                "outputChannel" => $directChannel
            ]),
            InMemoryReferenceSearchService::createEmpty()
        );
        $messageHandler->setConsumer($amqpInboundAdapter);

        $pollableChannel->send(MessageBuilder::withPayload("test")->build());

        $amqpInboundAdapter->start();
        $this->assertNotNull($messageHandler->getReceivedMessage());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_reconnecting_to_broker_when_connection_was_interrupted()
    {
        $amqpInboundAdapterBuilder = AmqpInboundAdapterBuilder::createWith("endpoint", "amqpChannel", "outputChannel");

        $directChannel = DirectChannel::create();
        $messageHandler = TurnOffConsumerMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $pollableChannel = $this->buildAmqpQueueFromBuilder($this->createDirectAmqpQueueBuilder());
        $amqpInboundAdapter = $amqpInboundAdapterBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                "amqpChannel" => $pollableChannel,
                "outputChannel" => $directChannel
            ]),
            InMemoryReferenceSearchService::createEmpty()
        );
        $messageHandler->setConsumer($amqpInboundAdapter);
        $pollableChannel->send(MessageBuilder::withPayload("test")->build());

        $amqpInboundAdapter->start();

        $this->getCachedConnectionFactory()->createConnection()->close();

        $pollableChannel->send(MessageBuilder::withPayload("test")->build());
        $amqpInboundAdapter->start();
        $this->assertNotNull($messageHandler->getReceivedMessage());
    }
}