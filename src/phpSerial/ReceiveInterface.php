<?php declare(strict_types=1);

namespace steinmb\phpSerial;

interface ReceiveInterface
{
    public function __construct(SerialConnection $serialConnection);
    public function readPort();
}
