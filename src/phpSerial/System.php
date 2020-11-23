<?php declare(strict_types=1);

namespace steinmb\phpSerial;

use Exception;

class System
{
    public $operatingSystem;
    public $_os;

    public function __construct()
    {
        setlocale(LC_ALL, 'en_US');

        $sysName = php_uname();

        if (strpos($sysName, 'Linux') === 0) {
            $this->operatingSystem = 'linux';
            $this->_os = 'linux';

            if (exec("stty") === 0) {
                $this->shutdown();
            } else {
                throw new Exception(
                    'No stty available, unable to run.'
                );
            }
        } elseif (strpos($sysName, 'Darwin') === 0) {
            $this->_os = 'osx';
            $this->shutdown();
        } elseif (strpos($sysName, 'Windows') === 0) {
            $this->_os = 'windows';
            $this->shutdown();
        } else {
            trigger_error(
                'Unknown host OS, unable to run.',
                E_USER_ERROR
            );
        }
    }

    private function shutdown(): void
    {
        register_shutdown_function(array($this, 'deviceClose'));
    }
}
