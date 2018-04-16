<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpCachedConnectionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpConnectionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpQueue;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpQueueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\ConnectionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class RabbitmqMessagingTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AmqpMessagingTest extends TestCase
{
    const RABBITMQ_HOST = 'rabbitmq';

    const RABBITMQ_USER = 'guest';

    const RABBITMQ_PASSWORD = 'guest';

    /**
     * @return AmqpQueueBuilder
     */
    public function createDirectAmqpQueueBuilder() : AmqpQueueBuilder
    {
        return AmqpQueueBuilder::createWithDirectExchangeBinding(
            Uuid::uuid4()->toString(), "reference", Uuid::uuid4()->toString(), ""
        )->withReceiveTimeoutInMilliseconds(1);
    }

    /**
     * @param AmqpQueueBuilder $amqpQueueBuilder
     * @return PollableChannel
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function buildAmqpQueueFromBuilder(AmqpQueueBuilder $amqpQueueBuilder) : PollableChannel
    {
        return $amqpQueueBuilder->build(InMemoryReferenceSearchService::createWith([
            "reference" => $this->getCachedConnectionFactory()
        ]));
    }

    /**
     * @return ConnectionFactory
     */
    public function getCachedConnectionFactory() : ConnectionFactory
    {
        return new AmqpCachedConnectionFactory(
            $this->getRabbitConnectionFactory()
        );
    }

    /**
     * @return ConnectionFactory
     */
    public function getRabbitConnectionFactory() : ConnectionFactory
    {
        return AmqpConnectionFactory::create()
            ->setHost(self::RABBITMQ_HOST)
            ->setUsername(self::RABBITMQ_USER)
            ->setPassword(self::RABBITMQ_PASSWORD);
    }
}