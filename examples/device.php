<?php

require __DIR__ . '/../vendor/autoload.php';
require( 'config.php' );

// UID
$app_id = 'eu17145504419526G654';

// Panneaux solaires
$device_id = 'bfa593f1451c0c56e2ro8x';

$tuya = new \tuyapiphp\TuyaApi( $config );

// Get a token
$token = $tuya->token->get_new( )->result->access_token;

// OK
// Get list of devices connected with android app
$devices = $tuya->devices( $token )->get_app_list( $app_id );
//var_dump($devices);
//exit();

// OK
$deviceResult = $tuya->devices( $token )->get_details( $device_id );
$device = $deviceResult->result;
//var_dump($device);
$commandsCode = [];
foreach ($device->status as $status) {
    $commandsCode[] = $status->code;
}

// Commands detail (with unit ?)
$deviceResult = $tuya->devices( $token )->get_specifications( $device_id );
$device = $deviceResult;
//var_dump($device);
//exit();

/**
 * Get device logs
 */
$types = '7';
$timeDuration = 3600 * 24 * 7; // 7 days
// First log to obtain 'start_row_key'
$logResult = $tuya->devices( $token )->get_logs( $device_id, [
    'type' => $types,
    'start_time' => 0, //(time() - $timeDuration) * 1000, // Default: 7 days ago
    'end_time' => time() * 1000,
    'size' => 100,
//    'codes' => 'day_energy,out_power,energy',
//    'codes' => 'day_energy',
]);
var_dump($logResult);
/*if ($logResult->result->has_next) {
    // Second log to get data
    $logResult = $tuya->devices( $token )->get_logs( $device_id, [
        'type' => $types,
        'start_time' => (time() - $timeDuration) * 1000,
        'end_time' => time() * 1000,
        'size' => 100,
        'start_row_key' => $logResult->result->next_row_key,
    ]);
    var_dump($logResult);
}*/

/*
// Set device name
$tuya->devices( $token )->put_name( $device_id , [ 'name' => 'FAN' ] );

// Send command to device
$payload = [ 'code' => 'switch_1' , 'value' => false ];
$tuya->devices( $token )->post_commands( $device_id , [ 'commands' => [ $payload ] ] );
*/