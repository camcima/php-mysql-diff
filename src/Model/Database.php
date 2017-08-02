<?php

namespace Camcima\MySqlDiff\Model;

/**
 * Class Database
 *
 * @package Camcima\MySqlDiff\Model
 */
class Database
{
    /**
     * @var Table[]
     */
    private $tables = [];

    /**
     * @return Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param Table $table
     */
    public function addTable(Table $table)
    {
        $this->tables[$table->getName()] = $table;
    }

    /**
     * @param string $tableName
     * @return Table
     * @throws \RuntimeException
     */
    public function getTableByName($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            throw new \RuntimeException(sprintf('Table "%s" not found in database!', $tableName));
        }

        return $this->tables[$tableName];
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function hasTable($tableName)
    {
        return isset($this->tables[$tableName]);
    }
}
