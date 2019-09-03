<?php
declare(strict_types=1);

namespace DV\Program;

use Swoole\Process as SwooleProcess;

trait IsRunningTrait
{
    /**
     * Is the swoole HTTP server running?
     */
    public function isRunning() : bool
    {
        $pids = $this->pidManager->read();

        if ([] === $pids) {
            return false;
        }

        [$masterPid, $managerPid] = $pids;

        if ($managerPid) {
            // Swoole process mode
            return $masterPid && $managerPid && SwooleProcess::kill((int) $managerPid, 0);
        }

        // Swoole base mode, no manager process
        return $masterPid && SwooleProcess::kill((int) $masterPid, 0);
    }
}
