<?php

namespace Camcima\MySqlDiff\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param bool $forceOuput
     */
    protected function outputString($string = '', $forceOuput = false)
    {
        if ($this->verbose || $forceOuput) {
            $this->output->write($string);
        }
    }
}
