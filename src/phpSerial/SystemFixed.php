<?php declare(strict_types=1);


namespace steinmb\phpSerial;


class SystemFixed implements SystemInterface
{
    private $system;

    public function __construct(string $system)
    {
        $this->system = $system;
    }

    public function operatingSystem(): string
    {
        return $this->system;
    }
}
