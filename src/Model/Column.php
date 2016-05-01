<?php

namespace Camcima\MySqlDiff\Model;

class Column
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
    private $columnType;

    /**
     * @var string
     */
    private $dataType;

    /**
     * @var bool
     */
    private $unsigned;

    /**
     * @var string
     */
    private $characterSet;

    /**
     * @var string
     */
    private $collate;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var bool
     */
    private $nullable;

    /**
     * @var bool
     */
    private $autoIncrement;

    /**
     * @var bool
     */
    private $primaryKey;

    /**
     * @var int
     */
    private $primaryKeyLength;

    /**
     * @var string
     */
    private $defaultValue;

    /**
     * @var string
     */
    private $onUpdateValue;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var Column
     */
    private $previousColumn;

    /**
     * @var Column
     */
    private $nextColumn;

    /**
     * @var int
     */
    private $order;

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
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * @param string $columnType
     */
    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return bool
     */
    public function isUnsigned()
    {
        return $this->unsigned;
    }

    /**
     * @param bool $unsigned
     */
    public function setUnsigned($unsigned)
    {
        $this->unsigned = $unsigned;
    }

    /**
     * @return string
     */
    public function getCharacterSet()
    {
        return $this->characterSet;
    }

    /**
     * @param string $characterSet
     */
    public function setCharacterSet($characterSet)
    {
        $this->characterSet = $characterSet;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param bool $nullable
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param bool $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return int
     */
    public function getPrimaryKeyLength()
    {
        return $this->primaryKeyLength;
    }

    /**
     * @param int $primaryKeyLength
     */
    public function setPrimaryKeyLength($primaryKeyLength)
    {
        $this->primaryKeyLength = $primaryKeyLength;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function getOnUpdateValue()
    {
        return $this->onUpdateValue;
    }

    /**
     * @param string $onUpdateValue
     */
    public function setOnUpdateValue($onUpdateValue)
    {
        $this->onUpdateValue = $onUpdateValue;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return Column
     */
    public function getPreviousColumn()
    {
        return $this->previousColumn;
    }

    /**
     * @param Column $previousColumn
     */
    public function setPreviousColumn(Column $previousColumn)
    {
        $this->previousColumn = $previousColumn;
    }

    /**
     * @return Column
     */
    public function getNextColumn()
    {
        return $this->nextColumn;
    }

    /**
     * @param Column $nextColumn
     */
    public function setNextColumn(Column $nextColumn)
    {
        $this->nextColumn = $nextColumn;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function generateCreationScript()
    {
        $columnOptions = [];

        if ($this->characterSet) {
            $columnOptions[] = sprintf('CHARACTER SET %s', $this->characterSet);
        }

        if ($this->collate) {
            $columnOptions[] = sprintf('COLLATE %s', $this->collate);
        }

        if (!$this->nullable) {
            $columnOptions[] = 'NOT NULL';
        } elseif ($this->columnType == 'timestamp') {
            $columnOptions[] = 'NULL';
        }

        if ($this->autoIncrement) {
            $columnOptions[] = 'AUTO_INCREMENT';
        }

        if (!empty($this->defaultValue)) {
            $columnOptions[] = sprintf('DEFAULT %s', $this->defaultValue);
        }

        if (!empty($this->onUpdateValue)) {
            $columnOptions[] = sprintf('ON UPDATE %s', $this->onUpdateValue);
        }

        if (!empty($this->comment)) {
            $columnOptions[] = sprintf('COMMENT \'%s\'', str_replace('\'','\'\'', $this->comment));
        }

        return trim(sprintf('`%s` %s %s', $this->name, $this->columnType, implode(' ', $columnOptions)));
    }
}
