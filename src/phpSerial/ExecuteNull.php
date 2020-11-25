<?php declare(strict_types=1);


namespace steinmb\phpSerial;


class ExecuteNull implements ExecuteInterface
{
    public function execute($cmd, &$out = null): int
    {
        return 0;
    }
}
