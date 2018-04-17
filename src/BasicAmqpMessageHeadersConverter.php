<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ContentTypeMessageHeaderConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BasicAmqpMessageHeadersConverter implements AmqpMessageConverter
{
    private const CONTENT_TYPE = 'content_type';
    private const TEXT_PLAIN = 'text/plain';


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
    public function toAmqpMessage(Message $message, AmqpMessageBuilder $amqpMessageBuilder): AmqpMessageBuilder
    {
        return $amqpMessageBuilder->addProperty(
            self::CONTENT_TYPE,
            $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
                ? $message->getHeaders()->get(MessageHeaders::CONTENT_TYPE)
                : self::TEXT_PLAIN
            );
    }

    /**
     * @inheritDoc
     */
    public function fromAmqpMessage(AMQPMessage $amqpMessage, MessageBuilder $messageBuilder): MessageBuilder
    {
        $amqpHeaders = $amqpMessage->get('application_headers')->getNativeData();
        $amqpInternalHeaders = AmqpHeaders::createFrom($amqpMessage);

        return $messageBuilder
            ->setHeader(MessageHeaders::CONTENT_TYPE, isset($amqpHeaders[self::CONTENT_TYPE]) ? $amqpHeaders[self::CONTENT_TYPE] : self::TEXT_PLAIN)
            ->setHeader(AmqpHeaders::MESSAGE_HEADER_NAME, $amqpInternalHeaders);
    }
}