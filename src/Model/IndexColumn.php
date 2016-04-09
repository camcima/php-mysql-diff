<?php

namespace Camcima\MySqlDiff\Model;

class IndexColumn
{
    /**
     * @var Column
     */
    private $column;

    /**
     * @var int
     */
    private $indexFirstCharacters;

    /**
     * @param Column $column
     * @param int $indexFirstCharacters
     */
    public function __construct(Column $column, $indexFirstCharacters = null)
    {
        $this->column = $column;
        $this->indexFirstCharacters = $indexFirstCharacters;
    }

    /**
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return int
     */
    public function getIndexFirstCharacters()
    {
        return $this->indexFirstCharacters;
    }
}
