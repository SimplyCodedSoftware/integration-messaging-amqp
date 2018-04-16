<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Amqp\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Amqp\AmqpInboundAdapterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class AmqpModuleConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleExtensionAnnotation()
 */
class AmqpModuleConfiguration implements ApplicationContextModuleExtension
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModuleExtension
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function canHandle($messagingComponent): bool
    {
        return $messagingComponent instanceof AmqpInboundAdapterBuilder;
    }

    /**
     * @inheritDoc
     */
    public function registerMessagingComponent(Configuration $configuration, $messagingComponent): void
    {
        /** @var AmqpInboundAdapterBuilder $messagingComponent  */
        Assert::isSubclassOf($messagingComponent, AmqpInboundAdapterBuilder::class, "Messaging component should be amqp inbound adapter");

        $configuration->registerConsumer($messagingComponent);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return ApplicationContextModule::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}