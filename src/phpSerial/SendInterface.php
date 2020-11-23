<?php declare(strict_types=1);

namespace steinmb\phpSerial;

interface SendInterface
{
    public function __construct(SerialConnection $serialConnection);
    public function send($message, $waitForReply): void;
}
