<?php declare(strict_types=1);

include_once __DIR__ . '/vendor/autoload.php';

use steinmb\phpSerial\CreatePort;
use steinmb\phpSerial\ExecuteNull;
use steinmb\phpSerial\Receive;
use steinmb\phpSerial\Send;
use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\System;
use steinmb\phpSerial\SystemFixed;

$portSettings = new CreatePort(
    new SystemFixed('linux'),
    'ttyS0',
    38400,
    'none',
    8,
    1,
    'none'
);
$port1 = new SerialConnection(
    new System(),
    new ExecuteNull(),
    $portSettings
);

$senderService = new Send($port1);
$receiveService = new Receive($port1);

$senderService->send('Foo bar');
$receiveService->read();
