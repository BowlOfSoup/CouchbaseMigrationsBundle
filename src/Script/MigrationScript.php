<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Script;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MigrationScript
{
    /**
     * @param \Composer\Script\Event $event
     */
    public static function run(Event $event)
    {
        $io = $event->getIO();

        try {
            $process = new Process('bin/console couchbase:migrations:migrate --no-verbose');
            $process->setTimeout(null);
            $process->mustRun(static::createIOCallback($io));
        } catch (ProcessFailedException $e) {
            // Functionality not available, gracefully exit.
        }
    }

    /**
     * @param \Composer\IO\IOInterface $io
     *
     * @throws \RuntimeException
     *
     * @return \Closure
     */
    protected static function createIOCallback(IOInterface $io)
    {
        static $callback;

        if (is_callable($callback)) {
            return $callback;
        }

        return $callback = function ($type, $buffer) use ($io) {
            if (Process::ERR === $type) {
                $io->writeError($buffer, false);
            } else {
                $io->write($buffer, false);
            }
        };
    }
}
