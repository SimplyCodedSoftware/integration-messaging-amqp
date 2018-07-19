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

$amqpQueue->send(\SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder::withPayload('["test"]')->build());
