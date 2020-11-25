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
        parent::setUp();
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

    public function testUnknownStopBits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $illegalLinuxStopBits = 1.5;
        self::assertEquals(
            'Linux do not support: ' . $illegalLinuxStopBits . ' setting.',
            new CreatePort(
                new SystemFixed('linux'),
                'ttyS0',
                38400,
                'none',
                8,
                1.5,
                'none'
            ),
            'Failed detecting a illegal parity setting under Linux.'
        );
    }

    public function testCreatePort(): void
    {
        self::assertInstanceOf(
            CreatePort::class,
            new CreatePort(
                new SystemFixed('linux'),
                'ttyS0',
                38400,
                'none',
                8,
                1,
                'none'
            ),
            'Failed detecting a illegal parity setting under Linux.'
        );
        self::assertClassHasAttribute(
            'baudRate', CreatePort::class,
            'Unable to find baud rate.'
        );
    }

    public function testStopBits()
    {
        self::assertSame(
            '1',
            $this->portSettings->stopBits,
            'Stop bits value incorrect.'
        );

        $decimal = new CreatePort(
            new SystemFixed('osx'),
            'ttyS0',
            38400,
            'none',
            8,
            1.5,
            'none'
        );
        self::assertSame(
            '1.5',
            $decimal->stopBits,
            'Stop bits value incorrect.'
        );

    }
}
