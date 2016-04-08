<?php

namespace Camcima\MySqlDiff\Model;

class ChangedTable
{
    /**
     * @var Table
     */
    private $fromTable;

    /**
     * @var Table
     */
    private $toTable;

    /**
     * @param Table $fromTable
     * @param Table $toTable
     */
    public function __construct(Table $fromTable, Table $toTable)
    {
        $this->fromTable = $fromTable;
        $this->toTable = $toTable;
    }

    /**
     * @return Table
     */
    public function getFromTable()
    {
        return $this->fromTable;
    }

    /**
     * @return Table
     */
    public function getToTable()
    {
        return $this->toTable;
    }

}