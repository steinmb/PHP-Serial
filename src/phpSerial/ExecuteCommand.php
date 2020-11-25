<?php declare(strict_types=1);


namespace steinmb\phpSerial;


class ExecuteCommand implements ExecuteInterface
{
    public function execute($cmd, &$out = null): int
    {
        $desc = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes);
        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $retVal = proc_close($proc);

        if (func_num_args() === 2) {
            $out = array($ret, $err);
        }

        return $retVal;
    }

}
