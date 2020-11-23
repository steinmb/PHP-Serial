<?php declare(strict_types=1);

interface ReceiveInterface
{
    public function __construct(SerialConnection $serialConnection);
    public function readPort();
}
