<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AcknowledgementCallback;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\ConnectionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpQueueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\OptionalAmqpMessageHeaderConverter;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\RequiredAmqpMessageHeaderConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class PollableAmqpQueueTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpQueueBuilderTest extends AmqpMessagingTest
{
    private const CONNECTION_REFERENCE = "reference";

    public function test_not_receiving_a_single_message_if_queue_is_empty()
    {
        $amqpQueueBuilder = AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4()->toString(), self::CONNECTION_REFERENCE, Uuid::uuid4(), "rabbit"
        );

        $amqpQueue = $this->buildAmqpQueue($amqpQueueBuilder);

        $this->assertNull($amqpQueue->receive());
    }

    public function test_receiving_from_direct_exchange_using_no_routing_key()
    {
        $exchangeName = Uuid::uuid4();
        $amqpQueueBuilder = AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4(), self::CONNECTION_REFERENCE, $exchangeName, ""
        );

        $amqpQueue = $this->buildAmqpQueue($amqpQueueBuilder);

        $payload = "somePayload";
        $amqpQueue->send(MessageBuilder::withPayload($payload)->build());

        $message = $amqpQueue->receive();
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(
            $payload,
            $message->getPayload()
        );

        $this->assertNull($amqpQueue->receive(), "There should be exactly one message in the queue. Got at least two.");
    }

    public function test_sending_receiving_message_from_direct_exchange_when_single_routing_key()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";
        $routingKey = "black";

        $this->buildDirectExchange($messageChannelName, $exchangeName, $routingKey)
            ->send(MessageBuilder::withPayload($payload)->build());

        $amqpQueue = $this->buildDirectExchange($messageChannelName, $exchangeName, $routingKey);

        $this->assertEquals(
            $payload,
            $amqpQueue->receive()->getPayload()
        );
    }

    public function test_sending_to_correct_queue_when_multiple_queues_bound_to_direct_exchange()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $blackQueue = $this->buildDirectExchange($messageChannelName, $exchangeName, "black");
        $whiteQueue = $this->buildDirectExchange($messageChannelName, $exchangeName, "white");

        $blackQueue->send(MessageBuilder::withPayload($payload)->build());

        $this->assertEquals(
            $payload,
            $blackQueue->receive()->getPayload()
        );
        $this->assertNull($whiteQueue->receive());
    }

    public function test_sending_to_fanout_exchange()
    {
        $exchangeName = Uuid::uuid4();

        $queue1 = $this->buildFanoutExchange(Uuid::uuid4(), $exchangeName);
        $queue2 = $this->buildFanoutExchange(Uuid::uuid4(), $exchangeName);
        $queue3 = $this->buildFanoutExchange(Uuid::uuid4(), $exchangeName);

        $queue1->send(MessageBuilder::withPayload("some")->build());

        $this->assertNotNull($queue1->receive());
        $this->assertNotNull($queue2->receive());
        $this->assertNotNull($queue3->receive());
    }

    public function test_adding_text_plain_content_type_when_not_given()
    {
        $exchangeName = Uuid::uuid4();
        $amqpQueueBuilder = AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4(), self::CONNECTION_REFERENCE, $exchangeName, ""
        );

        $amqpQueue = $this->buildAmqpQueue($amqpQueueBuilder);
        $amqpQueue->send(MessageBuilder::withPayload("somePayload")->build());

        $receivedMessage = $amqpQueue->receive();

        $this->assertEquals("text/plain", $receivedMessage->getHeaders()->get(MessageHeaders::CONTENT_TYPE));
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_mapping_headers()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $routingKey = "black";

        $amqpChannel = AmqpQueueBuilder::createWithDirectExchangeBinding(
                $messageChannelName,
                self::CONNECTION_REFERENCE,
                $exchangeName,
                $routingKey
            )
            ->withMessageConverterReferenceNames(["testConverter"]);

        $amqpChannel = $this->buildAmqpQueueWithReferences($amqpChannel, [
            "testConverter" => RequiredAmqpMessageHeaderConverter::createWith(
                ["hash"],
                ["hash"]
            )
        ]);

        $amqpChannel->send(
            MessageBuilder::withPayload("some")
                ->setHeader("hash", "123")
                ->setHeader(MessageHeaders::CONTENT_TYPE, "application/json")
                ->build()
        );

        $receivedMessage = $amqpChannel->receive();

        /** @var AmqpHeaders $amqpHeaders */
        $amqpHeaders = $receivedMessage->getHeaders()->get(AmqpHeaders::MESSAGE_HEADER_NAME);

        $this->assertInstanceOf(AmqpHeaders::class, $amqpHeaders);
        $this->assertInstanceOf(AMQPChannel::class, $amqpHeaders->getChannel());
        $this->assertNotNull($amqpHeaders->getDeliveryTag());
        $this->assertFalse($amqpHeaders->isRedelivered());
        $this->assertEquals($exchangeName, $amqpHeaders->getExchangeName());
        $this->assertEquals($routingKey, $amqpHeaders->getRoutingKey());

        $this->assertEquals($receivedMessage->getHeaders()->get(MessageHeaders::CONTENT_TYPE), "application/json");

        $this->assertEquals("123", $receivedMessage->getHeaders()->get("hash"));
    }

    public function test_mapping_with_optional_headers()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $routingKey = "black";

        $amqpChannel = AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            $routingKey
        )
            ->withMessageConverterReferenceNames(["testConverter"]);

        $amqpChannel = $this->buildAmqpQueueWithReferences($amqpChannel, [
            "testConverter" => OptionalAmqpMessageHeaderConverter::createWith(
                ["hash", "token"],
                ["hash", "token"]
            )
        ]);

        $amqpChannel->send(
            MessageBuilder::withPayload("some")
                ->setHeader("hash", "123")
                ->setHeader(MessageHeaders::CONTENT_TYPE, "application/json")
                ->build()
        );

        $receivedMessage = $amqpChannel->receive();

        $this->assertEquals("123", $receivedMessage->getHeaders()->get("hash"));
        $this->assertFalse($receivedMessage->getHeaders()->containsKey("token"));
    }

    public function test_mapping_with_objects_that_are_allowed_to_convert_to_string()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $routingKey = "black";

        $amqpChannel = AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            $routingKey
        )
            ->withMessageConverterReferenceNames(["testConverter"]);

        $amqpChannel = $this->buildAmqpQueueWithReferences($amqpChannel, [
            "testConverter" => OptionalAmqpMessageHeaderConverter::createWith(
                ["personId"],
                ["personId"]
            )
        ]);

        $amqpChannel->send(
            MessageBuilder::withPayload("some")
                ->setHeader("personId", Uuid::fromString("b2b41101-f0f7-428c-aa55-59097ac6053e"))
                ->build()
        );

        $receivedMessage = $amqpChannel->receive();

        $this->assertEquals("b2b41101-f0f7-428c-aa55-59097ac6053e", $receivedMessage->getHeaders()->get("personId"));
    }

    public function test_throwing_exception_if_mapped_header_not_found_during_receiving()
    {
        $exchangeName = Uuid::uuid4();
        $amqpQueueBuilder = AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4(), self::CONNECTION_REFERENCE, $exchangeName, ""
        )->withMessageConverterReferenceNames(["testConverter"]);

        $amqpChannel = $this->buildAmqpQueueWithReferences($amqpQueueBuilder, [
            "testConverter" => RequiredAmqpMessageHeaderConverter::createWith(
                [],
                ["hash"]
            )
        ]);

        $this->expectException(InvalidArgumentException::class);

        $amqpChannel->send(MessageBuilder::withPayload("somePayload")->build());
        $amqpChannel->receive();
    }

    public function test_requeue_received_message()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $pollableChannel = $this->buildAmqpQueue(AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            ""
        )->withMessageAck(true));

        $pollableChannel
            ->send(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $pollableChannel->receive();

        /** @var AcknowledgementCallback $callback */
        $callback = $receivedMessage->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        $callback->requeue();

        $this->assertNotNull($pollableChannel->receive());
    }

    public function test_rejecting_received_message()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $pollableChannel = $this->buildAmqpQueue(AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            ""
        )->withMessageAck(true));
        $pollableChannel
            ->send(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $pollableChannel->receive();

        /** @var AcknowledgementCallback $callback */
        $callback = $receivedMessage->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        $callback->reject();

        $this->assertNull($pollableChannel->receive());

        /** @var AmqpHeaders $amqpHeaders */
        $amqpHeaders = $receivedMessage->getHeaders()->get(AmqpHeaders::MESSAGE_HEADER_NAME);
        $amqpHeaders->getChannel()->close();

        $this->assertNull($this->buildDirectExchange($messageChannelName, $exchangeName, "")->receive());
    }

    public function test_returning_message_to_channel_if_not_acked_and_connection_lost()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $pollableChannel = $this->buildAmqpQueue(AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            ""
        )->withMessageAck(true));
        $pollableChannel
            ->send(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $pollableChannel->receive();
        /** @var AmqpHeaders $amqpHeaders */
        $amqpHeaders = $receivedMessage->getHeaders()->get(AmqpHeaders::MESSAGE_HEADER_NAME);
        $amqpHeaders->getChannel()->close();

        $this->assertNotNull($this->buildDirectExchange($messageChannelName, $exchangeName, "")->receive());
    }

    public function test_acking_message()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $pollableChannel = $this->buildAmqpQueue(AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            ""
        )->withMessageAck(true));
        $pollableChannel
            ->send(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $pollableChannel->receive();
        /** @var AcknowledgementCallback $callback */
        $callback = $receivedMessage->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        $callback->accept();
        /** @var AmqpHeaders $amqpHeaders */
        $amqpHeaders = $receivedMessage->getHeaders()->get(AmqpHeaders::MESSAGE_HEADER_NAME);
        $amqpHeaders->getChannel()->close();

        $this->assertNull($this->buildDirectExchange($messageChannelName, $exchangeName, "")->receive());
    }

    public function test_acking_message_when_channel_do_not_require_it()
    {
        $messageChannelName = Uuid::uuid4();
        $exchangeName = Uuid::uuid4();
        $payload = "somePayload";

        $pollableChannel = $this->buildAmqpQueue(AmqpQueueBuilder::createWithDirectExchangeBinding(
            $messageChannelName,
            self::CONNECTION_REFERENCE,
            $exchangeName,
            ""
        )->withMessageAck(false));
        $pollableChannel
            ->send(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $pollableChannel->receive();
        /** @var AcknowledgementCallback $callback */
        $callback = $receivedMessage->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        $callback->accept();
        /** @var AmqpHeaders $amqpHeaders */
        $amqpHeaders = $receivedMessage->getHeaders()->get(AmqpHeaders::MESSAGE_HEADER_NAME);
        $amqpHeaders->getChannel()->close();

        $this->assertNull($this->buildDirectExchange($messageChannelName, $exchangeName, "")->receive());
    }

    public function test_reconnecting_to_broker_when_connection_interrupted()
    {
        $exchangeName = Uuid::uuid4();
        $amqpQueueBuilder = AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4(), self::CONNECTION_REFERENCE, $exchangeName, ""
        );

        $amqpQueue = $this->buildAmqpQueue($amqpQueueBuilder);

        $payload = "somePayload";
        $amqpQueue->send(MessageBuilder::withPayload($payload)->build());

        $this->getCachedConnectionFactory()->createConnection()->close();

        $this->assertNotNull($amqpQueue->receive());
    }

    /**
     * @param AmqpQueueBuilder $amqpQueueBuilder
     * @param array $references
     * @return PollableChannel
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function buildAmqpQueueWithReferences(AmqpQueueBuilder $amqpQueueBuilder, array $references)
    {
        $amqpQueueBuilder->withReceiveTimeoutInMilliseconds(1);

        /** @var PollableChannel $messageChannel */
        $messageChannel = $amqpQueueBuilder->build(InMemoryReferenceSearchService::createWith(
            array_merge(
                $references,
                [
                    self::CONNECTION_REFERENCE => $this->getCachedConnectionFactory()
                ]
            )
        ));

        return $messageChannel;
    }

    /**
     * @param $amqpQueueBuilder
     * @return PollableChannel
     */
    private function buildAmqpQueue(AmqpQueueBuilder $amqpQueueBuilder): PollableChannel
    {
        return $this->buildAmqpQueueWithCustomConnectionFactory($amqpQueueBuilder, $this->getCachedConnectionFactory());
    }

    /**
     * @param AmqpQueueBuilder $amqpQueueBuilder
     * @param ConnectionFactory $connectionFactory
     * @return PollableChannel
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function buildAmqpQueueWithCustomConnectionFactory(AmqpQueueBuilder $amqpQueueBuilder, ConnectionFactory $connectionFactory) : PollableChannel
    {
        $amqpQueueBuilder->withReceiveTimeoutInMilliseconds(1);

        /** @var PollableChannel $messageChannel */
        $messageChannel = $amqpQueueBuilder->build(InMemoryReferenceSearchService::createWith([
            self::CONNECTION_REFERENCE => $connectionFactory
        ]));

        return $messageChannel;
    }

    /**
     * @param $messageChannelName
     * @param $exchangeName
     * @param $routingKey
     * @return PollableChannel
     */
    private function buildDirectExchange($messageChannelName, $exchangeName, $routingKey): PollableChannel
    {
        return $this->buildAmqpQueue(
            AmqpQueueBuilder::createWithDirectExchangeBinding(
                $messageChannelName,
                self::CONNECTION_REFERENCE,
                $exchangeName,
                $routingKey
            ));
    }

    /**
     * @param string $messageChannelName
     * @param string $exchangeName
     * @return PollableChannel
     */
    private function buildFanoutExchange(string $messageChannelName, string $exchangeName): PollableChannel
    {
        return $this->buildAmqpQueue(
            AmqpQueueBuilder::createWithFanoutExchangeBinding(
                $messageChannelName,
                self::CONNECTION_REFERENCE,
                $exchangeName
            )
        );
    }
}