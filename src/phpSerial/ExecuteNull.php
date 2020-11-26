<?php declare(strict_types=1);


namespace steinmb\phpSerial;


final class ExecuteNull implements ExecuteInterface
{
    protected $returnValue;

    public function __construct(int $returnValue)
    {
        $this->returnValue = $returnValue;
    }

    public function execute($cmd, &$out = null): int
    {
        $out = $cmd;
        return $this->returnValue;
    }
}
