<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Class PollableAmqpQueueBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpQueueBuilder implements MessageChannelBuilder
{
    private const DEFAULT_RECEIVE_TIMEOUT = 500;

    private const EXCHANGE_TYPE_DIRECT = "direct";
    private const EXCHANGE_TYPE_FANOUT = "fanout";

    /**
     * @var string
     */
    private $messageChannelName;
    /**
     * @var string
     */
    private $connectionReferenceName;
    /**
     * @var string[]
     */
    private $messageConverterReferenceNames = [];
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var string
     */
    private $exchangeType;
    /**
     * @var array
     */
    private $routingKeys;
    /**
     * @var bool
     */
    private $registerQueueOnInitialization = true;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds = self::DEFAULT_RECEIVE_TIMEOUT;
    /**
     * @var bool
     */
    private $withMessageAck = false;
    /**
     * @var bool
     */
    private $isDurable = false;

    /**
     * PollableAmqpQueueBuilder constructor.
     * @param string $messageChannelName
     * @param string $connectionReferenceName
     * @param string $exchangeName
     * @param string $exchangeType
     * @param string[] $routingKeys
     */
    private function __construct(string $messageChannelName, string $connectionReferenceName, string $exchangeName, string $exchangeType, array $routingKeys)
    {
        $this->messageChannelName = $messageChannelName;
        $this->connectionReferenceName = $connectionReferenceName;
        $this->exchangeName = $exchangeName;
        $this->routingKeys = $routingKeys;
        $this->exchangeType = $exchangeType;
    }

    /**
     * @param string $messageChannelName
     * @param string $connectionReferenceName
     * @param string $exchangeName
     * @param string $routingKey
     * @return AmqpQueueBuilder
     */
    public static function createWithDirectExchangeBinding(string $messageChannelName, string $connectionReferenceName, string $exchangeName, string $routingKey) : self
    {
        return new self($messageChannelName, $connectionReferenceName, $exchangeName, self::EXCHANGE_TYPE_DIRECT, [$routingKey]);
    }

    /**
     * @param array $messageConverterReferenceNames
     * @return AmqpQueueBuilder
     */
    public function withMessageConverterReferenceNames(array $messageConverterReferenceNames) : self
    {
        $this->messageConverterReferenceNames = $messageConverterReferenceNames;

        return $this;
    }

    /**
     * @param string $messageChannelName
     * @param string $connectionReferenceName
     * @param string $exchangeName
     * @return AmqpQueueBuilder
     */
    public static function createWithFanoutExchangeBinding(string $messageChannelName, string $connectionReferenceName, string $exchangeName) : self
    {
        return new self($messageChannelName, $connectionReferenceName, $exchangeName, self::EXCHANGE_TYPE_FANOUT, []);
    }

    /**
     * @param int $timeout
     * @return AmqpQueueBuilder
     */
    public function withReceiveTimeoutInMilliseconds(int $timeout) : self
    {
        $this->receiveTimeoutInMilliseconds = $timeout;

        return $this;
    }

    /**
     * @param bool $registerOnInitialization
     * @return AmqpQueueBuilder
     */
    public function registerQueueOnInitialization(bool $registerOnInitialization) : self
    {
        $this->registerQueueOnInitialization = $registerOnInitialization;

        return $this;
    }

    /**
     * @param bool $withMessageAck
     * @return AmqpQueueBuilder
     */
    public function withMessageAck(bool $withMessageAck) : self
    {
        $this->withMessageAck = $withMessageAck;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->messageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        $requiredReferences = $this->messageConverterReferenceNames;
        $requiredReferences[] = $this->connectionReferenceName;

        return $requiredReferences;
    }

    /**
     * @param bool $isDurable
     * @return AmqpQueueBuilder
     */
    public function withQueueDurability(bool $isDurable) : self
    {
        $this->isDurable = $isDurable;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        /** @var ConnectionFactory $connectionFactory */
        $connectionFactory = $referenceSearchService->findByReference($this->connectionReferenceName);
        $messageConverters = [BasicAmqpMessageHeadersConverter::create()];
        foreach ($this->messageConverterReferenceNames as $referenceName) {
            $messageConverters[] = $referenceSearchService->findByReference($referenceName);
        }

        return AmqpQueue::create(
            $this->getMessageChannelName(),
            $this->exchangeName,
            $this->exchangeType,
            $this->routingKeys,
            $connectionFactory,
            $messageConverters,
            $this->receiveTimeoutInMilliseconds,
            $this->registerQueueOnInitialization,
            $this->withMessageAck,
            $this->isDurable
        );
    }
}