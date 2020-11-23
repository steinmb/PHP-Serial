<?php declare(strict_types=1);

namespace steinmb\phpSerial;

final class Send implements SendInterface
{
    /**
     * @var SerialConnection
     */
    private $serialConnection;

    public function __construct(SerialConnection $serialConnection)
    {
        $this->serialConnection = $serialConnection;
    }

    /**
     * Sends a string to the device
     *
     * @param $message
     * @param float $waitForReply
     *   time to wait for the reply (in seconds).
     */
    public function send($message, $waitForReply = 0.1): void
    {
        $this->setupDevice();
        $this->serialConnection->_buffer .= $message;

        if ($this->serialConnection->autoFlush === true) {
            $this->serialConnection->flush();
        }

        usleep((int) ($waitForReply * 1000000));
    }

    private function setupDevice(): void
    {
        $this->serialConnection->setDevice($this->serialConnection->_device);
        $this->serialConnection->setBaudRate($this->serialConnection->baudRate);
        $this->serialConnection->setParity($this->serialConnection->pa);
    }
}
