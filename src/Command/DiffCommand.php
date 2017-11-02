<?php

namespace Camcima\MySqlDiff\Command;

use Camcima\MySqlDiff\Differ;
use Camcima\MySqlDiff\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DiffCommand.
 */
class DiffCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('diff')
            ->setDescription('Show differences between initial and target database')
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
                'ignore',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Table ignore list'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->verbose = true;

        $this->outputLine();
        $this->outputLine('<info>PHP MySQL Diff</info> <comment>1.0.0</comment>');
        $this->outputLine('----------------------------------------');
        $this->outputLine();

        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        if (!file_exists($from)) {
            $this->outputLine('<error>' . sprintf('File not found: %s', $from) . '</error>');
            exit(1);
        }

        if (!file_exists($to)) {
            $this->outputLine('<error>' . sprintf('File not found: %s', $to) . '</error>');
            exit(1);
        }

        $ignoreTables = [];
        if ($input->getOption('ignore')) {
            $ignoreListFile = $input->getOption('ignore');
            if (!file_exists($ignoreListFile)) {
                $this->outputLine('<error>' . sprintf('File not found: %s', $ignoreListFile) . '</error>');
                exit(1);
            }

            $ignoreTables = file($ignoreListFile);
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
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase, $ignoreTables);
        $this->outputLine(' <info>✓</info>');
        $this->outputLine();

        if ($databaseDiff->isEmptyDifferences()) {
            $this->outputLine('<comment>The databases have the same schema!</comment>');
            exit;
        }

        $this->outputLine(sprintf('<info>FROM</info> %s', $from));
        $this->outputLine(sprintf('<info>  TO</info> %s', $to));
        $this->outputLine();

        foreach ($databaseDiff->getNewTables() as $newTable) {
            $this->outputLine(sprintf('<info>▲</info> table "%s" is in the TO database but not in the FROM database', $newTable->getName()));
        }

        foreach ($databaseDiff->getDeletedTables() as $deletedTable) {
            $this->outputLine(sprintf('<fg=red>▼</> table "%s" is in the FROM database but not in the TO database', $deletedTable->getName()));
        }

        foreach ($databaseDiff->getChangedTables() as $changedTable) {
            $this->outputLine(sprintf('<comment>►</comment> table "%s" has a different schema', $changedTable->getName()));

            foreach ($changedTable->getNewColumns() as $newColumn) {
                $this->outputLine(sprintf('    <info>▲</info> column "%s" is in the TO database but not in the FROM database', $newColumn->getName()));
            }

            foreach ($changedTable->getDeletedColumns() as $deletedColumn) {
                $this->outputLine(sprintf('    <fg=red>▼</> column "%s" is in the FROM database but not in the TO database', $deletedColumn->getName()));
            }

            foreach ($changedTable->getChangedColumns() as $changedColumn) {
                $this->outputLine(sprintf('    <comment>►</comment> column "%s" has a different definition', $changedColumn->getName()));
                $this->outputLine(sprintf('        <comment>FROM</comment> %s', $changedTable->getFromTable()->getColumnByName($changedColumn->getName())->generateCreationScript()));
                $this->outputLine(sprintf('        <comment>  TO</comment> %s', $changedColumn->generateCreationScript()));
            }

            if ($changedTable->isDeletedPrimaryKey()) {
                $this->outputLine(sprintf('    <fg=red>▼</> primary key is in the FROM database but not in the TO database'));
            } elseif (!empty($changedTable->getChangedPrimaryKeys())) {
                if (empty($changedTable->getFromTable()->getPrimaryKeys())) {
                    $this->outputLine(sprintf('    <info>▲</info> primary key is in the TO database but not in the FROM database'));
                } else {
                    $this->outputLine(sprintf('    <comment>►</comment> primary key has a different definition'));
                    $this->outputLine(sprintf('        <comment>FROM</comment> %s', $changedTable->getFromTable()->generatePrimaryKeyCreationScript()));
                    $this->outputLine(sprintf('        <comment>  TO</comment> %s', $changedTable->getToTable()->generatePrimaryKeyCreationScript()));
                }
            }

            foreach ($changedTable->getNewForeignKeys() as $newForeignKey) {
                $this->outputLine(sprintf('    <info>▲</info> foreign key "%s" is in the TO database but not in the FROM database', $newForeignKey->getName()));
            }

            foreach ($changedTable->getDeletedForeignKeys() as $deletedForeignKey) {
                $this->outputLine(sprintf('    <fg=red>▼</> foreign key "%s" is in the FROM database but not in the TO database', $deletedForeignKey->getName()));
            }

            foreach ($changedTable->getChangedForeignKeys() as $changedForeignKey) {
                $this->outputLine(sprintf('    <comment>►</comment> foreign key "%s" has a different definition', $changedForeignKey->getName()));
                $this->outputLine(sprintf('        <comment>FROM</comment> %s', $changedTable->getFromTable()->getForeignKeyByName($changedForeignKey->getName())->generateCreationScript()));
                $this->outputLine(sprintf('        <comment>  TO</comment> %s', $changedForeignKey->generateCreationScript()));
            }

            foreach ($changedTable->getNewIndexes() as $newIndex) {
                $this->outputLine(sprintf('    <info>▲</info> index "%s" is in the TO database but not in the FROM database', $newIndex->getName()));
            }

            foreach ($changedTable->getDeletedIndexes() as $deletedIndex) {
                $this->outputLine(sprintf('    <fg=red>▼</> index "%s" is in the FROM database but not in the TO database', $deletedIndex->getName()));
            }

            foreach ($changedTable->getChangedIndexes() as $changedIndex) {
                $this->outputLine(sprintf('    <comment>►</comment> index "%s" has a different definition', $changedIndex->getName()));
                $this->outputLine(sprintf('        <comment>FROM</comment> %s', $changedTable->getFromTable()->getIndexByName($changedIndex->getName())->generateCreationScript()));
                $this->outputLine(sprintf('        <comment>  TO</comment> %s', $changedIndex->generateCreationScript()));
            }
        }

        $this->outputLine();
        $this->outputLine('<comment>Diff completed!</comment>');
    }
}
