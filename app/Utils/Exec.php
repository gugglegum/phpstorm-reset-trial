<?php

declare(strict_types=1);

namespace App\Utils;

final class Exec
{
    /**
     * @param string $command
     * @throws \Exception
     */
    public static function exec(string $command)
    {
        $process = proc_open($command, [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ], $pipes);

        if (is_resource($process)) {
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $retCode = proc_close($process);
            if ($retCode !== 0) {
                throw new \Exception("External command ({$command}) returned bad result code ({$retCode})");
            }
        } else {
            throw new \Exception("Failed to execute command: {$command}");
        }
    }


}
