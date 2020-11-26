<?php declare(strict_types=1);


namespace steinmb\phpSerial;


final class ExecuteCommand implements ExecuteInterface
{
    public function execute($cmd, &$out = null): int
    {
        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptorspec, $pipes);
        $ret = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $retVal = proc_close($process);

        if (func_num_args() === 2) {
            $out = array($ret, $err);
        }

        return $retVal;
    }
}
