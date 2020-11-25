<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use RuntimeException;

final class System implements SystemInterface
{
    public function operatingSystem(): string
    {
        setlocale(LC_ALL, 'en_US');
        $sysName = php_uname();

        if (strpos($sysName, 'Linux') === 0) {
            return 'linux';
        }

        if (strpos($sysName, 'Darwin') === 0) {
            return 'osx';
        }

        if (strpos($sysName, 'Windows') === 0) {
            return 'windows';
        }

        throw new RuntimeException(
            'Unknown operation system.'
        );
    }
}
