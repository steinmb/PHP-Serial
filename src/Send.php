<?php declare(strict_types=1);

class Send implements SendInterface
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
        $this->serialConnection->_buffer .= $message;

        if ($this->serialConnection->autoFlush === true) {
            $this->serialflush();
        }

        usleep((int) ($waitForReply * 1000000));
    }
}
