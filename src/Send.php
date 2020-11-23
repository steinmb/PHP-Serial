<?php


class Send implements SendInterface
{

    /**
     * Sends a string to the device
     *
     * @param $message
     * @param float $waitForReply
     *   time to wait for the reply (in seconds).
     */
    public function send($message, $waitForReply = 0.1): void
    {
        $this->_buffer .= $message;

        if ($this->autoFlush === true) {
            $this->serialflush();
        }

        usleep((int) ($waitForReply * 1000000));
    }
}
