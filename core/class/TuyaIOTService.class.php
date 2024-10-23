<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use tuyapiphp\TuyaApi;
use DateTime;
use DateTimeZone;

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
     * @return string|null
     */
    static public function getToken(): ?string
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
        $token = self::getToken();
        if (!$token) {
            self::logError('No token: Connection not established or Tuya service unavailable');
            return false;
        }

        $tuya = self::getTuyaApi();
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
            self::logInfo('Missing data to add device');
            self::logDebug(json_encode($rawDevice));
            return null;
        }

        self::logInfo('Discover device "' . $rawDevice->name . '" (' . $rawDevice->id . ')');

        // Try to find existing eqLogic
        $eqLogic = TuyaIOT::byLogicalId($rawDevice->id, 'TuyaIOT');

        // Create new eqLogic if not exists
        if (!is_object($eqLogic)) {
            $eqLogic = self::generateTuyaIOT($rawDevice);
            self::logInfo('Create new device');
        } else
        {
            self::logInfo('Update device');
        }

        // Update data
        $eqLogic->setConfiguration('data', $rawDevice);

        // Save
        $eqLogic->save(true);

        // Generate command associated
        if (!self::generateCommands($eqLogic)) {
            return null;
        }

        return $eqLogic;
    }

    /**
     * Generate or update command associated to device
     *
     * @param TuyaIOT $eqLogic
     * @return bool
     */
    static public function generateCommands(TuyaIOT $eqLogic): bool
    {
        $token = self::getToken();
        if (!$token) {
            self::logError('No token: Connection not established or Tuya service unavailable');
            return false;
        }

        // Get detail from device
        $rawDeviceDetail = self::getTuyaApi()->devices($token)->get_specifications($eqLogic->getLogicalId());

        // Check if success
        if (!$rawDeviceDetail->success) {
            self::logDebug('Error while getting device detail: ' . json_encode($rawDeviceDetail));
            return false;
        }
        $rawDeviceDetail = $rawDeviceDetail->result;

        // Reference all commands from eqLogic
        $commands = self::getAndGroupDeviceCommands($eqLogic);

        // And generate commands associated
        foreach ($rawDeviceDetail->status as $rawCommand) {
            $cmd = $commands[$rawCommand->code] ?? null;
            $isNew = $cmd === null;

            $cmd = self::generateTuyaIOTCmd($eqLogic, $rawCommand, $cmd);

            $preLog = $isNew ? 'Create command' : 'Update command';
            self::logInfo(
                $preLog . ' "' . $cmd->getName() . '" (' . $cmd->getTuyaCode() . ') for device "' .
                $eqLogic->getName() . '" (' . $eqLogic->getLogicalId() . ')'
            );

            $cmd->save();
        }

        return true;
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
     * Generate command from raw command or update it if provided
     *
     * @param TuyaIOT $eqLogic
     * @param object $rawCommand
     * @param ?TuyaIOTCmd $cmd Command to update
     * @return TuyaIOTCmd
     */
    static protected function generateTuyaIOTCmd(
        TuyaIOT $eqLogic,
        object $rawCommand,
        ?TuyaIOTCmd $cmd = null
    ): TuyaIOTCmd
    {
        $rawValues = $rawCommand->values ? json_decode($rawCommand->values) : null;
        if (is_null($cmd)) {
            $cmd = new TuyaIOTCmd();
            $cmd->setLogicalId('');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName($rawCommand->code);
            $cmd->setTuyaCode($rawCommand->code);
            $cmd->setType('info');
            $cmd->setIsVisible(1);
            $cmd->setIsHistorized(1);
            // Min/Max
            if ($rawValues->min) {
                $cmd->setConfiguration('minValue', $rawValues->min);
            }
            if ($rawValues->max) {
                $cmd->setConfiguration('maxValue', $rawValues->max);
            }
        }

        switch (strtolower($rawCommand->type)) {
            case 'integer' :
                $cmd->setSubType('numeric');
                break;
            case 'boolean' :
                $cmd->setSubType('binary');
                break;
            default :
                $cmd->setSubType('string');
                break;
        }

        // Retrieve unit
        if ($rawValues->unit) {
            $cmd->setUnite($rawValues->unit);
        }

        return $cmd;
    }


    /**
     * Update all objects and command value (when enabled)
     *
     * @return void
     * @see cron::byClassAndFunction('TuyaIOT', 'updateAll')
     */
    static public function updateAll(): void
    {
        self::logInfo('Start updating all devices values');

        // Get all devices
        $eqLogics = TuyaIOT::byType('TuyaIOT', true);

        foreach ($eqLogics as $eqLogic) {
            // Check if enabled
            if (!$eqLogic->getIsEnable()) {
                continue;
            }

            // Update device
            self::updateDeviceValues($eqLogic);
        }

        self::logInfo('All devices values added!');
    }

    /**
     * Update values for each command of device
     *
     * @param TuyaIOT $eqLogic
     * @return bool
     */
    static public function updateDeviceValues(TuyaIOT $eqLogic): bool
    {
        self::logDebug('Update commands values for device "' . $eqLogic->getName() . '" (' . $eqLogic->getLogicalId() . ')');
        $token = self::getToken();
        if (!$token) {
            self::logError('No token: Connection not established or Tuya service unavailable');
            return false;
        }

        // Get log from device
        $raw = self::getTuyaApi()->devices($token)->get_logs($eqLogic->getLogicalId(), [
            // @see: https://developer.tuya.com/en/docs/cloud/device-management?id=K9g6rfntdz78a#sjlx1
            'type' => '7', // 7 = 'A data point is reported from the device to the cloud.'
            'start_time' => 0, // Default: 7 days ago
            'end_time' => time() * 1000,
            'size' => 100,
        ]);

        // Check if success
        if (!$raw->success) {
            self::logDebug('Error while getting logs from device "' . $eqLogic->getName() . '" (' . $eqLogic->getLogicalId() . '): ' . json_encode($raw));
            return false;
        }
        $raw = $raw->result;

        // Prepare data
        $rawDeviceLogs = self::groupDeviceLog($raw->logs);
        $commands = self::getAndGroupDeviceCommands($eqLogic);

        // Update values
        foreach ($commands as $cmd) {
            self::updateCommandValues($cmd, $rawDeviceLogs[$cmd->getTuyaCode()] ?? []);
        }

        return true;
    }

    /**
     * Update values for specific command
     *
     * @param TuyaIOTCmd $cmd
     * @param array $rawLogs
     * @return bool
     */
    static protected function updateCommandValues(TuyaIOTCmd $cmd, array $rawLogs): bool
    {
        self::logDebug('Update values for command "' . $cmd->getName() . '" (' . $cmd->getTuyaCode() . ')');

        // For each values
        $valueAdded = 0;
        foreach ($rawLogs as $rawLog) {
            $dateTime = new DateTime('@' . floor($rawLog->event_time / 1000), new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone(config::byKey('timezone', 'core', 'UTC')));
            $dateTimeFormatted = $dateTime->format('Y-m-d H:i:s');
            $history = history::byCmdIdDatetime($cmd->getId(), $dateTimeFormatted);

            // If value already exist at this time, skip
            if (is_object($history)) {
                continue;
            }
            // Else, create new value
            else {
                $value = $rawLog->value;
                // For numeric value, divide by 100 to obtain float value
                if ($cmd->getSubType() == 'numeric') {
                    switch ($cmd->getUnite()) {
                        // Except for percent
                        case '%' :
                            break;
                        // Temperature
                        case '℃' :
                        case '°C' :
                        case '℉' :
                        case '°F' :
                        $value /= 10;
                            break;
                        default:
                            $value /= 100;
                            break;
                    }
                }

                self::logDebug('Add new value: "' . $value . '" at "' . $dateTimeFormatted . '" for command "' . $cmd->getName() . '" (' . $cmd->getTuyaCode() . ')' );
                $cmd->event($value, $dateTimeFormatted);
                $valueAdded++;
            }
        }

        if ($valueAdded === 0) {
            self::logDebug('No new value added for command "' . $cmd->getName() . '" (' . $cmd->getTuyaCode() . ')');
        }

        return true;
    }

    static protected function groupDeviceLog(array $rawDeviceLogs): array
    {
        $logs = [];

        // First sort by time ASC
        usort($rawDeviceLogs, function ($a, $b) {
            return $a->event_time - $b->event_time;
        });

        // Then, group by command code
        foreach ($rawDeviceLogs as $rawDeviceLog) {
            $logs[$rawDeviceLog->code][] = $rawDeviceLog;
        }

        return $logs;
    }

    /**
     * Get and group device commands
     *
     * @param TuyaIOT $eqLogic
     * @return array
     */
    static protected function getAndGroupDeviceCommands(TuyaIOT $eqLogic): array
    {
        $commands = [];
        /** @var TuyaIOTCmd $cmd */
        foreach ($eqLogic->getCmd() as $cmd) {
            $commands[$cmd->getTuyaCode()] = $cmd;
        }

        return $commands;
    }

    static public function logDebug($message)
    {
        self::log('DEBUG', $message);
    }

    static public function logError($message)
    {
        self::log('ERROR', $message);
    }

    static public function logInfo($message)
    {
        self::log('INFO', $message);
    }

    static protected function log($level, $message): void
    {
        log::add('TuyaIOT', $level, $message);
    }
}