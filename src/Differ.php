<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\ChangedTable;
use Camcima\MySqlDiff\Model\Database;
use Camcima\MySqlDiff\Model\DatabaseDiff;

class Differ
{
    /**
     * @param Database $fromDatabase
     * @param Database $toDatabase
     *
     * @return DatabaseDiff
     */
    public function diff(Database $fromDatabase, Database $toDatabase)
    {
        $databaseDiff = new DatabaseDiff();

        foreach ($fromDatabase->getTables() as $fromTable) {
            if (!$toDatabase->hasTable($fromTable->getName())) {
                $databaseDiff->addDeletedTable($fromTable);
                continue;
            }

            $toTable = $toDatabase->getTableByName($fromTable->getName());
            if ($fromTable->getCreationScript() != $toTable->getCreationScript()) {
                $databaseDiff->addChangedTable(new ChangedTable($fromTable, $toTable));
            }
        }

        foreach ($toDatabase->getTables() as $toTable) {
            if (!$fromDatabase->hasTable($toTable->getName())) {
                $databaseDiff->addNewTable($toTable);
            }
        }

        return $databaseDiff;
    }
}