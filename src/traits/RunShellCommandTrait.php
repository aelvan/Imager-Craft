<?php

namespace aelvan\imager\traits;

use Craft;
use mikehaertl\shellcommand\Command;

trait RunShellCommandTrait
{
    /**
     * Runs a shell command through mikehaertl\shellcommand
     * 
     * @param $commandString
     *
     * @return string
     */
    private static function runShellCommand($commandString): string
    {
        $shellCommand = new Command();
        $shellCommand->setCommand($commandString);

        if ($shellCommand->execute()) {
            $result = $shellCommand->getOutput();
        } else {
            $result = $shellCommand->getError();
        }

        return $result;
    }
}