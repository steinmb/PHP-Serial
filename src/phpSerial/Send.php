<?php declare(strict_types=1);

namespace steinmb\phpSerial;

final class Send implements SendInterface
{
    /**
     * @var SerialConnection
     */
    private $serialConnection;

    public function __construct(GatewayInterface $serialConnection)
    {
        $this->serialConnection = $serialConnection;
    }

    /**
     * Sends a string to the device.
     *
     * @param $message
     * @param float $waitForReply
     *   Time to wait for the reply (in seconds).
     */
    public function send(string $message, float $waitForReply = 0.1): void
    {
        $this->setupDevice();
        $this->serialConnection->sendMessage($message);

        if ($this->serialConnection->autoFlush === true) {
            $this->serialConnection->flush();
        }

        usleep((int) ($waitForReply * 1000000));
    }

    private function setupDevice(): void
    {
        $this->serialConnection->connect('w+b');
    }
}
