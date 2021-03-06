<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class RabbitQueue
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class AmqpQueue implements PollableChannel, MessageDrivenChannelAdapter
{
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var string
     */
    private $exchangeType;
    /**
     * @var string[]
     */
    private $routingKeys;
    /**
     * @var AmqpMessageConverter[]
     */
    private $messageConverters;
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var AMQPChannel
     */
    private $channel;
    /**
     * @var QueueChannel
     */
    private $internalQueue;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var bool
     */
    private $withMessageAck;
    /**
     * @var bool
     */
    private $isDurable;

    /**
     * PollableAmqpQueue constructor.
     * @param string $queueName
     * @param string $exchangeName
     * @param string $exchangeType
     * @param string[] $routingKeys
     * @param ConnectionFactory $connectionFactory
     * @param array|AmqpMessageConverter[] $messageConverters
     * @param bool $registerQueueOnInitialization
     * @param bool $withMessageAck
     * @param bool $isDurable
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(string $queueName, string $exchangeName, string $exchangeType, array $routingKeys, ConnectionFactory $connectionFactory, array $messageConverters, bool $registerQueueOnInitialization, bool $withMessageAck, bool $isDurable)
    {
        Assert::allInstanceOfType($messageConverters, AmqpMessageConverter::class);

        $this->queueName = $queueName;
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->routingKeys = $routingKeys;
        $this->messageConverters = $messageConverters;
        $this->connectionFactory = $connectionFactory;
        $this->internalQueue = QueueChannel::create();
        $this->withMessageAck = $withMessageAck;
        $this->isDurable = $isDurable;

        $this->initialize($registerQueueOnInitialization);
    }

    /**
     * @param string $queueName
     * @param string $exchangeName
     * @param string $exchangeType
     * @param array $routingKeys
     * @param ConnectionFactory $connectionFactory
     * @param array|AmqpMessageConverter[] $messageConverters
     * @param int $receiveTimeoutInMilliseconds
     * @param bool $registerQueueOnInitialization
     * @param bool $withMessageAck
     * @param bool $isDurable
     * @return AmqpQueue
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(
        string $queueName,
        string $exchangeName,
        string $exchangeType,
        array $routingKeys,
        ConnectionFactory $connectionFactory,
        array $messageConverters,
        int $receiveTimeoutInMilliseconds,
        bool $registerQueueOnInitialization,
        bool $withMessageAck,
        bool $isDurable
    ) : self
    {
        $pollableAmqpQueue = new self($queueName, $exchangeName, $exchangeType, $routingKeys, $connectionFactory, $messageConverters, $registerQueueOnInitialization, $withMessageAck, $isDurable);
        $pollableAmqpQueue->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;

        return $pollableAmqpQueue;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->declareQueueIfNotExists();

        $routingKeysAmount = count($this->routingKeys);
        $amqpmMessage = AmqpMessageBuilder::createFromPayload($message->getPayload());
        foreach ($this->messageConverters as $messageConverter) {
            $amqpmMessage = $messageConverter->toAmqpMessage($message, $amqpmMessage);
        }

        $amqpmMessage = $amqpmMessage->build();

        if ($routingKeysAmount === 0) {
            $this->getChannel()->basic_publish($amqpmMessage, $this->exchangeName, "");
        }else if ($routingKeysAmount === 1) {
            $this->getChannel()->basic_publish($amqpmMessage, $this->exchangeName, $this->routingKeys[0]);
        }
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        $this->declareQueueIfNotExists();
        $internalQueue = $this->internalQueue;
        $self = $this;

        $this->getChannel()->basic_consume($this->queueName, '', false, !$this->withMessageAck, false, false,
            function(AMQPMessage $amqpmMessage) use ($internalQueue, $self) {
                $convertedMessage = $self->convertMessage($amqpmMessage);
                $internalQueue->send($convertedMessage);
            }
        );

        try {
            $this->getChannel()->wait(null, true, $this->getTimeoutInSeconds());
        }catch (AMQPTimeoutException $e) {
            return null;
        }

        return $this->internalQueue->receive();
    }

    /**
     * @param MessageHandler $onMessageCallback
     */
    public function startMessageDrivenConsumer(MessageHandler $onMessageCallback) : void
    {
        $this->declareQueueIfNotExists();
        $channel = $this->getChannel();
        $self = $this;

        $this->getChannel()->basic_consume($this->queueName, '', false, !$this->withMessageAck, false, false,
            function(AMQPMessage $amqpmMessage) use ($onMessageCallback, $self) {
                $convertedMessage = $self->convertMessage($amqpmMessage);
                $onMessageCallback->handle($convertedMessage);
                $self->autoAck($convertedMessage);
            }
        );

        // Loop as long as the channel has callbacks registered
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @return AMQPChannel
     */
    private function getChannel() : AMQPChannel
    {
        if (!isset($this->channel) || !$this->channel->getConnection() || !$this->channel->getConnection()->isConnected()) {
            $this->channel = $this->connectionFactory->createConnection()->channel();
            $this->registerShutdownFunction($this->channel);
        }


        return $this->channel;
    }

    private function declareQueueIfNotExists(): void
    {
        $this->getChannel()->exchange_declare($this->exchangeName, $this->exchangeType, false, false, false);
        $this->getChannel()->queue_declare($this->queueName, false, $this->isDurable, false, false);

        if ($this->exchangeName && empty($this->routingKeys)) {
            $this->getChannel()->queue_bind($this->queueName, $this->exchangeName);
        }

        foreach ($this->routingKeys as $routingKey) {
            $this->getChannel()->queue_bind($this->queueName, $this->exchangeName, $routingKey);
        }
    }

    /**
     * @return float
     */
    private function getTimeoutInSeconds() : float
    {
        return $this->receiveTimeoutInMilliseconds / 1000;
    }

    /**
     * @param bool $registerQueueOnInitialization
     */
    private function initialize(bool $registerQueueOnInitialization) : void
    {
        if ($registerQueueOnInitialization) {
            $this->declareQueueIfNotExists();
        }
    }


    /**
     * @param AMQPChannel $channel
     */
    private function registerShutdownFunction(AMQPChannel $channel) : void
    {
        register_shutdown_function(
            function (AMQPChannel $channel, AbstractConnection $connection)
            {
                $channel->close();
                $connection->close();
            },
            $channel,
            $channel->getConnection()
        );
    }

    /**
     * @param AMQPMessage $amqpmMessage
     * @return Message
     */
    private function convertMessage(AMQPMessage $amqpmMessage) : Message
    {
        $message = MessageBuilder::withPayload($amqpmMessage->getBody())
            ->setHeader(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK,
                $this->withMessageAck
                    ? new AmqpAcknowledgementCallback(AmqpHeaders::createFrom($amqpmMessage))
                    : NoAcknowledgementCallback::create()
            );

        foreach ($this->messageConverters as $messageConverter) {
            $message = $messageConverter->fromAmqpMessage($amqpmMessage, $message);
        }

        return $message->build();
    }

    /**
     * @param Message $message
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function autoAck(Message $message) : void
    {
        /** @var AcknowledgementCallback $acknowledgeCallback */
        $acknowledgeCallback = $message->getHeaders()->get(AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        if (!$acknowledgeCallback->isAlreadyAcknowledged() && $acknowledgeCallback->isAutoAck()) {
            $acknowledgeCallback->accept();
        }
    }
}