<?php declare(strict_types=1);

include_once __DIR__ . '/vendor/autoload.php';

use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\System;

$clientSystem = new System();
$com1 = new SerialConnection(
    new System(),
    'com1',
    38400,
    'none',
    8,
    1,
    'none'
);

$com2 = $com1->setDevice('com2');
