<?php

namespace Camcima\MySqlDiff\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand.
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param string $line
     * @param bool $forceOutput
     */
    protected function outputLine($line = '', $forceOutput = false)
    {
        if ($this->verbose || $forceOutput) {
            $this->output->writeln($line);
        }
    }

    /**
     * @param string $string
     * @param bool $forceOutput
     */
    protected function outputString($string = '', $forceOutput = false)
    {
        if ($this->verbose || $forceOutput) {
            $this->output->write($string);
        }
    }
}
