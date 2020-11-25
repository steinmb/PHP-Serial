<?php declare(strict_types=1);


namespace steinmb\phpSerial;


interface SystemInterface
{
    public function __construct();
    public function operatingSystem(): string;
}
