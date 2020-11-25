<?php declare(strict_types=1);

namespace steinmb\phpSerial;

interface ReceiveInterface
{
    public function __construct(GatewayInterface $serialConnection);
    public function read();
}
