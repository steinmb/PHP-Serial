<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use RuntimeException;

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
    public function readPort(int $count = 0): string
    {
        $this->setupDevice();
        if ($this->serialConnection->_dState !== SERIAL_DEVICE_OPENED) {
            throw new RuntimeException(
                'Device must be opened to read it.'
            );
        }

        return $this->read($count);
    }

    private function read(int $count): string
    {
        $content = '';
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

    private function setupDevice(): void
    {
        $this->serialConnection->connect('r+b');
    }
}