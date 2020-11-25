<?php declare(strict_types=1);


namespace steinmb\phpSerial;


interface ExecuteInterface
{
    public function execute($cmd, &$out = null): int;
}
