<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface AmqpGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AmqpGateway
{
    /**
     * @param AMQPMessage $AMQPMessage
     * @return void
     */
    public function execute(AMQPMessage $AMQPMessage) : void;
}