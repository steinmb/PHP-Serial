<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use RuntimeException;

final class System
{
    private $operatingSystem;

    public function __construct()
    {
        setlocale(LC_ALL, 'en_US');
        $sysName = php_uname();

        if (strpos($sysName, 'Linux') === 0) {
            $this->operatingSystem = 'linux';
            return;
        }

        if (strpos($sysName, 'Darwin') === 0) {
            $this->operatingSystem = 'osx';
            return;
        }

        if (strpos($sysName, 'Windows') === 0) {
            $this->operatingSystem = 'windows';
            return;
        }

        throw new RuntimeException(
            'Unknown operation system.'
        );
    }

    public function operatingSystem(): string
    {
        return $this->operatingSystem;
    }

}
