<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use RuntimeException;

define ("SERIAL_DEVICE_NOTSET", 0);
define ("SERIAL_DEVICE_SET", 1);
define ("SERIAL_DEVICE_OPENED", 2);

/**
 * Serial port control class
 */
final class SerialConnection
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
    public $_device;
    public $baudRate;
    public $parity;
    public $characterLength;
    public $stopBits;
    public $flowControl;
    public $_winDevice;
    public $_dHandle;
    public $_dState = SERIAL_DEVICE_NOTSET;
    public $_buffer = "";
    public $_os;
    private $operatingSystem;

    /**
     * This var says if buffer should be flushed by sendMessage (true) or
     * manually (false)
     *
     * @var bool
     */
    public $autoFlush = true;

    public function __construct(
        System $operatingSystem,
        string $device,
        int $baudRate,
        string $parity,
        int $characterLength,
        int $stopBits,
        string $flowControl
    )
    {
        $this->operatingSystem = $operatingSystem;
        $this->_device = $device;

        if (!isset(self::VALID_BAUDS[$baudRate])) {
            throw InvalidSerialException::invalidBaudRate(
                self::VALID_BAUDS, $baudRate
            );
        }
        $this->baudRate = $baudRate;

        if (!isset(self::VALID_PARITY[$parity])) {
            throw new \InvalidArgumentException(
                'Unknown parity mode: ' . $parity
            );
        }
        $this->parity = $parity;
        $this->characterLength = $characterLength;
        $this->stopBits = $stopBits;
        $this->flowControl = $flowControl;
    }

    public function changeDevice(string $device): SerialConnection
    {
        $new_object = clone $this;
        $new_object->setDevice($device);

        return $new_object;
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
    public function setDevice(string $device): void
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            if ($this->_os === "linux") {
                if (preg_match("@^COM(\\d+):?$@i", $device, $matches)) {
                    $device = "/dev/ttyS" . ($matches[1] - 1);
                }

                if ($this->_exec("stty -F " . $device) === 0) {
                    $this->_device = $device;
                    $this->_dState = SERIAL_DEVICE_SET;
                }
            } elseif ($this->_os === "osx") {
                if ($this->_exec("stty -f " . $device) === 0) {
                    $this->_device = $device;
                    $this->_dState = SERIAL_DEVICE_SET;
                }
            } elseif ($this->_os === "windows") {
                if (preg_match("@^COM(\\d+):?$@i", $device, $matches)
                        and $this->_exec(
                            exec("mode " . $device . " xon=on BAUD=9600")
                        ) === 0
                ) {
                    $this->_winDevice = "COM" . $matches[1];
                    $this->_device = "\\.com" . $matches[1];
                    $this->_dState = SERIAL_DEVICE_SET;
                }
            }

            throw new RuntimeException(
                'Specified serial port is not valid.'
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
    public function open($mode = "r+b"): bool
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

        $this->_dHandle = @fopen($this->_device, $mode);

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
    public function close(): bool
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
    public function setBaudRate(int $rate): void
    {
        $this->deviceStatus('baud rate', $rate);

        if (!$this->operatingSystem->_os !== 'windows') {
            $result = $this->write($rate);
        } else {
            $result = $this->_exec(
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
        return $this->_exec(
            "stty -F " . $this->_device . " " . $value,
            $out);
    }

    /**
     * Set parity.
     * Valid modes: odd, even, none.
     *
     * @param  string $parity
     */
    public function setParity(string $parity): void
    {
        $this->deviceStatus('parity', $parity);

        if ($this->operatingSystem->_os !== 'windows') {
            $result = $this->write($args[$parity]);
        } else {
            $result = $this->_exec(
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
    public function setCharacterLength(int $int): bool
    {
        if ($this->_dState !== SERIAL_DEVICE_SET) {
            trigger_error("Unable to set length of a character : the device " .
                          "is either not set or opened", E_USER_WARNING);

            return false;
        }

        $int = (int) $int;
        if ($int < 5) {
            $int = 5;
        } elseif ($int > 8) {
            $int = 8;
        }

        if ($this->_os === "linux") {
            $ret = $this->_exec(
                "stty -F " . $this->_device . " cs" . $int,
                $out
            );
        } elseif ($this->_os === "osx") {
            $ret = $this->_exec(
                "stty -f " . $this->_device . " cs" . $int,
                $out
            );
        } else {
            $ret = $this->_exec(
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
     * It must be either 1, 1.5 or 2. 1.5 is not supported
     * under linux and on some computers.
     *
     * @return bool
     */
    public function setStopBits($length): bool
    {
        $this->deviceStatus('stop bits', $length);
        if ($length !== 1
                and $length !== 2
                and $length !== 1.5
                and !($length === 1.5 and $this->_os === 'linux')
        ) {
            trigger_error(
                "Specified stop bit length is invalid",
                E_USER_WARNING
            );

            return false;
        }

        if ($this->_os === "linux") {
            $ret = $this->_exec(
                "stty -F " . $this->_device . " " .
                    (($length === 1) ? "-" : "") . 'cstopb',
                $out
            );
        } elseif ($this->_os === 'osx') {
            $ret = $this->_exec(
                "stty -f " . $this->_device . " " .
                    (($length === 1) ? "-" : "") . "cstopb",
                $out
            );
        } else {
            $ret = $this->_exec(
                "mode " . $this->_winDevice . " STOP=" . $length,
                $out
            );
        }

        if ($ret === 0) {
            return true;
        }

        trigger_error(
            "Unable to set stop bit length : " . $out[1],
            E_USER_WARNING
        );

        return false;
    }

    /**
     * Configures the flow control
     *
     * @param  string $mode Set the flow control mode. Available modes :
     *                      -> "none" : no flow control
     *                      -> "rts/cts" : use RTS/CTS handshaking
     *                      -> "xon/xoff" : use XON/XOFF protocol
     * @return bool
     */
    public function setFlowControl($mode): ?bool
    {
        $this->deviceStatus('flow control', $mode);

        $linuxModes = array(
            "none"     => "clocal -crtscts -ixon -ixoff",
            "rts/cts"  => "-clocal crtscts -ixon -ixoff",
            "xon/xoff" => "-clocal -crtscts ixon ixoff"
        );
        $windowsModes = array(
            "none"     => "xon=off octs=off rts=on",
            "rts/cts"  => "xon=off octs=on rts=hs",
            "xon/xoff" => "xon=on octs=off rts=on",
        );

        if ($mode !== "none" and $mode !== "rts/cts" and $mode !== "xon/xoff") {
            trigger_error("Invalid flow control mode specified", E_USER_ERROR);
        }

        if ($this->_os === "linux") {
            $ret = $this->_exec(
                "stty -F " . $this->_device . " " . $linuxModes[$mode],
                $out
            );
        } elseif ($this->_os === "osx") {
            $ret = $this->_exec(
                "stty -f " . $this->_device . " " . $linuxModes[$mode],
                $out
            );
        } else {
            $ret = $this->_exec(
                "mode " . $this->_winDevice . " " . $windowsModes[$mode],
                $out
            );
        }

        if ($ret === 0) {
            return true;
        }

        trigger_error(
            "Unable to set flow control : " . $out[1],
            E_USER_ERROR
        );
    }

    /**
     * Sets a setserial parameter (cf man setserial)
     * NO MORE USEFUL !
     * 	-> No longer supported
     * 	-> Only use it if you need it
     *
     * @param  string $param parameter name
     * @param  string $arg   parameter value
     * @return bool
     */
    public function setSetSerialFlag($param, $arg = "")
    {
        if (!$this->_ckOpened()) {
            return false;
        }

        $return = exec(
            "setserial " . $this->_device . " " . $param . " " . $arg . " 2>&1"
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
     * Flushes the output buffer
     * Renamed from flush for osx compat. issues
     *
     * @return bool
     */
    public function flush()
    {
        if (!$this->_ckOpened()) {
            return false;
        }

        if (fwrite($this->_dHandle, $this->_buffer) !== false) {
            $this->_buffer = "";

            return true;
        }

        $this->_buffer = "";
        trigger_error("Error while sending message", E_USER_WARNING);

        return false;
    }

    public function _ckOpened()
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened", E_USER_WARNING);

            return false;
        }

        return true;
    }

    public function _ckClosed(): bool
    {
        if ($this->_dState === SERIAL_DEVICE_OPENED) {
            trigger_error('Device must be closed', E_USER_WARNING);
            return false;
        }

        return true;
    }

    private function _exec($cmd, &$out = null): int
    {
        $desc = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes);
        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $retVal = proc_close($proc);

        if (func_num_args() === 2) {
            $out = array($ret, $err);
        }

        return $retVal;
    }

}
