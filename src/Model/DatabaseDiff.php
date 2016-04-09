<?php

namespace Camcima\MySqlDiff\Model;

class DatabaseDiff
{
    /**
     * @var Table[]
     */
    private $newTables = [];

    /**
     * @var Table[]
     */
    private $deletedTables = [];

    /**
     * @var ChangedTable[];
     */
    private $changedTables = [];

    /**
     * @return Table[]
     */
    public function getNewTables()
    {
        return $this->newTables;
    }

    /**
     * @param Table $table
     */
    public function addNewTable(Table $table)
    {
        $this->newTables[] = $table;
    }

    /**
     * @return Table[]
     */
    public function getDeletedTables()
    {
        return $this->deletedTables;
    }

    /**
     * @param Table $table
     */
    public function addDeletedTable(Table $table)
    {
        $this->deletedTables[] = $table;
    }

    /**
     * @return ChangedTable[]
     */
    public function getChangedTables()
    {
        return $this->changedTables;
    }

    /**
     * @param ChangedTable $changedTable
     */
    public function addChangedTable(ChangedTable $changedTable)
    {
        $this->changedTables[] = $changedTable;
    }

    /**
     * @return bool
     */
    public function isEmptyDifferences()
    {
        return empty($this->newTables) && empty($this->deletedTables) && empty($this->changedTables);
    }
}
