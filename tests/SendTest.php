<?php

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
        $this->linux = new SerialConnection(
            new SystemFixed('linux'),
            'com1',
            38400,
            'none',
            8,
            1,
            'none'
        );

        $this->macOS = new SerialConnection(
            new SystemFixed('osx'),
            'com1',
            38400,
            'none',
            8,
            1,
            'none'
        );

        $this->windows = new SerialConnection(
            new SystemFixed('windows'),
            'com1',
            38400,
            'none',
            8,
            1,
            'none'
        );

    }

    public function testSend()
    {
        $linuxSender = new Send($this->linux);
        self::assertEquals(0, $this->linux->getDeviceStatus());
    }
}
