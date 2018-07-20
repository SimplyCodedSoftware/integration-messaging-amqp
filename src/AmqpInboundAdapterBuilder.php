<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableConsumer;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\GenericPollableGateway;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class AmqpInboundAdapterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundAdapterBuilder implements ConsumerBuilder
{
    /**
     * @var string
     */
    private $endpointName;
    /**
     * @var string
     */
    private $amqpChannelName;
    /**
     * @var string
     */
    private $outputChannel;
    /**
     * @var string
     */
    private $errorChannelName = "";
    /**
     * @var string[]
     */
    private $transactionFactoryReferenceNames = [];

    /**
     * AmqpInboundAdapterBuilder constructor.
     * @param string $endpointName
     * @param string $amqpChannelName
     * @param string $outputChannel
     */
    private function __construct(string $endpointName, string $amqpChannelName, string $outputChannel)
    {
        $this->endpointName = $endpointName;
        $this->amqpChannelName = $amqpChannelName;
        $this->outputChannel = $outputChannel;
    }

    /**
     * @param string $endpointName
     * @param string $amqpChannelName
     * @param string $outputChannel
     * @return AmqpInboundAdapterBuilder
     */
    public static function createWith(string $endpointName, string $amqpChannelName, string $outputChannel) : self
    {
        return new self($endpointName, $amqpChannelName, $outputChannel);
    }

    /**
     * @param string $errorChannelName
     * @return AmqpInboundAdapterBuilder
     */
    public function withErrorChannel(string $errorChannelName) : self
    {
        $this->errorChannelName = $errorChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return $this->transactionFactoryReferenceNames;
    }

    /**
     * @param array $referenceNames
     * @return AmqpInboundAdapterBuilder
     */
    public function withTransactionFactoryReferenceNames(array $referenceNames) : self
    {
        $this->transactionFactoryReferenceNames = $referenceNames;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): ConsumerLifecycle
    {
        /** @var MessageHandler $gateway */
        $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), MessageHandler::class, "handle", $this->outputChannel)
                        ->withTransactionFactories($this->transactionFactoryReferenceNames)
                        ->withErrorChannel($this->errorChannelName)
                        ->build($referenceSearchService, $channelResolver);

        $messageDrivenChannelAdapter = $channelResolver->resolve($this->amqpChannelName);
        if ($messageDrivenChannelAdapter instanceof MessageChannelAdapter) {
            $messageDrivenChannelAdapter = $messageDrivenChannelAdapter->getInternalMessageChannel();
        }

        Assert::isTrue($messageDrivenChannelAdapter instanceof MessageDrivenChannelAdapter, "Message Channel must be Message Driven Adapter {$this->amqpChannelName}");

        return MessageDrivenConsumer::create(
            $this->endpointName,
            $messageDrivenChannelAdapter,
            $gateway
        );
    }
}