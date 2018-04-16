<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AmqpHeaders
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpHeaders
{
    public const ACKNOWLEDGEMENT_CALLBACK = "amqp_ack_callback";
    public const MESSAGE_HEADER_NAME = "amqpHeaders";

    /**
     * @var AMQPChannel
     */
    private $channel;
    /**
     * @var string
     */
    private $deliveryTag;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var string
     */
    private $routingKey;
    /**
     * @var bool
     */
    private $isRedelivered;
    /**
     * @var string
     */
    private $contentEncoding;
    /**
     * @var bool
     */
    private $isTruncated;

    /**
     * AmqpHeaders constructor.
     * @param AMQPChannel $channel
     * @param string $deliveryTag
     * @param string $exchangeName
     * @param string $routingKey
     * @param bool $isRedelivered
     * @param string $contentEncoding
     * @param bool $isTruncated
     */
    private function __construct(
        AMQPChannel $channel, string $deliveryTag, string $exchangeName, string $routingKey, bool $isRedelivered,
        string $contentEncoding, bool $isTruncated
    )
    {
        $this->channel = $channel;
        $this->deliveryTag = $deliveryTag;
        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
        $this->isRedelivered = $isRedelivered;
        $this->contentEncoding = $contentEncoding;
        $this->isTruncated = $isTruncated;
    }

    /**
     * @param AMQPMessage $amqpMessage
     * @return AmqpHeaders
     */
    public static function createFrom(AMQPMessage $amqpMessage) : self
    {
        return new self(
            $amqpMessage->delivery_info['channel'],
            $amqpMessage->delivery_info['delivery_tag'],
            $amqpMessage->delivery_info['exchange'],
            $amqpMessage->delivery_info['routing_key'],
            $amqpMessage->delivery_info['redelivered'],
            $amqpMessage->content_encoding ? $amqpMessage->content_encoding : "",
            $amqpMessage->is_truncated
        );
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel() : AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getDeliveryTag() : string
    {
        return $this->deliveryTag;
    }

    /**
     * @return bool
     */
    public function isRedelivered(): bool
    {
        return $this->isRedelivered;
    }

    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function getContentEncoding(): string
    {
        return $this->contentEncoding;
    }

    /**
     * @return bool
     */
    public function isTruncated(): bool
    {
        return $this->isTruncated;
    }
}