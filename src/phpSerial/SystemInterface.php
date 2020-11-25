<?php declare(strict_types=1);


namespace steinmb\phpSerial;


interface SystemInterface
{
    public function operatingSystem(): string;
}
