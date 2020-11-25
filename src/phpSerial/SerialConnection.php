<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use RuntimeException;

define ("SERIAL_DEVICE_NOTSET", 0);
define ("SERIAL_DEVICE_SET", 1);
define ("SERIAL_DEVICE_OPENED", 2);

/**
 * Serial port control class
 */
final class SerialConnection implements GatewayInterface
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
    private const VALID_FLOW_CONTROL = [
        'none'     => 'clocal -crtscts -ixon -ixoff',
        'rts/cts'  => '-clocal crtscts -ixon -ixoff',
        'xon/xoff' => '-clocal -crtscts ixon ixoff'
    ];
    private const VALID_FLOW_CONTROL_WINDOWS = [
        'none'     => 'xon=off octs=off rts=on',
        'rts/cts'  => 'xon=off octs=on rts=hs',
        'xon/xoff' => 'xon=on octs=off rts=on',
    ];
    private $deviceStatus = SERIAL_DEVICE_NOTSET;
    private $windowsDevice;
    private $deviceHandle;
    private $buffer = '';
    private $machine;
    private $execute;
    private $portSettings;

    /**
     * This var says if buffer should be flushed by sendMessage (true) or
     * manually (false)
     *
     * @var bool
     */
    public $autoFlush = true;

    public function __construct(
        SystemInterface $machine,
        ExecuteInterface $execute,
        CreatePort $portSettings
    )
    {
        $this->machine = $machine;
        $this->execute = $execute;
        $this->portSettings = $portSettings;
    }

    public function getDeviceStatus(): int
    {
        return $this->deviceStatus;
    }

    public function getDeviceHandle()
    {
        return $this->deviceHandle;
    }

    public function connect(string $mode)
    {
        $this->setDevice($this->portSettings->device);
        $this->setBaudRate();
        $this->setParity();
        $this->setCharacterLength();
        $this->setStopBits();
        $this->setFlowControl();
        $this->open($mode);
    }

    /**
     * Device set function : used to set the device name/address.
     * -> linux : use the device address, like /dev/ttyS0
     * -> osx : use the device address, like /dev/tty.serial
     * -> windows : use the COMxx device name, like COM1 (can also be used
     *     with linux)
     *
     * @param  string $device the name of the device to be used
     */
    private function setDevice(): void
    {
        $device = $this->portSettings->device;

        if ($this->deviceStatus !== SERIAL_DEVICE_OPENED) {
            if ($this->machine->operatingSystem() === 'linux') {
                $this->setLinuxDevice($device);
            } elseif ($this->machine->operatingSystem() === 'osx') {
                $this->setmacOSDevice($device);
            } elseif ($this->machine->operatingSystem() === "windows") {
                if (preg_match("@^COM(\\d+):?$@i", $device, $matches)
                        and $this->execute->execute(
                            exec("mode " . $device . " xon=on BAUD=9600")
                        ) === 0
                ) {
                    $this->windowsDevice = "COM" . $matches[1];
                    $this->portSettings->device = "\\.com" . $matches[1];
                    $this->deviceStatus = SERIAL_DEVICE_SET;
                }
            }

            throw new RuntimeException(
                'Specified serial port: ' . $device . ' not found.'
            );
        }

        throw new RuntimeException(
            'You must close your device before to set an other.'
        );
    }

    private function setmacOSDevice(string $device)
    {
        if ($this->execute->execute('stty -f ' . $device) !== 0) {
            throw new RuntimeException(
                'Specified macOS serial port: ' . $device . ' not found.'
            );
        }

        $this->deviceStatus = SERIAL_DEVICE_SET;
    }
    private function setLinuxDevice(string $device): void
    {
        if (exec("stty") !== 0) {
            throw new RuntimeException(
                'No stty available, unable setup device: ' , $device
            );
        }

        if ($this->execute->execute("stty -F " . $device) !== 0) {
            throw new RuntimeException(
                'Specified serial port: ' . $device . ' not found.'
            );
        }

        $this->deviceStatus = SERIAL_DEVICE_SET;
    }

    /**
     * Opens the device for reading and/or writing.
     *
     * @param  string $mode Opening mode : same parameter as fopen()
     * @return bool
     */
    private function open($mode = 'r+b'): bool
    {
        if ($this->deviceStatus === SERIAL_DEVICE_OPENED) {
            trigger_error("The device is already opened", E_USER_NOTICE);

            return true;
        }

        if ($this->deviceStatus === SERIAL_DEVICE_NOTSET) {
            trigger_error(
                "The device must be set before to be open",
                E_USER_WARNING
            );

            return false;
        }

        if (!preg_match("@^[raw]\\+?b?$@", $mode)) {
            trigger_error(
                "Invalid opening mode : ".$mode.". Use fopen() modes.",
                E_USER_WARNING
            );

            return false;
        }

        $this->deviceHandle = @fopen($this->portSettings->device, $mode);

        if ($this->deviceHandle !== false) {
            stream_set_blocking($this->deviceHandle, 0);
            $this->deviceStatus = SERIAL_DEVICE_OPENED;

            return true;
        }

        $this->deviceHandle = null;
        trigger_error("Unable to open the device", E_USER_WARNING);

        return false;
    }

    /**
     * Closes the device
     *
     * @return bool
     */
    private function close(): bool
    {
        if ($this->deviceStatus !== SERIAL_DEVICE_OPENED) {
            return true;
        }

        if (fclose($this->deviceHandle)) {
            $this->deviceHandle = null;
            $this->deviceStatus = SERIAL_DEVICE_SET;

            return true;
        }

        trigger_error("Unable to close the device", E_USER_ERROR);
    }

    /**
     * Configure the Baud Rate.
     */
    private function setBaudRate(): void
    {
        $this->deviceStatus('baud rate', $this->portSettings->baudRate);

        if (!$this->machine->operatingSystem() !== 'windows') {
            $result = $this->write($this->portSettings->baudRate);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->windowsDevice . ' BAUD=' . self::VALID_BAUDS[$this->portSettings->baudRate],
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set baud rate: ' . $this->portSettings->baudRate
            );
        }
    }

    private function deviceStatus($message, $value): void
    {
        if ($this->deviceStatus !== SERIAL_DEVICE_SET) {
            throw new RuntimeException(
                'Unable to set ' . $message . ' to ' . $value .
                ' The device is either not set or opened.'
            );
        }
    }

    private function write($value): int
    {
        $command = 'stty -F';
        if ($this->machine->operatingSystem() === 'osx') {
            $command = 'stty -f';
        }

        return $this->execute->execute(
            $command . ' ' . $this->portSettings->device . ' ' . $value,
            $out);
    }

    /**
     * Set parity.
     * Valid modes: odd, even, none.
     */
    private function setParity(): void
    {
        $this->deviceStatus('parity', $this->portSettings->parity);

        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(self::VALID_PARITY[$this->portSettings->parity]);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->windowsDevice . " PARITY=" . $this->portSettings->parity[0],
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set parity to: ' . $this->portSettings->parity
            );
        }
    }

    /**
     * Sets the length of a character.
     */
    private function setCharacterLength(): bool
    {
        if ($this->deviceStatus !== SERIAL_DEVICE_SET) {
            trigger_error("Unable to set length of a character : the device " .
                          "is either not set or opened", E_USER_WARNING);

            return false;
        }

        if ($this->machine->operatingSystem() === "linux") {
            $ret = $this->execute->execute(
                "stty -F " . $this->portSettings->device . " cs" . $this->portSettings->characterLength,
                $out
            );
        } elseif ($this->machine->operatingSystem() === "osx") {
            $ret = $this->execute->execute(
                "stty -f " . $this->portSettings->device . " cs" . $this->portSettings->characterLength,
                $out
            );
        } else {
            $ret = $this->execute->execute(
                "mode " . $this->windowsDevice . " DATA=" . $this->portSettings->characterLength,
                $out
            );
        }

        if ($ret === 0) {
            return true;
        }

        trigger_error(
            "Unable to set character length : " .$out[1],
            E_USER_WARNING
        );

        return false;
    }

    /**
     * Sets the length of stop bits.
     *
     * It must be either 1, 1.5 or 2.
     * 1.5 is not supported under some windows computers and Linux.
     */
    private function setStopBits(): void
    {
        $this->deviceStatus('stop bits', $this->portSettings->stopBits);
        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(' ' . (($this->portSettings->stopBits === 1) ? "-" : "") . 'cstopb');
        } else {
            $result = $this->execute->execute(
                "mode " . $this->windowsDevice . " STOP=" . $this->portSettings->stopBits,
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set stop bits to: ' . $this->portSettings->stopBits
            );
        }
    }

    /**
     * Configures the flow control.
     *
     * Set the flow control mode. Available modes :
     *  -> "none" : no flow control
     *  -> "rts/cts" : use RTS/CTS handshaking
     *  -> "xon/xoff" : use XON/XOFF protocol
     */
    private function setFlowControl(): void
    {
        $this->deviceStatus('flow control', $this->portSettings->flowControl);

        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(self::VALID_FLOW_CONTROL[$this->portSettings->flowControl]);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->windowsDevice . " " . self::VALID_FLOW_CONTROL_WINDOWS[$this->portSettings->flowControl],
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set flow control.'
            );
        }
    }

    /**
     * Sets a setserial parameter (man setserial)
     * NO MORE USEFUL !
     * 	-> No longer supported
     * 	-> Only use it if you need it
     *
     * @param  string $param parameter name
     * @param  string $arg   parameter value
     *
     * @deprecated No longer supported.
     *
     * @return bool
     */
    private function setSetSerialFlag(string $param, string $arg = ''): bool
    {
        if (!$this->_ckOpened()) {
            return false;
        }

        $return = exec(
            "setserial " . $this->portSettings->device . " " . $param . " " . $arg . " 2>&1"
        );

        if ($return[0] === "I") {
            trigger_error("setserial: Invalid flag", E_USER_WARNING);

            return false;
        }

        if ($return[0] === "/") {
            trigger_error("setserial: Error with device file", E_USER_WARNING);

            return false;
        }

        return true;
    }

    /**
     * Flushes the output buffer.
     */
    public function flush(): void
    {
        if (!$this->_ckOpened()) {
            return;
        }

        if (fwrite($this->deviceHandle, $this->buffer) !== false) {
            $this->buffer = '';
        }
    }

    private function _ckOpened(): bool
    {
        if ($this->deviceStatus !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened", E_USER_WARNING);

            return false;
        }

        return true;
    }

    public function sendMessage(string $message): void
    {
        $this->buffer = $message;
    }
}
