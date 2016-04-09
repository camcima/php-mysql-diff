<?php

namespace Camcima\MySqlDiff\Command;

use Camcima\MySqlDiff\Differ;
use Camcima\MySqlDiff\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('diff:migrate')
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
        $output->writeln('');

        $verbose = !empty($input->getOption('output'));

        if ($verbose) {
            $output->writeln('<info>PHP MySQL Diff</info> <comment>1.0.0</comment>');
            $output->writeln('----------------------------------------');
            $output->writeln('');
        }

        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        if (!file_exists($from)) {
            $output->writeln('<error>' . sprintf('File not found: %s', $from) . '</error>');
            exit;
        }

        if (!file_exists($to)) {
            $output->writeln('<error>' . sprintf('File not found: %s', $to) . '</error>');
            exit;
        }

        $parser = new Parser();

        if ($verbose) {
            $output->write('• Parsing initial database ......');
        }
        $fromDatabase = $parser->parseDatabase(file_get_contents($from));
        if ($verbose) {
            $output->writeln(' <info>✓</info>');
            $output->write('• Parsing target database .......');
        }
        $toDatabase = $parser->parseDatabase(file_get_contents($to));
        if ($verbose) {
            $output->writeln(' <info>✓</info>');
            $output->write('• Comparing databases ...........');
        }
        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);
        if ($verbose) {
            $output->writeln(' <info>✓</info>');
            $output->write('• Generating migration script ...');
        }

        $migrationScript = $differ->generateMigrationScript($databaseDiff);
        if ($verbose) {
            $output->writeln(' <info>✓</info>');
            $output->write('• Writing output file ...........');

            $outputFile = $input->getOption('output');
            file_put_contents($outputFile, $migrationScript);
            $output->writeln(' <info>✓</info>');
            $output->writeln('');
            $output->writeln('<comment>Migration script generated!</comment>');
        } else {
            $output->writeln($migrationScript);
        }
    }
}