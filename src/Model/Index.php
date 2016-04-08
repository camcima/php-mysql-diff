<?php

namespace Camcima\MySqlDiff\Model;

class Index
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
     * @var Column[]
     */
    private $columns;

    /**
     * @var bool
     */
    private $unique;

    /**
     * @var bool
     */
    private $spatial;

    /**
     * @var bool
     */
    private $fulltext;

    /**
     * @var string
     */
    private $options;

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
        $this->columns[] = $column;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return bool
     */
    public function isSpatial()
    {
        return $this->spatial;
    }

    /**
     * @param bool $spatial
     */
    public function setSpatial($spatial)
    {
        $this->spatial = $spatial;
    }

    /**
     * @return bool
     */
    public function isFulltext()
    {
        return $this->fulltext;
    }

    /**
     * @param bool $fulltext
     */
    public function setFulltext($fulltext)
    {
        $this->fulltext = $fulltext;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function generateCreationScript()
    {
        $indexType = '';
        if ($this->spatial) {
            $indexType = 'SPATIAL';
        } elseif ($this->unique) {
            $indexType = 'UNIQUE';
        } elseif ($this->fulltext) {
            $indexType = 'FULLTEXT';
        }

        if (!empty($indexType)) {
            $indexType .= ' ';
        }

        $indexColumns = [];
        foreach ($this->columns as $column) {
            $indexColumns[] = sprintf('`%s`', $column->getName());
        }

        $indexOptions = '';
        if (!empty($this->options)) {
            $indexOptions = ' ' . $this->options;
        }

        return trim(sprintf('%sKEY `%s` (%s)%s', $indexType, $this->name, implode(',', $indexColumns), $indexOptions));
    }
}