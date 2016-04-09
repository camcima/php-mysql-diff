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
     * @var IndexColumn[]
     */
    private $indexColumns;

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
    public function getIndexColumns()
    {
        return $this->indexColumns;
    }

    /**
     * @param Column[] $indexColumns
     */
    public function setIndexColumns($indexColumns)
    {
        $this->indexColumns = $indexColumns;
    }

    /**
     * @param IndexColumn $indexColumn
     */
    public function addIndexColumn(IndexColumn $indexColumn)
    {
        $this->indexColumns[] = $indexColumn;
    }

    /**
     * @param $columnName
     *
     * @return IndexColumn
     */
    public function getIndexColumnByColumnName($columnName)
    {
        foreach ($this->indexColumns as $indexColumn) {
            if ($indexColumn->getColumn()->getName() == $columnName) {
                return $indexColumn;
            }
        }

        throw new \RuntimeException(sprintf('Index column "%s" not found!', $columnName));
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
        foreach ($this->indexColumns as $indexColumn) {
            $firstCharacters = '';
            if ($indexColumn->getIndexFirstCharacters()) {
                $firstCharacters = sprintf('(%s)', $indexColumn->getIndexFirstCharacters());
            }

            $indexColumns[] = sprintf('`%s`%s', $indexColumn->getColumn()->getName(), $firstCharacters);
        }

        $indexOptions = '';
        if (!empty($this->options)) {
            $indexOptions = ' ' . $this->options;
        }

        return trim(sprintf('%sKEY `%s` (%s)%s', $indexType, $this->name, implode(',', $indexColumns), $indexOptions));
    }
}
