<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/TuyaIOTService.class.php';

class TuyaIOT extends eqLogic
{
    /**
     * Generate or update commands from device
     */
    public function generateCommand(TuyaIOT $eqLogic): bool
    {
        TuyaIOTService::logInfo('Generate commands for device "' . $eqLogic->getName() . '" (' . $eqLogic->getLogicalId() . ')');

        return TuyaIOTService::generateCommands($eqLogic);
    }

    /**
     * Update all objects and command value (when enabled)
     *
     * @return void
     * @see cron::byClassAndFunction('TuyaIOT', 'updateAll')
     */
    static public function updateAll(): void
    {
        TuyaIOTService::updateAll();
    }
}

class TuyaIOTCmd extends cmd
{
    public function getTuyaCode(): string
    {
        return $this->getConfiguration('tuyaCode');
    }

    public function setTuyaCode(string $code): void
    {
        $this->setConfiguration('tuyaCode', $code);
    }
}