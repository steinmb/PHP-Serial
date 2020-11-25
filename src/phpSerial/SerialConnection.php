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
    private $baudRate;
    private $parity;
    private $characterLength;
    private $stopBits;
    private $flowControl;
    private $_winDevice;
    private $_dHandle;
    private $_dState = SERIAL_DEVICE_NOTSET;
    private $_buffer = '';
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
        return $this->_dState;
    }

    public function getDeviceHandle()
    {
        return $this->_dHandle;
    }

    public function changeDevice(string $device): SerialConnection
    {
        $new_object = clone $this;
        $new_object->setDevice($device);

        return $new_object;
    }

    public function connect(string $mode)
    {
        $this->setDevice($this->portSettings->device);
        $this->setBaudRate($this->portSettings->baudRate);
        $this->setParity($this->portSettings->parity);
        $this->setCharacterLength($this->portSettings->characterLength);
        $this->setStopBits($this->portSettings->stopBits);
        $this->setFlowControl($this->portSettings->flowControl);
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
    private function setDevice(string $device): void
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            if ($this->machine->operatingSystem() === 'linux') {
                if (exec("stty") !== 0) {
                    throw new RuntimeException(
                        'No stty available, unable setup device: ' , $device
                    );
                }

                if (preg_match("@^COM(\\d+):?$@i", $device, $matches)) {
                    $device = "/dev/ttyS" . ($matches[1] - 1);
                }

                if ($this->execute->execute("stty -F " . $device) === 0) {
                    $this->portSettings->device;
                    $this->portSettings->device = $device;
                    $this->_dState = SERIAL_DEVICE_SET;
                }
            } elseif ($this->machine->operatingSystem() === 'osx') {
                if ($this->execute->execute("stty -f " . $device) === 0) {
                    $this->portSettings->device = $device;
                    $this->_dState = SERIAL_DEVICE_SET;
                }
            } elseif ($this->machine->operatingSystem() === "windows") {
                if (preg_match("@^COM(\\d+):?$@i", $device, $matches)
                        and $this->execute->execute(
                            exec("mode " . $device . " xon=on BAUD=9600")
                        ) === 0
                ) {
                    $this->_winDevice = "COM" . $matches[1];
                    $this->portSettings->device = "\\.com" . $matches[1];
                    $this->_dState = SERIAL_DEVICE_SET;
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

    /**
     * Opens the device for reading and/or writing.
     *
     * @param  string $mode Opening mode : same parameter as fopen()
     * @return bool
     */
    private function open($mode = 'r+b'): bool
    {
        if ($this->_dState === SERIAL_DEVICE_OPENED) {
            trigger_error("The device is already opened", E_USER_NOTICE);

            return true;
        }

        if ($this->_dState === SERIAL_DEVICE_NOTSET) {
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

        $this->_dHandle = @fopen($this->portSettings->device, $mode);

        if ($this->_dHandle !== false) {
            stream_set_blocking($this->_dHandle, 0);
            $this->_dState = SERIAL_DEVICE_OPENED;

            return true;
        }

        $this->_dHandle = null;
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
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            return true;
        }

        if (fclose($this->_dHandle)) {
            $this->_dHandle = null;
            $this->_dState = SERIAL_DEVICE_SET;

            return true;
        }

        trigger_error("Unable to close the device", E_USER_ERROR);
    }

    /**
     * Configure the Baud Rate.
     *
     * @param int $rate
     *  The rate to set the port in.
     */
    private function setBaudRate(int $rate): void
    {
        $this->deviceStatus('baud rate', $rate);

        if (!$this->machine->operatingSystem() !== 'windows') {
            $result = $this->write($rate);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->_winDevice . ' BAUD=' . self::VALID_BAUDS[$rate],
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set baud rate: ' . $rate
            );
        }
    }

    private function deviceStatus($message, $value): void
    {
        if ($this->_dState !== SERIAL_DEVICE_SET) {
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
     *
     * @param  string $parity
     */
    private function setParity(string $parity): void
    {
        $this->deviceStatus('parity', $parity);

        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(self::VALID_PARITY[$parity]);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->_winDevice . " PARITY=" . $parity[0],
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set parity to: ' . $parity
            );
        }
    }

    /**
     * Sets the length of a character.
     *
     * @param  int  $int length of a character (5 <= length <= 8)
     *
     * @return bool
     */
    private function setCharacterLength(int $int): bool
    {
        if ($this->_dState !== SERIAL_DEVICE_SET) {
            trigger_error("Unable to set length of a character : the device " .
                          "is either not set or opened", E_USER_WARNING);

            return false;
        }

        if ($int < 5) {
            $int = 5;
        } elseif ($int > 8) {
            $int = 8;
        }

        if ($this->machine->operatingSystem() === "linux") {
            $ret = $this->execute->execute(
                "stty -F " . $this->portSettings->device . " cs" . $int,
                $out
            );
        } elseif ($this->machine->operatingSystem() === "osx") {
            $ret = $this->execute->execute(
                "stty -f " . $this->portSettings->device . " cs" . $int,
                $out
            );
        } else {
            $ret = $this->execute->execute(
                "mode " . $this->_winDevice . " DATA=" . $int,
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
     * @param  float $length the length of a stop bit.
     * It must be either 1, 1.5 or 2.
     * 1.5 is not supported under some windows computers and Linux.
     */
    private function setStopBits(float $length): void
    {
        $this->deviceStatus('stop bits', $length);
        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(' ' . (($length === 1) ? "-" : "") . 'cstopb');
        } else {
            $result = $this->execute->execute(
                "mode " . $this->_winDevice . " STOP=" . $length,
                $out
            );
        }

        if ($result !== 0) {
            throw new RuntimeException(
                'Unable to set stop bits to: ' . $length
            );
        }
    }

    /**
     * Configures the flow control.
     *
     * @param  string $mode
     * Set the flow control mode. Available modes :
     *  -> "none" : no flow control
     *  -> "rts/cts" : use RTS/CTS handshaking
     *  -> "xon/xoff" : use XON/XOFF protocol
     */
    private function setFlowControl(string $mode): void
    {
        $this->deviceStatus('flow control', $mode);

        if ($this->machine->operatingSystem() !== 'windows') {
            $result = $this->write(self::VALID_FLOW_CONTROL[$mode]);
        } else {
            $result = $this->execute->execute(
                "mode " . $this->_winDevice . " " . self::VALID_FLOW_CONTROL_WINDOWS[$mode],
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

        if (fwrite($this->_dHandle, $this->_buffer) !== false) {
            $this->_buffer = '';
        }
    }

    private function _ckOpened(): bool
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened", E_USER_WARNING);

            return false;
        }

        return true;
    }

    private function _ckClosed(): bool
    {
        if ($this->_dState === SERIAL_DEVICE_OPENED) {
            trigger_error('Device must be closed', E_USER_WARNING);
            return false;
        }

        return true;
    }

    public function sendMessage(string $message): void
    {
        $this->_buffer = $message;
    }
}
