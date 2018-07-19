<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Interface MessageDrivenConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageDrivenChannelAdapter
{
    /**
     * @param MessageHandler $onMessageCallback
     */
    public function startMessageDrivenConsumer(MessageHandler $onMessageCallback) : void;
}