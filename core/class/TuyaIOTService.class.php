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
     * @return void
     */
    static public function discoverDevices(): void
    {
        // Nothing to do for now
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