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
    public function read(int $count = 0): string
    {
        $this->setupDevice();
        if ($this->serialConnection->getDeviceStatus() !== SERIAL_DEVICE_OPENED) {
            throw new RuntimeException(
                'Device must be opened to read it.'
            );
        }

        return $this->readPort($count);
    }

    private function readPort(int $count): string
    {
        $content = '';
        $i = 0;

        if ($count !== 0) {
            do {
                if ($i > $count) {
                    $content .= fread($this->serialConnection->getDeviceHandle(), ($count - $i));
                } else {
                    $content .= fread($this->serialConnection->getDeviceHandle(), 128);
                }
            } while (($i += 128) === strlen($content));
        } else {
            do {
                $content .= fread($this->serialConnection->getDeviceHandle(), 128);
            } while (($i += 128) === strlen($content));
        }

        return $content;
    }

    private function setupDevice(): void
    {
        $this->serialConnection->connect('r+b');
    }
}
