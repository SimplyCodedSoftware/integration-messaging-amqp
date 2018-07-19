<?php

require __DIR__ . "/vendor/autoload.php";


$amqpConnection = \SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpConnectionFactory::create()
    ->setUsername("guest")
    ->setPassword("guest")
    ->setHost("rabbitmq")
    ->setVirtualHost("/");

$amqpQueue = \SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpQueueBuilder::createWithDirectExchangeBinding("test", "amqp_connection_factory", "matching", "")
    ->withMessageAck(true)
    ->withQueueDurability(true)
    ->withMessageConverterReferenceNames([])
    ->registerQueueOnInitialization(false)
    ->build(\SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService::createWith([
        "amqp_connection_factory" => $amqpConnection
    ]));

$requestChannel = \SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel::create();
$requestChannel->subscribe(new class implements \SimplyCodedSoftware\IntegrationMessaging\MessageHandler {
    /**
     * @inheritDoc
     */
    public function handle(\SimplyCodedSoftware\IntegrationMessaging\Message $message): void
    {
        /** @var \SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpAcknowledgementCallback $ack */
        $ack = $message->getHeaders()->get(\SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpHeaders::ACKNOWLEDGEMENT_CALLBACK);
        $ack->accept();

        echo $message->getPayload() . "\n";
    }

});

$amqpInboundAdapter = \SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpInboundAdapterBuilder::createWith(
    "inboundGateway",
    "test",
    "reply"
)->build(
    \SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver::createFromAssociativeArray([
        "test" => $amqpQueue,
        "reply" => $requestChannel
    ]),
    \SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService::createEmpty()
);

$amqpInboundAdapter->start();