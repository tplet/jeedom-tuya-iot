<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/TuyaIOTService.class.php';

class TuyaIOT extends eqLogic
{


    /**
     * Recreate command from device
     */
    public function generateCommand()
    {
        /*
        $session = TuyaIOTService::getTuyaApi();
        $device = DeviceFactory::createDeviceFromId($session, $this->getLogicalId(), $this->getConfiguration('tuyaType'), $this->getConfiguration('tuyaName'));
        $device->setData( $this->getConfiguration('tuyaData') );
        $smartlifeDevice = new SmartLifeDevice($device);

        SmartLifeLog::begin('RECREATE CMD');
        SmartLifeLog::info('RECREATE CMD', $device, 'Rafraichissement des commandes');

        $configCmdDevice = new SmartLifeConfig($device);
        foreach ($configCmdDevice->getCommands() as $command) {
            $this->addCommand($command, $device, true);
        }

        SmartLifeLog::end('RECREATE CMD');
        */
    }
}