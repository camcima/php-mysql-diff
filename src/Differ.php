<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\ChangedTable;
use Camcima\MySqlDiff\Model\Column;
use Camcima\MySqlDiff\Model\Database;
use Camcima\MySqlDiff\Model\DatabaseDiff;

/**
 * Class Differ.
 */
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

            if ($fromTable->generateCreationScript(true) !== $toTable->generateCreationScript(true)) {
                $changedTable = new ChangedTable($fromTable, $toTable);
                $this->diffChangedTable($changedTable);

                if (!empty($changedTable->generateAlterScript())) {
                    $databaseDiff->addChangedTable($changedTable);
                }
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
            if ($toColumn->generateCreationScript() !== $fromColumn->generateCreationScript()) {
                $changedTable->addChangedColumn($toColumn);
                continue;
            }

            if (!$fromColumn->getPreviousColumn() && !$toColumn->getPreviousColumn()) {
                continue;
            }
            if (!$fromColumn->getPreviousColumn() && $toColumn->getPreviousColumn() instanceof Column) {
                $this->addChangedColumn($changedTable, $toColumn);
            } elseif ($fromColumn->getPreviousColumn() instanceof Column && !$toColumn->getPreviousColumn()) {
                $this->addChangedColumn($changedTable, $toColumn);
            } elseif ($fromColumn->getPreviousColumn()->getName() !== $toColumn->getPreviousColumn()->getName()) {
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

        if ($fromTable->generatePrimaryKeyCreationScript() !== $toTable->generatePrimaryKeyCreationScript()) {
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
            if ($toIndex->generateCreationScript() !== $fromIndex->generateCreationScript()) {
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
            if ($toForeignKey->generateCreationScript() !== $fromForeignKey->generateCreationScript()) {
                $changedTable->addChangedForeignKey($toForeignKey);
            }
        }
    }

    /**
     * @param DatabaseDiff $databaseDiff
     * @param bool $displayProgress
     *
     * @return string
     */
    public function generateMigrationScript(DatabaseDiff $databaseDiff, $displayProgress = false)
    {
        return implode(PHP_EOL, $this->generateMigrationScriptArray($databaseDiff, $displayProgress));
    }

    /**
     * @param DatabaseDiff $databaseDiff
     * @param bool $displayProgress
     *
     * @return array
     */
    public function generateMigrationScriptArray(DatabaseDiff $databaseDiff, $displayProgress = false)
    {
        $migrationScript = [];
        $migrationScript[] = '# Disable Foreign Keys Check';
        $migrationScript[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $migrationScript[] = 'SET SQL_MODE = \'\';';

        $migrationScript[] = '';
        $migrationScript[] = '# Deleted Tables';
        foreach ($databaseDiff->getDeletedTables() as $deletedTable) {
            $migrationScript[] = '';
            $migrationScript[] = sprintf('-- deleted table `%s`', $deletedTable->getName());
            $migrationScript[] = '';

            if ($displayProgress) {
                $migrationScript[] = sprintf("SELECT 'Dropping table %s';", $deletedTable->getName());
            }

            $migrationScript[] = sprintf('DROP TABLE `%s`;', $deletedTable->getName());
        }

        $migrationScript[] = '';
        $migrationScript[] = '# Changed Tables';
        foreach ($databaseDiff->getChangedTables() as $changedTable) {
            $migrationScript[] = '';
            $migrationScript[] = sprintf('-- changed table `%s`', $changedTable->getName());
            $migrationScript[] = '';

            if ($displayProgress) {
                $migrationScript[] = sprintf("SELECT 'Altering table %s';", $changedTable->getName());
            }

            $migrationScript[] = $changedTable->generateAlterScript();
        }
        $migrationScript[] = '';
        $migrationScript[] = '# New Tables';

        foreach ($databaseDiff->getNewTables() as $newTable) {
            $migrationScript[] = '';
            $migrationScript[] = sprintf('-- new table `%s`', $newTable->getName());
            $migrationScript[] = '';

            if ($displayProgress) {
                $migrationScript[] = sprintf("SELECT 'Creating table %s';", $newTable->getName());
            }

            $migrationScript[] = $newTable->generateCreationScript(true);
        }

        $migrationScript[] = '';
        $migrationScript[] = '# Disable Foreign Keys Check';
        $migrationScript[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $migrationScript[] = '';

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
