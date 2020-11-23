<?php declare(strict_types=1);

namespace steinmb\phpSerial;

final class Receive implements ReceiveInterface
{
    private $serialConnection;

    public function __construct(SerialConnection $serialConnection)
    {
        $this->serialConnection = $serialConnection;
    }

    /**
     * Reads the port until no new data are available, then return the content.
     *
     * @param int $count
     *  Number of characters to be read (will stop before
     *  if less characters are in the buffer).
     *
     * @return string
     */
    public function readPort($count = 0)
    {
        if ($this->serialConnection->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened to read it", E_USER_WARNING);
            return false;
        }

        if ($this->serialConnection->_os === "linux" || $this->serialConnection->_os === "osx") {
            return $this->read();
        }

        if ($this->serialConnection->_os === "windows") {
            return $this->read();
        }

        return false;
    }

    private function read(): string
    {
        $content = '';
        $count = 0;
        $i = 0;

        if ($count !== 0) {
            do {
                if ($i > $count) {
                    $content .= fread($this->serialConnection->_dHandle, ($count - $i));
                } else {
                    $content .= fread($this->serialConnection->_dHandle, 128);
                }
            } while (($i += 128) === strlen($content));
        } else {
            do {
                $content .= fread($this->serialConnection->_dHandle, 128);
            } while (($i += 128) === strlen($content));
        }

        return $content;
    }
}