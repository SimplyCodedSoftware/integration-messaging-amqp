<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Interface ConnectionFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConnectionFactory
{
    /**
     * @return AbstractConnection
     */
    public function createConnection(): AbstractConnection;
}