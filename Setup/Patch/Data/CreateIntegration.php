<?php

namespace Luigisbox\Integration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;

class CreateIntegration implements DataPatchInterface
{
    private $moduleDataSetup;
    private $integrationManager;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConfigBasedIntegrationManager $integrationManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->integrationManager = $integrationManager;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        // Call to process integration config
        $this->integrationManager->processIntegrationConfig(['LuigisboxIntegration']);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.2.0'; // Specify your module version here.
    }
}

