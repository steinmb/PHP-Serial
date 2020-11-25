<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use InvalidArgumentException;

final class CreatePort
{
    private const VALID_BAUDS = [
        110    => 11,
        150    => 15,
        300    => 30,
        600    => 60,
        1200   => 12,
        2400   => 24,
        4800   => 48,
        9600   => 96,
        19200  => 19,
        38400  => 38400,
        57600  => 57600,
        115200 => 115200,
    ];
    private const VALID_PARITY = [
        'none' => '-parenb',
        'odd'  => 'parenb parodd',
        'even' => 'parenb -parodd',
    ];
    private const VALID_STOP_BIT = [1.0, 1.5, 2.0,];
    public $device;
    public $baudRate;
    public $parity;
    public $characterLength;
    public $stopBits;
    public $flowControl;

    public function __construct(
        SystemInterface $machine,
        string $device,
        int $baudRate,
        string $parity,
        int $characterLength,
        float $stopBits,
        string $flowControl
    )
    {
        $this->machine = $machine;
        $this->device = $device;

        if (!isset(self::VALID_BAUDS[$baudRate])) {
            throw InvalidSerialException::invalidBaudRate(
                self::VALID_BAUDS, $baudRate
            );
        }
        $this->baudRate = $baudRate;

        if (!isset(self::VALID_PARITY[$parity])) {
            throw new InvalidArgumentException(
                'Unknown parity mode: ' . $parity
            );
        }
        $this->parity = $parity;
        $this->characterLength = $characterLength;

        if (!in_array($stopBits, self::VALID_STOP_BIT, true)) {
            throw new InvalidArgumentException(
                'Invalid stop bit value: ' . $stopBits
            );
        }

        if ($stopBits === 1.5 && $this->machine->operatingSystem() === 'linux') {
            throw new InvalidArgumentException(
                'Linux do not support: ' . $stopBits . ' setting.'
            );

        }
        $this->stopBits = $stopBits;


        if ($flowControl !== 'none' && $flowControl !== 'rts/cts' && $flowControl !== 'xon/xoff') {
            throw new InvalidArgumentException(
                'Invalid flow control mode specified: ' . $flowControl
            );
        }

        $this->flowControl = $flowControl;

    }
}