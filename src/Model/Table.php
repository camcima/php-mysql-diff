<?php

namespace Camcima\MySqlDiff\Model;

class Table
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $ifNotExists;

    /**
     * @var string
     */
    private $definition;

    /**
     * @var string
     */
    private $creationScript;

    /**
     * @var Column[]
     */
    private $columns = [];

    /**
     * @var Column[]
     */
    private $primaryKeys = [];

    /**
     * @var ForeignKey[]
     */
    private $foreignKeys = [];

    /**
     * @var Index[]
     */
    private $indexes = [];

    /**
     * @var string
     */
    private $engine;

    /**
     * @var int
     */
    private $autoIncrement;

    /**
     * @var string
     */
    private $defaultCharset;

    /**
     * @var string
     */
    private $comment;

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
     * @return bool
     */
    public function isIfNotExists()
    {
        return $this->ifNotExists;
    }

    /**
     * @param bool $ifNotExists
     */
    public function setIfNotExists($ifNotExists)
    {
        $this->ifNotExists = $ifNotExists;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getCreationScript()
    {
        return $this->creationScript;
    }

    /**
     * @param string $creationScript
     */
    public function setCreationScript($creationScript)
    {
        $this->creationScript = $creationScript;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param Column[] $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param Column $column
     */
    public function addColumn(Column $column)
    {
        $column->setParentTable($this);
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @param string $columnName
     *
     * @return Column
     */
    public function getColumnByName($columnName)
    {
        if (!isset($this->columns[$columnName])) {
            throw new \RuntimeException(sprintf('Column "%s" not found in table ""!', $columnName, $this->name));
        }

        return $this->columns[$columnName];
    }

    /**
     * @param int $columnOrder
     *
     * @return Column
     */
    public function getColumnByOrder($columnOrder)
    {
        foreach ($this->columns as $column) {
            if ($column->getOrder() == $columnOrder) {
                return $column;
            }
        }

        throw new \RuntimeException(sprintf('Column order "%s" not found in table ""!', $columnOrder, $this->name));
    }

    /**
     * @param string $columnName
     *
     * @return bool
     */
    public function hasColumn($columnName)
    {
        return isset($this->columns[$columnName]);
    }

    /**
     * @return Column[]
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * @param Column $primaryKeyColumn
     */
    public function addPrimaryKey(Column $primaryKeyColumn)
    {
        $this->primaryKeys[] = $primaryKeyColumn;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $foreignKey->setParentTable($this);
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * @param string $foreignKeyName
     *
     * @return ForeignKey
     */
    public function getForeignKeyByName($foreignKeyName)
    {
        if (!isset($this->foreignKeys[$foreignKeyName])) {
            throw new \RuntimeException(sprintf('Foreign key "%s" not found in table ""!', $foreignKeyName, $this->name));
        }

        return $this->foreignKeys[$foreignKeyName];
    }

    /**
     * @param string $foreignKeyName
     *
     * @return bool
     */
    public function hasForeignKey($foreignKeyName)
    {
        return isset($this->foreignKeys[$foreignKeyName]);
    }

    /**
     * @return Index[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param Index $index
     */
    public function addIndex(Index $index)
    {
        $index->setParentTable($this);
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * @param string $indexName
     *
     * @return Index
     */
    public function getIndexByName($indexName)
    {
        if (!isset($this->indexes[$indexName])) {
            throw new \RuntimeException(sprintf('Index "%s" not found in table ""!', $indexName, $this->name));
        }

        return $this->indexes[$indexName];
    }

    /**
     * @param string $indexName
     *
     * @return bool
     */
    public function hasIndex($indexName)
    {
        return isset($this->indexes[$indexName]);
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param string $engine
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return int
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param int $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return string
     */
    public function getDefaultCharset()
    {
        return $this->defaultCharset;
    }

    /**
     * @param string $defaultCharset
     */
    public function setDefaultCharset($defaultCharset)
    {
        $this->defaultCharset = $defaultCharset;
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
     * @return string
     */
    public function generatePrimaryKeyCreationScript()
    {
        if (empty($this->primaryKeys)) {
            return '';
        }

        $primaryKeys = [];
        foreach ($this->primaryKeys as $primaryKeyColumn) {
            if ($primaryKeyColumn->getPrimaryKeyLength()) {
                $primaryKey = sprintf('`%s`(%s)', $primaryKeyColumn->getName(), $primaryKeyColumn->getPrimaryKeyLength());
            } else {
                $primaryKey = sprintf('`%s`', $primaryKeyColumn->getName());
            }

            $primaryKeys[] = $primaryKey;
        }

        return sprintf('PRIMARY KEY (%s)', implode(',', $primaryKeys));
    }

    /**
     * @param bool $ignoreAutoIncrement
     * @param bool $sortKeys
     *
     * @return string
     */
    public function generateCreationScript($ignoreAutoIncrement = false, $sortKeys = true)
    {
        $tableDefinitions = [];

        // Columns
        foreach ($this->columns as $column) {
            $tableDefinitions[] = $column->generateCreationScript();
        }

        // Primary Keys
        if (!empty($this->primaryKeys)) {
            $tableDefinitions[] = $this->generatePrimaryKeyCreationScript();
        }

        // Indexes
        if ($sortKeys) {
            ksort($this->indexes);
        }

        foreach ($this->indexes as $index) {
            $tableDefinitions[] = $index->generateCreationScript();
        }

        // Foreign Keys
        if ($sortKeys) {
            ksort($this->foreignKeys);
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $tableDefinitions[] = $foreignKey->generateCreationScript();
        }

        $tableOptions = [];

        if ($this->ifNotExists) {
            $ifNotExists = ' IF NOT EXISTS';
        } else {
            $ifNotExists = '';
        }

        if ($this->engine) {
            $tableOptions[] = sprintf('ENGINE=%s', $this->engine);
        }

        if ($this->autoIncrement && !$ignoreAutoIncrement) {
            $tableOptions[] = sprintf('AUTO_INCREMENT=%s', $this->autoIncrement);
        }

        if ($this->defaultCharset) {
            $tableOptions[] = sprintf('DEFAULT CHARSET=%s', $this->defaultCharset);
        }

        if ($this->comment) {
            $tableOptions[] = sprintf('COMMENT=\'%s\'', str_replace('\'','\'\'', $this->comment));
        }

        $implodedTableOptions = implode(' ', $tableOptions);

        if (!empty($implodedTableOptions)) {
            $implodedTableOptions = ' ' . $implodedTableOptions;
        }

        return trim(sprintf('CREATE TABLE%s `%s` (%s  %s%s)%s;', $ifNotExists, $this->name, PHP_EOL, implode(',' . PHP_EOL . '  ', $tableDefinitions), PHP_EOL, $implodedTableOptions));
    }
}
