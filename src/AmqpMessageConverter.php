<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Interface AmqpMessageConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AmqpMessageConverter
{
    /**
     * @param Message $message
     * @param AmqpMessageBuilder $amqpMessageBuilder
     * @return AmqpMessageBuilder
     */
    public function toAmqpMessage(Message $message, AmqpMessageBuilder $amqpMessageBuilder) : AmqpMessageBuilder;

    /**
     * @param AMQPMessage $amqpMessage
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function fromAmqpMessage(AMQPMessage $amqpMessage, MessageBuilder $messageBuilder) : MessageBuilder;
}