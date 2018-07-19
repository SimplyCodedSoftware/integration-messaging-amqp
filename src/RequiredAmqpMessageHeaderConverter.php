<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class SimpleAmqpMessageHeaderConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequiredAmqpMessageHeaderConverter implements AmqpMessageConverter
{
    /**
     * @var string[]
     */
    private $toAmqpHeaderNames;
    /**
     * @var string[]
     */
    private $fromAmqpHeaderNames;

    /**
     * SimpleAmqpMessageHeaderConverter constructor.
     * @param array $toAmqpHeaderNames
     * @param array $fromAmqpHeaderNames
     */
    private function __construct(array $toAmqpHeaderNames, array $fromAmqpHeaderNames)
    {
        $this->toAmqpHeaderNames = $toAmqpHeaderNames;
        $this->fromAmqpHeaderNames = $fromAmqpHeaderNames;
    }

    /**
     * @param array $toAmqpHeaderNames
     * @param array $fromAmqpHeaderNames
     *
     * @return RequiredAmqpMessageHeaderConverter
     */
    public static function createWith(array $toAmqpHeaderNames, array $fromAmqpHeaderNames) : self
    {
        return new self($toAmqpHeaderNames, $fromAmqpHeaderNames);
    }

    /**
     * @inheritDoc
     */
    public function toAmqpMessage(Message $message, AmqpMessageBuilder $amqpMessageBuilder): AmqpMessageBuilder
    {
        foreach ($this->toAmqpHeaderNames as $headerName) {
            $propertyValue      = $message->getHeaders()->get($headerName);
            $amqpMessageBuilder = $amqpMessageBuilder->addProperty($headerName, is_object($propertyValue) ? (string)$propertyValue : $propertyValue);
        }

        return $amqpMessageBuilder;
    }

    /**
     * @inheritDoc
     */
    public function fromAmqpMessage(AMQPMessage $amqpMessage, MessageBuilder $messageBuilder): MessageBuilder
    {
        $amqpHeaders = $amqpMessage->get('application_headers')->getNativeData();

        foreach ($this->fromAmqpHeaderNames as $headerName) {
            if (!array_key_exists($headerName, $amqpHeaders)) {
                throw InvalidArgumentException::create("Header to be mapped {$headerName} from AMQP message does not exists");
            }

            $messageBuilder = $messageBuilder->setHeader($headerName, is_object($amqpHeaders[$headerName]) ? (string)$amqpHeaders[$headerName] : $amqpHeaders[$headerName]);
        }

        return $messageBuilder;
    }
}