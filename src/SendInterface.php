<?php declare(strict_types=1);

interface SendInterface
{
    public function __construct(SerialConnection $serialConnection);
    public function send(): void;
}
