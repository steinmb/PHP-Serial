<?php declare(strict_types=1);

include_once __DIR__ . '/vendor/autoload.php';

use steinmb\phpSerial\System;

$clientSystem = new System();
$serial = new \steinmb\phpSerial\SerialConnection(
    new System(),
    'com1',
    38400,
    'none',
    8,
    1,
    'none'
);
