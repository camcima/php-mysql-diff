<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\ChangedTable;
use Camcima\MySqlDiff\Model\Column;
use Camcima\MySqlDiff\Model\Database;
use Camcima\MySqlDiff\Model\DatabaseDiff;

class Differ
{
    /**
     * @param Database $fromDatabase
     * @param Database $toDatabase
     * @param array $ignoreList
     *
     * @return DatabaseDiff
     */
    public function diffDatabases(Database $fromDatabase, Database $toDatabase, array $ignoreList = [])
    {
        $databaseDiff = new DatabaseDiff();

        foreach ($fromDatabase->getTables() as $fromTable) {

            if ($this->isTableIgnored($fromTable->getName(), $ignoreList)) {
                continue;
            }

            if (!$toDatabase->hasTable($fromTable->getName())) {
                $databaseDiff->addDeletedTable($fromTable);
                continue;
            }

            $toTable = $toDatabase->getTableByName($fromTable->getName());
            if ($fromTable->generateCreationScript(true) != $toTable->generateCreationScript(true)) {
                $changedTable = new ChangedTable($fromTable, $toTable);
                $this->diffChangedTable($changedTable);
                $databaseDiff->addChangedTable($changedTable);
            }
        }

        foreach ($toDatabase->getTables() as $toTable) {

            if ($this->isTableIgnored($toTable->getName(), $ignoreList)) {
                continue;
            }

            if (!$fromDatabase->hasTable($toTable->getName())) {
                $databaseDiff->addNewTable($toTable);
            }
        }

        return $databaseDiff;
    }

    /**
     * @param ChangedTable $changedTable
     */
    public function diffChangedTable(ChangedTable $changedTable)
    {
        $this->diffColumns($changedTable);
        $this->diffPrimaryKey($changedTable);
        $this->diffIndexes($changedTable);
        $this->diffForeignKeys($changedTable);
    }

    /**
     * @param ChangedTable $changedTable
     */
    private function diffColumns(ChangedTable $changedTable)
    {
        $fromTable = $changedTable->getFromTable();
        $toTable = $changedTable->getToTable();

        // Determine deleted columns
        foreach ($fromTable->getColumns() as $fromColumn) {
            if (!$toTable->hasColumn($fromColumn->getName())) {
                $changedTable->addDeletedColumn($fromColumn);
            }
        }

        foreach ($toTable->getColumns() as $toColumn) {

            // Determine new columns
            if (!$fromTable->hasColumn($toColumn->getName())) {
                $changedTable->addNewColumn($toColumn);
                continue;
            }

            // Determine changed columns
            $fromColumn = $fromTable->getColumnByName($toColumn->getName());
            if ($toColumn->generateCreationScript() != $fromColumn->generateCreationScript()) {
                $changedTable->addChangedColumn($toColumn);
                continue;
            }

            if (!$fromColumn->getPreviousColumn() && !$toColumn->getPreviousColumn()) {
                continue;
            } elseif (!$fromColumn->getPreviousColumn() && $toColumn->getPreviousColumn() instanceof Column) {
                $this->addChangedColumn($changedTable, $toColumn);
            } elseif ($fromColumn->getPreviousColumn() instanceof Column && !$toColumn->getPreviousColumn()) {
                $this->addChangedColumn($changedTable, $toColumn);
            } elseif ($fromColumn->getPreviousColumn()->getName() != $toColumn->getPreviousColumn()->getName()) {
                $this->addChangedColumn($changedTable, $toColumn);
            }
        }
    }

    /**
     * @param ChangedTable $changedTable
     * @param Column $column
     */
    private function addChangedColumn(ChangedTable $changedTable, Column $column)
    {
        if (!$changedTable->hasNewColumn($column->getName())) {
            $changedTable->addChangedColumn($column);
        }

        if (!$column->getNextColumn()) {
            return;
        }

        $this->addChangedColumn($changedTable, $column->getNextColumn());
    }

    /**
     * @param ChangedTable $changedTable
     */
    private function diffPrimaryKey(ChangedTable $changedTable)
    {
        $fromTable = $changedTable->getFromTable();
        $toTable = $changedTable->getToTable();

        if (empty($toTable->getPrimaryKeys()) && !empty($fromTable->getPrimaryKeys())) {
            $changedTable->setDeletedPrimaryKey(true);

            return;
        }

        if ($fromTable->generatePrimaryKeyCreationScript() != $toTable->generatePrimaryKeyCreationScript()) {
            $changedTable->setChangedPrimaryKeys($toTable->getPrimaryKeys());
        }
    }

    /**
     * @param ChangedTable $changedTable
     */
    private function diffIndexes(ChangedTable $changedTable)
    {
        $fromTable = $changedTable->getFromTable();
        $toTable = $changedTable->getToTable();

        // Determine deleted indexes
        foreach ($fromTable->getIndexes() as $fromIndex) {
            if (!$toTable->hasIndex($fromIndex->getName())) {
                $changedTable->addDeletedIndex($fromIndex);
            }
        }

        foreach ($toTable->getIndexes() as $toIndex) {

            // Determine new indexes
            if (!$fromTable->hasIndex($toIndex->getName())) {
                $changedTable->addNewIndex($toIndex);
                continue;
            }

            // Determine changed indexes
            $fromIndex = $fromTable->getIndexByName($toIndex->getName());
            if ($toIndex->generateCreationScript() != $fromIndex->generateCreationScript()) {
                $changedTable->addChangedIndex($toIndex);
            }
        }
    }

    /**
     * @param ChangedTable $changedTable
     */
    private function diffForeignKeys(ChangedTable $changedTable)
    {
        $fromTable = $changedTable->getFromTable();
        $toTable = $changedTable->getToTable();

        // Determine deleted foreign keys
        foreach ($fromTable->getForeignKeys() as $fromForeignKey) {
            if (!$toTable->hasForeignKey($fromForeignKey->getName())) {
                $changedTable->addDeletedForeignKey($fromForeignKey);
            }
        }

        foreach ($toTable->getForeignKeys() as $toForeignKey) {

            // Determine new foreign keys
            if (!$fromTable->hasForeignKey($toForeignKey->getName())) {
                $changedTable->addNewForeignKey($toForeignKey);
                continue;
            }

            // Determine changed foreign keys
            $fromForeignKey = $fromTable->getForeignKeyByName($toForeignKey->getName());
            if ($toForeignKey->generateCreationScript() != $fromForeignKey->generateCreationScript()) {
                $changedTable->addChangedForeignKey($toForeignKey);
            }
        }
    }

    /**
     * @param DatabaseDiff $databaseDiff
     *
     * @return string
     */
    public function generateMigrationScript(DatabaseDiff $databaseDiff)
    {
        $migrationScript = '';
        $migrationScript .= '# Disable Foreign Keys Check' . PHP_EOL;
        $migrationScript .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;
        $migrationScript .= 'SET SQL_MODE = \'\';' . PHP_EOL;

        $migrationScript .= PHP_EOL . '# Deleted Tables' . PHP_EOL;
        foreach ($databaseDiff->getDeletedTables() as $deletedTable) {
            $migrationScript .= PHP_EOL . sprintf('-- deleted table `%s`' . PHP_EOL . PHP_EOL, $deletedTable->getName());
            $migrationScript .= sprintf('DROP TABLE `%s`;' . PHP_EOL, $deletedTable->getName());
        }

        $migrationScript .= PHP_EOL . '# Changed Tables' . PHP_EOL;
        foreach ($databaseDiff->getChangedTables() as $changedTable) {
            $migrationScript .= PHP_EOL . sprintf('-- changed table `%s`' . PHP_EOL . PHP_EOL, $changedTable->getName());
            $migrationScript .= $changedTable->generateAlterScript() . PHP_EOL;
        }

        $migrationScript .= PHP_EOL . '# New Tables' . PHP_EOL;
        foreach ($databaseDiff->getNewTables() as $newTable) {
            $migrationScript .= PHP_EOL . sprintf('-- new table `%s`' . PHP_EOL . PHP_EOL, $newTable->getName());
            $migrationScript .= $newTable->generateCreationScript(true) . PHP_EOL;
        }

        $migrationScript .= PHP_EOL . '# Disable Foreign Keys Check' . PHP_EOL;
        $migrationScript .= 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;

        return $migrationScript;
    }

    /**
     * @param string $tableName
     * @param array $ignoreList
     *
     * @return bool
     */
    private function isTableIgnored($tableName, array $ignoreList)
    {
        foreach ($ignoreList as $ignoreRegExp) {
            if (preg_match($ignoreRegExp, $tableName) === 1) {
                return true;
            }
        }

        return false;
    }
}
