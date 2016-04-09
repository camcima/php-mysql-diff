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
     * @var Column[]
     */
    private $newColumns;

    /**
     * @var Column[]
     */
    private $deletedColumns;

    /**
     * @var Column[]
     */
    private $changedColumns;

    /**
     * @var Column[]
     */
    private $changedPrimaryKeys;

    /**
     * @var bool
     */
    private $deletedPrimaryKey;

    /**
     * @var Index[]
     */
    private $newIndexes;

    /**
     * @var Index[]
     */
    private $deletedIndexes;

    /**
     * @var Index[]
     */
    private $changedIndexes;

    /**
     * @var ForeignKey[]
     */
    private $newForeignKeys;

    /**
     * @var ForeignKey[]
     */
    private $deletedForeignKeys;

    /**
     * @var ForeignKey[]
     */
    private $changedForeignKeys;

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
     * @return string
     */
    public function getName()
    {
        return $this->toTable->getName();
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

    /**
     * @return Column[]
     */
    public function getNewColumns()
    {
        return $this->newColumns;
    }

    /**
     * @param Column $newColumn
     */
    public function addNewColumn(Column $newColumn)
    {
        $this->newColumns[$newColumn->getName()] = $newColumn;
    }

    /**
     * @return Column[]
     */
    public function getDeletedColumns()
    {
        return $this->deletedColumns;
    }

    /**
     * @param Column $deletedColumn
     */
    public function addDeletedColumn(Column $deletedColumn)
    {
        $this->deletedColumns[$deletedColumn->getName()] = $deletedColumn;
    }

    /**
     * @return Column[]
     */
    public function getChangedColumns()
    {
        return $this->changedColumns;
    }

    /**
     * @param Column $changedColumn
     */
    public function addChangedColumn(Column $changedColumn)
    {
        $this->changedColumns[$changedColumn->getName()] = $changedColumn;
    }

    /**
     * @return Column[]
     */
    public function getChangedPrimaryKeys()
    {
        return $this->changedPrimaryKeys;
    }

    /**
     * @param Column[] $changedPrimaryKeys
     */
    public function setChangedPrimaryKeys($changedPrimaryKeys)
    {
        $this->changedPrimaryKeys = $changedPrimaryKeys;
    }

    /**
     * @return bool
     */
    public function isDeletedPrimaryKey()
    {
        return $this->deletedPrimaryKey;
    }

    /**
     * @param bool $deletedPrimaryKey
     */
    public function setDeletedPrimaryKey($deletedPrimaryKey)
    {
        $this->deletedPrimaryKey = $deletedPrimaryKey;
    }

    /**
     * @return Index[]
     */
    public function getNewIndexes()
    {
        return $this->newIndexes;
    }

    /**
     * @param Index $index
     */
    public function addNewIndex(Index $index)
    {
        $this->newIndexes[$index->getName()];
    }

    /**
     * @return Index[]
     */
    public function getDeletedIndexes()
    {
        return $this->deletedIndexes;
    }

    /**
     * @param Index $index
     */
    public function addDeletedIndex(Index $index)
    {
        $this->deletedIndexes[$index->getName()];
    }

    /**
     * @return Index[]
     */
    public function getChangedIndexes()
    {
        return $this->changedIndexes;
    }

    /**
     * @param Index $index
     */
    public function addChangedIndex(Index $index)
    {
        $this->changedIndexes[$index->getName()];
    }

    /**
     * @return ForeignKey[]
     */
    public function getNewForeignKeys()
    {
        return $this->newForeignKeys;
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function addNewForeignKey(ForeignKey $foreignKey)
    {
        $this->newForeignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * @return ForeignKey[]
     */
    public function getDeletedForeignKeys()
    {
        return $this->deletedForeignKeys;
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function addDeletedForeignKey(ForeignKey $foreignKey)
    {
        $this->deletedForeignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * @return ForeignKey[]
     */
    public function getChangedForeignKeys()
    {
        return $this->changedForeignKeys;
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function addChangedForeignKey(ForeignKey $foreignKey)
    {
        $this->changedForeignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * @return string
     */
    public function generateAlterScript()
    {
        $alterScript = '';

        return $alterScript;
    }
}