<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Amqp;

use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpCachedConnectionFactory;

/**
 * Class CachedRabbitmqConnectionFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpCachedConnectionFactoryTest extends AmqpMessagingTest
{
    public function test_using_same_instance_of_connection()
    {
        $cachedRabbitmqConnectionFactory = new AmqpCachedConnectionFactory($this->getRabbitConnectionFactory());

        $this->assertEquals(
            $cachedRabbitmqConnectionFactory->createConnection(),
            $cachedRabbitmqConnectionFactory->createConnection()
        );
    }
}