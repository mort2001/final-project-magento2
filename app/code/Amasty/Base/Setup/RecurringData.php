<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Magento 2 Base Package
 */

namespace Amasty\Base\Setup;

use Magento\Framework\Module\Manager;
use Magento\Framework\Module\Status;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class RecurringData implements InstallDataInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Status
     */
    private $moduleStatus;

    /**
     * @var array
     */
    private $modulesToDisable = [];

    public function __construct(
        Manager $moduleManager,
        Status $moduleStatus,
        array $modulesToDisable = []
    ) {
        $this->moduleManager = $moduleManager;
        $this->moduleStatus = $moduleStatus;
        $this->modulesToDisable = $this->initModulesToDisable($modulesToDisable);
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!empty($this->modulesToDisable)) {
            $this->moduleStatus->setIsEnabled(false, $this->modulesToDisable);
        }
    }

    private function initModulesToDisable(array $modulesToDisable): array
    {
        $result = [];

        foreach (array_unique($modulesToDisable) as $module) {
            if ($this->moduleManager->isEnabled($module)) {
                $result[] = $module;
            }
        }

        return $result;
    }
}
