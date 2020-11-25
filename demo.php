<?php declare(strict_types=1);

include_once __DIR__ . '/vendor/autoload.php';

use steinmb\phpSerial\ExecuteNull;
use steinmb\phpSerial\Receive;
use steinmb\phpSerial\Send;
use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\System;

$clientSystem = new System();
$port1 = new SerialConnection(
    new System(),
    new ExecuteNull(),
    'com1',
    38400,
    'none',
    8,
    1,
    'none'
);

$senderService = new Send($port1);
$receiveService = new Receive($port1);

$senderService->send('Foo bar');
$receiveService->read();
