<?php

namespace Camcima\MySqlDiff\Command;

use Camcima\MySqlDiff\Differ;
use Camcima\MySqlDiff\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Generate migration script')
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'File path of the creation script of the initial database'
            )
            ->addArgument(
                'to',
                InputArgument::REQUIRED,
                'File path of the creation script of the target database'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output migration script to a file'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->verbose = !empty($input->getOption('output'));

        $this->outputLine('', true);
        $this->outputLine('<info>PHP MySQL Diff</info> <comment>1.0.0</comment>');
        $this->outputLine('----------------------------------------');
        $this->outputLine();

        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        if (!file_exists($from)) {
            $this->outputLine('<error>' . sprintf('File not found: %s', $from) . '</error>');
            exit;
        }

        if (!file_exists($to)) {
            $this->outputLine('<error>' . sprintf('File not found: %s', $to) . '</error>');
            exit;
        }

        $parser = new Parser();


        $this->outputString('• Parsing initial database ......');
        $fromDatabase = $parser->parseDatabase(file_get_contents($from));
        $this->outputLine(' <info>✓</info>');

        $this->outputString('• Parsing target database .......');
        $toDatabase = $parser->parseDatabase(file_get_contents($to));
        $this->outputLine(' <info>✓</info>');

        $this->outputString('• Comparing databases ...........');
        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);
        $this->outputLine(' <info>✓</info>');

        if ($databaseDiff->isEmptyDifferences()) {
            $this->outputLine();
            $this->outputLine('<comment>The databases have the same schema!</comment>');
            exit;
        }

        $this->outputString('• Generating migration script ...');
        $migrationScript = $differ->generateMigrationScript($databaseDiff);
        $this->outputLine(' <info>✓</info>');

        if ($this->verbose) {
            $this->outputString('• Writing output file ...........');
            $outputFile = $input->getOption('output');
            file_put_contents($outputFile, $migrationScript);
            $this->outputLine(' <info>✓</info>');

            $this->outputLine();
            $this->outputLine('<comment>Migration script generated!</comment>');
        } else {
            $this->outputLine($migrationScript, true);
        }
    }
}
