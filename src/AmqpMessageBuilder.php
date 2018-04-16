<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class AmqpMessageBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpMessageBuilder
{
    /**
     * @var mixed
     */
    private $payload;
    /**
     * @var array
     */
    private $properties;

    /**
     * AmqpMessageBuilder constructor.
     * @param $payload
     * @param array $properties
     */
    private function __construct($payload, array $properties)
    {
        $this->payload = $payload;
        $this->properties = $properties;
    }

    /**
     * @param $payload
     * @return AmqpMessageBuilder
     */
    public static function createFromPayload($payload): self
    {
        return new self($payload, []);
    }

    /**
     * @param string $propertyName
     * @param $propertyValue
     * @return AmqpMessageBuilder
     */
    public function addProperty(string $propertyName, $propertyValue): self
    {
        $this->properties[$propertyName] = $propertyValue;

        return $this;
    }

    /**
     * @param mixed $payload
     * @return AmqpMessageBuilder
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return AMQPMessage
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function build(): AMQPMessage
    {
        Assert::notNullAndEmpty($this->payload, "Payload of amqp message can't be empty");

        $amqpMessage = new AMQPMessage($this->payload);
        $amqpMessage->set("application_headers", new \PhpAmqpLib\Wire\AMQPTable($this->properties));

        return $amqpMessage;
    }
}