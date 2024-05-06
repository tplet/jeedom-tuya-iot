<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use tuyapiphp\TuyaApi;
use Monolog\Logger;

class TuyaIOTService
{
    static private ?TuyaApi $instance = null;
    static private ?string $token = null;

    /**
     * Get available base url
     *
     * @return string[]
     */
    static public function getBaseUrlAvailable(): array
    {
        return [
            'https://openapi.tuyacn.com' => 'China Data Center',
            'https://openapi.tuyaus.com' => 'Western America Data Center',
            'https://openapi-ueaz.tuyaus.com' => 'Eastern America Data Center',
            'https://openapi.tuyaeu.com' => 'Central Europe Data Center',
            'https://openapi-weaz.tuyaeu.com' => 'Western Europe Data Center',
            'https://openapi.tuyain.com' => 'India Data Center',
        ];
    }

    /**
     * Get TuyaApi instance
     *
     * @return TuyaApi
     */
    static public function getTuyaApi(): TuyaApi
    {
        if (self::$instance === null) {
            self::$instance = new TuyaApi(self::getConfig());
        }
        return self::$instance;
    }

    static public function getConfig(): array
    {
        return [
            'accessKey' => config::byKey('accessKey', 'TuyaIOT'),
            'secretKey' => config::byKey('secretKey', 'TuyaIOT'),
            'baseUrl' => config::byKey('baseUrl', 'TuyaIOT'),
            'uid' => config::byKey('uid', 'TuyaIOT'),
//            'debug' => log::getLogLevel('TuyaIOT') == Logger::DEBUG,
        ];
    }

    /**
     * Get Account UID
     *
     * @return string
     */
    static protected function getUid(): string
    {
        return config::byKey('uid', 'TuyaIOT');
    }

    /**
     * Get token
     *
     * @return string
     */
    static public function getToken(): string
    {
        if (self::$token === null) {
            self::$token = self::getTuyaApi()->token->get_new()->result->access_token;
        }
        return self::$token;
    }

    static public function checkConnection(): bool
    {
        try {
            $result = self::getTuyaApi()->token->get_new();

            return $result && isset($result->success) && $result->success;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Discover devices when dedicated button click
     *
     * @return bool|int
     */
    static public function discoverDevices()
    {
        $tuya = self::getTuyaApi();
        $token = self::getToken();

        $rawDevices = $tuya->devices( $token )->get_app_list( self::getUid() );

        // Check if success
        if (!$rawDevices->success) {
            return false;
        }

        // For each, create or update it
        $devices = [];
        foreach ($rawDevices->result as $rawDevice) {
            $devices[] = self::discoverDevice($rawDevice);
        }

        return count($devices);
    }

    /**
     * Discover device from raw device
     * Create or update eqLogic
     *
     * @param object $rawDevice
     * @return ?TuyaIOT
     */
    static protected function discoverDevice(object $rawDevice): ?TuyaIOT
    {
        // Check if data exists
        if (empty($rawDevice) || empty($rawDevice->id) || empty($rawDevice->name)) {
            self::log(Logger::INFO, 'Missing data to add device');
            self::log(Logger::DEBUG, json_encode($rawDevice));
            return null;
        }

        self::log(Logger::INFO, 'Discover device ' . $rawDevice->name . ' (' . $rawDevice->id . ')');

        // Try to find existing eqLogic
        $eqLogic = TuyaIOT::byLogicalId($rawDevice->id, 'TuyaIOT');

        // Create new eqLogic if not exists
        $isNew = !is_object($eqLogic);
        if ($isNew) {
            $eqLogic = self::generateTuyaIOT($rawDevice);
            self::log(Logger::INFO, 'New device, create it');
        }

        // Update data
        $eqLogic->setConfiguration('data', $rawDevice);

        // Save
        $eqLogic->save(true);

        // Generate commands if new device
        if ($isNew) {
            foreach ($rawDevice->status as $rawCommad) {
                $cmd = self::generateCommands($eqLogic, $rawCommad);
                $cmd->save();
                self::log(Logger::INFO, 'New command created for this device: ' . $cmd->getLogicalId());
            }
        }

        return $eqLogic;
    }

    /**
     * Generate TuyaIOT from raw device
     *
     * @param object $rawDevice
     * @return TuyaIOT
     */
    static protected function generateTuyaIOT(object $rawDevice): TuyaIOT
    {
        $eqLogic = new TuyaIOT();
        $eqLogic->setEqType_name('TuyaIOT');
        $eqLogic->setLogicalId($rawDevice->id);
        $eqLogic->setName($rawDevice->name);
        $eqLogic->setIsVisible(0);
        $eqLogic->setIsEnable(config::byKey('autoenable', 'TuyaIOT') ? 1 : 0);

        return $eqLogic;
    }

    /**
     * Generate command from raw command if not already exists
     *
     * @param TuyaIOT $eqLogic
     * @param object $rawCommand
     * @return cmd
     */
    static protected function generateCommands(TuyaIOT $eqLogic, object $rawCommand): cmd
    {
        $cmd = $eqLogic->getCmd(null, $rawCommand->code);
        if (!is_object($cmd)) {
            $cmd = new TuyaIOTCmd();
            $cmd->setLogicalId('');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName($rawCommand->code);
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsVisible(1);
            $cmd->setIsHistorized(1);
        }

        return $cmd;
    }

    static protected function log($level, $message): void
    {
        log::add('TuyaIOT', $level, $message);
    }

    /**
     * Update all objects and command value (when enabled)
     *
     * @return void
     */
    static public function updateAll(): void
    {
        // Nothing to do for now
    }
}