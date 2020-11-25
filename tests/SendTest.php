<?php

use steinmb\phpSerial\CreatePort;
use steinmb\phpSerial\ExecuteNull;
use steinmb\phpSerial\Send;
use PHPUnit\Framework\TestCase;
use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\SystemFixed;

final class SendTest extends TestCase
{
    private $linux;
    private $macOS;
    private $windows;

    public function setup(): void
    {
        $portSettings = new CreatePort(
            'com1',
            38400,
            'none',
            8,
            1,
            'none'
        );
        $this->linux = new SerialConnection(
            new SystemFixed('linux'),
            new ExecuteNull(),
            $portSettings
        );

        $this->macOS = new SerialConnection(
            new SystemFixed('osx'),
            new ExecuteNull(),
            $portSettings
        );

        $this->windows = new SerialConnection(
            new SystemFixed('windows'),
            new ExecuteNull(),
            $portSettings
        );
    }

    public function testSend(): void
    {
        $linuxSender = new Send($this->linux);
        self::assertEquals(0, $this->linux->getDeviceStatus());
    }
}
