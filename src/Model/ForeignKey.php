<?php

namespace Camcima\MySqlDiff\Model;

class ForeignKey
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Table
     */
    private $parentTable;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $referenceTableName;

    /**
     * @var string
     */
    private $referenceColumnName;

    /**
     * @var string
     */
    private $onDeleteClause;

    /**
     * @var string
     */
    private $onUpdateClause;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Table
     */
    public function getParentTable()
    {
        return $this->parentTable;
    }

    /**
     * @param Table $parentTable
     */
    public function setParentTable($parentTable)
    {
        $this->parentTable = $parentTable;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return string
     */
    public function getReferenceTableName()
    {
        return $this->referenceTableName;
    }

    /**
     * @param string $referenceTableName
     */
    public function setReferenceTableName($referenceTableName)
    {
        $this->referenceTableName = $referenceTableName;
    }

    /**
     * @return string
     */
    public function getReferenceColumnName()
    {
        return $this->referenceColumnName;
    }

    /**
     * @param string $referenceColumnName
     */
    public function setReferenceColumnName($referenceColumnName)
    {
        $this->referenceColumnName = $referenceColumnName;
    }

    /**
     * @return string
     */
    public function getOnDeleteClause()
    {
        return $this->onDeleteClause;
    }

    /**
     * @param string $onDeleteClause
     */
    public function setOnDeleteClause($onDeleteClause)
    {
        $this->onDeleteClause = $onDeleteClause;
    }

    /**
     * @return string
     */
    public function getOnUpdateClause()
    {
        return $this->onUpdateClause;
    }

    /**
     * @param string $onUpdateClause
     */
    public function setOnUpdateClause($onUpdateClause)
    {
        $this->onUpdateClause = $onUpdateClause;
    }

    /**
     * @return string
     */
    public function generateCreationScript()
    {
        $foreignKeyOptions = [];

        if (!empty($this->onDeleteClause)) {
            $foreignKeyOptions[] = $this->onDeleteClause;
        }

        if (!empty($this->onUpdateClause)) {
            $foreignKeyOptions[] = $this->onUpdateClause;
        }

        return trim(sprintf('CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) %s', $this->name, $this->columnName, $this->referenceTableName, $this->referenceColumnName, implode(' ', $foreignKeyOptions)));
    }
}
