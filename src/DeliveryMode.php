<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp;

/**
 * Class DeliveryMode
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DeliveryMode
{
    public const NON_PERSISTENT = 1;
    public const PERSISTENT = 2;

    /**
     * @var int
     */
    private $mode;

    /**
     * DeliveryMode constructor.
     * @param int $mode
     */
    private function __construct(int $mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return DeliveryMode
     */
    public static function createNonPersistent() : self
    {
        return new self(self::NON_PERSISTENT);
    }

    /**
     * @return DeliveryMode
     */
    public static function createPersistent() : self
    {
        return new self(self::PERSISTENT);
    }

    /**
     * @return int
     */
    public function getMode() : int
    {
        return $this->mode;
    }
}