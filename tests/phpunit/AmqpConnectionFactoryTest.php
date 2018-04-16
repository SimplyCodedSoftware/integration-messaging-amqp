<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Amqp;

/**
 * Class ConnectionFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpConnectionFactoryTest extends AmqpMessagingTest
{
    public function test_connecting_correctly_to_rabbit()
    {
        $connection = $this->getRabbitConnectionFactory()->createConnection();

        $this->assertTrue($connection->isConnected(), "Rabbitmq should be connected with default config");
    }
}