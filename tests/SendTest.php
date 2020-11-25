<?php declare(strict_types=1);

use steinmb\phpSerial\CreatePort;
use steinmb\phpSerial\ExecuteNull;
use steinmb\phpSerial\Send;
use PHPUnit\Framework\TestCase;
use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\SystemFixed;

final class SendTest extends TestCase
{
    private $portSettings;
    private $linux;
    private $macOS;
    private $windows;

    public function setup(): void
    {
        $this->portSettings = new CreatePort(
            new SystemFixed('linux'),
            'ttyS0',
            38400,
            'none',
            8,
            1,
            'none'
        );
        $this->linux = new SerialConnection(
            new SystemFixed('linux'),
            new ExecuteNull(),
            $this->portSettings
        );

        $this->macOS = new SerialConnection(
            new SystemFixed('osx'),
            new ExecuteNull(),
            $this->portSettings
        );

        $this->windows = new SerialConnection(
            new SystemFixed('windows'),
            new ExecuteNull(),
            $this->portSettings
        );
    }

    public function testSend(): void
    {
        $linuxSender = new Send($this->linux);
        self::assertEquals(0, $this->linux->getDeviceStatus());
    }

}
