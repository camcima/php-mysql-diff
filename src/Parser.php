<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\Column;
use Camcima\MySqlDiff\Model\Database;
use Camcima\MySqlDiff\Model\ForeignKey;
use Camcima\MySqlDiff\Model\Index;
use Camcima\MySqlDiff\Model\IndexColumn;
use Camcima\MySqlDiff\Model\Table;

class Parser
{
    const REGEXP_TABLES = '/(?<creationScript>CREATE\s+TABLE\s+`(?<tableName>\S+)`\s+\((?<tableDefinition>[^;]+)\)(?:\s+ENGINE=(?<engine>\S+))?\s*(?:AUTO_INCREMENT=(?<autoIncrement>\d+))?\s*(?:DEFAULT CHARSET=(?<defaultCharset>\S+))?\s*;)/s';
    const REGEXP_COLUMN = '/\s*`(?<columnName>\S+?)`\s+(?<dataType>(?:tiny|small|medium|big)?int\((?<intLength>\d+)\)(?:\s+unsigned)?|float(?:\s+unsigned)?(?:\((?<floatLength>\d+),(?<floatPrecision>\d+)\))?|binary|real|decimal\((?<decimalLength>\d+),(?<decimalPrecision>\d+)\)|double(?:\((?<doubleLength>\d+),(?<doublePrecision>\d+)\))?|datetime|date|time|timestamp|year\((?<yearLength>\d)\)|geometry|(?:var|nvar)?char\((?<charLength>\d+)\)|(?:var)?binary\((?<binaryLength>\d+)\)|(?:tiny|medium|long)?text|(?:tiny|medium|long)?blob|enum\(.+\)|set\(.+\))\s*(?:CHARACTER SET\s+(?<characterSet>\S+))?\s*(?:COLLATE\s+(?<collate>\S+))?\s*(?<nullable>NULL|NOT NULL)?\s*(?<autoIncrement>AUTO_INCREMENT)?\s*(?:DEFAULT (?<defaultValue>\S+|\'[^\']+\'))?\s*(?:ON UPDATE (?<onUpdateValue>\S+))?\s*(?:COMMENT \'(?<comment>[^\']+)\')?\s*(?:,|$)/';
    const REGEXP_PRIMARY_KEY = '/PRIMARY KEY \((?<primaryKey>.+?)\)/';
    const REGEXP_FOREIGN_KEY = '/CONSTRAINT `(?<name>\S+?)`\s+FOREIGN KEY\s+\(`(?<column>\S+?)`\)\s+REFERENCES\s+`(?<referenceTable>\S+?)`\s*\(`(?<referenceColumn>\S+?)`\)\s*(?<onDelete>ON DELETE .+?)?\s*(?<onUpdate>ON UPDATE .+?)?\s*(?:,|$)/';
    const REGEXP_INDEX = '/\s*(?<spatial>SPATIAL)?\s*(?<unique>UNIQUE)?\s*(?<fullText>FULLTEXT)?\s*KEY\s+`(?<name>\S+?)`\s+\((?<columns>(?:`[^`]+`(?:\(\d+\))?,?)+)\)\s*(?<options>[^,]+?)?\s*(?:,|$)/';
    const REGEXP_INDEX_COLUMN = '/^(?<columnName>[^\(]+)\s*(?:\((?<firstCharacters>\d+)\))?$/';

    /**
     * @param string $sqlScript
     *
     * @return Database
     */
    public function parseDatabase($sqlScript)
    {
        $database = new Database();

        $tables = $this->parseTables($sqlScript);

        foreach ($tables as $table) {
            $this->parseTableDefinition($table);

            $database->addTable($table);
        }

        return $database;
    }

    /**
     * @param string $sqlScript
     *
     * @return Table[]
     */
    public function parseTables($sqlScript)
    {
        preg_match_all(self::REGEXP_TABLES, $sqlScript, $matches);

        $tables = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $name = $matches['tableName'][$i];
            $definition = $matches['tableDefinition'][$i];
            $creationScript = $matches['creationScript'][$i];
            $engine = $matches['engine'][$i];
            $autoIncrement = $matches['autoIncrement'][$i];
            $defaultCharset = $matches['defaultCharset'][$i];

            $table = new Table($name);
            $table->setDefinition(trim($definition));
            $table->setCreationScript(trim($creationScript));

            if ($engine) {
                $table->setEngine($engine);
            }

            if ($autoIncrement) {
                $table->setAutoIncrement((int) $autoIncrement);
            }

            if ($defaultCharset) {
                $table->setDefaultCharset($defaultCharset);
            }

            $tables[$name] = $table;
        }

        return $tables;
    }

    /**
     * @param Table $table
     */
    public function parseTableDefinition(Table $table)
    {
        $this->parseColumns($table);
        $this->parsePrimaryKey($table);
        $this->parseForeignKeys($table);
        $this->parseIndexes($table);
    }

    /**
     * @param Table $table
     */
    public function parseColumns(Table $table)
    {
        preg_match_all(self::REGEXP_COLUMN, $table->getDefinition(), $matches);

        $lastColumn = null;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $columnName = $matches['columnName'][$i];
            $dataType = $matches['dataType'][$i];
            $intLength = $matches['intLength'][$i];
            $decimalLength = $matches['decimalLength'][$i];
            $doubleLength = $matches['doubleLength'][$i];
            $floatLength = $matches['floatLength'][$i];
            $charLength = $matches['charLength'][$i];
            $binaryLength = $matches['binaryLength'][$i];
            $yearLength = $matches['yearLength'][$i];
            $decimalPrecision = $matches['decimalPrecision'][$i];
            $doublePrecision = $matches['doublePrecision'][$i];
            $floatPrecision = $matches['floatPrecision'][$i];
            $nullable = $matches['nullable'][$i];
            $autoIncrement = $matches['autoIncrement'][$i];
            $defaultValue = $matches['defaultValue'][$i];
            $onUpdateValue = $matches['onUpdateValue'][$i];
            $comment = $matches['comment'][$i];
            $characterSet = $matches['characterSet'][$i];
            $collate = $matches['collate'][$i];

            $column = new Column($columnName);
            $column->setDataType($dataType);
            $column->setLength($this->getColumnLength($intLength, $decimalLength, $doubleLength, $floatLength, $charLength, $binaryLength, $yearLength));
            $column->setPrecision($this->getColumnPrecision($decimalPrecision, $doublePrecision, $floatPrecision));
            $column->setNullable($nullable != 'NOT NULL');
            $column->setAutoIncrement(!empty($autoIncrement));

            if (!empty($defaultValue)) {
                $column->setDefaultValue($defaultValue);
            }

            if (!empty($onUpdateValue)) {
                $column->setOnUpdateValue($onUpdateValue);
            }

            if (!empty($comment)) {
                $column->setComment($comment);
            }

            if (!empty($characterSet)) {
                $column->setCharacterSet($characterSet);
            }

            if (!empty($collate)) {
                $column->setCollate($collate);
            }

            $column->setPrimaryKey(false);

            if ($lastColumn instanceof Column) {
                $column->setPreviousColumn($lastColumn);
                $lastColumn->setNextColumn($column);
            }

            $column->setOrder($i);

            $table->addColumn($column);
            $lastColumn = $column;
        }
    }

    /**
     * @param Table $table
     */
    public function parsePrimaryKey(Table $table)
    {
        if (preg_match(self::REGEXP_PRIMARY_KEY, $table->getDefinition(), $matches) !== 1) {
            return;
        }

        $primaryKeyNames = explode(',', str_replace('`', '', $matches['primaryKey']));

        foreach ($primaryKeyNames as $primaryKeyName) {
            $primaryKeyColumn = $table->getColumnByName(trim($primaryKeyName));
            $primaryKeyColumn->setPrimaryKey(true);
            $table->addPrimaryKey($primaryKeyColumn);
        }
    }

    /**
     * @param Table $table
     */
    public function parseForeignKeys(Table $table)
    {
        preg_match_all(self::REGEXP_FOREIGN_KEY, $table->getDefinition(), $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $name = $matches['name'][$i];
            $columnName = $matches['column'][$i];
            $referenceTableName = $matches['referenceTable'][$i];
            $referenceColumnName = $matches['referenceColumn'][$i];
            $onDeleteClause = $matches['onDelete'][$i];
            $onUpdateClause = $matches['onUpdate'][$i];

            $foreignKey = new ForeignKey($name);
            $foreignKey->setColumnName($columnName);
            $foreignKey->setReferenceTableName($referenceTableName);
            $foreignKey->setReferenceColumnName($referenceColumnName);

            if (!empty($onDeleteClause)) {
                $foreignKey->setOnDeleteClause($onDeleteClause);
            }

            if (!empty($onUpdateClause)) {
                $foreignKey->setOnUpdateClause($onUpdateClause);
            }

            $table->addForeignKey($foreignKey);
        }
    }

    /**
     * @param Table $table
     */
    public function parseIndexes(Table $table)
    {
        preg_match_all(self::REGEXP_INDEX, $table->getDefinition(), $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $indexName = $matches['name'][$i];
            $indexColumnNames = explode(',', str_replace('`', '', $matches['columns'][$i]));
            $indexOptions = $matches['options'][$i];
            $spatial = $matches['spatial'][$i];
            $unique = $matches['unique'][$i];
            $fullText = $matches['fullText'][$i];

            $index = new Index($indexName);

            foreach ($indexColumnNames as $indexColumnDefinition) {
                preg_match(self::REGEXP_INDEX_COLUMN, $indexColumnDefinition, $definitionMatch);

                $indexColumnName = $definitionMatch['columnName'];

                $indexFirstCharacters = null;
                if (isset($definitionMatch['firstCharacters']) && !empty($definitionMatch['firstCharacters'])) {
                    $indexFirstCharacters = (int) $definitionMatch['firstCharacters'];
                }

                $column = $table->getColumnByName(trim($indexColumnName));
                $index->addIndexColumn(new IndexColumn($column, $indexFirstCharacters));
            }

            $index->setUnique(!empty($unique));
            $index->setSpatial(!empty($spatial));
            $index->setFulltext(!empty($fullText));

            if (!empty($indexOptions)) {
                $index->setOptions(trim($indexOptions));
            }

            $table->addIndex($index);
        }
    }

    /**
     * @param int $intLength
     * @param int $decimalLength
     * @param int $doubleLength
     * @param int $floatLength
     * @param int $charLength
     * @param int $binaryLength
     * @param int $yearLength
     *
     * @return int|null
     */
    private function getColumnLength($intLength, $decimalLength, $doubleLength, $floatLength, $charLength, $binaryLength, $yearLength)
    {
        if (!empty($intLength)) {
            return (int) $intLength;
        } elseif (!empty($decimalLength)) {
            return (int) $decimalLength;
        } elseif (!empty($doubleLength)) {
            return (int) $doubleLength;
        } elseif (!empty($floatLength)) {
            return (int) $floatLength;
        } elseif (!empty($charLength)) {
            return (int) $charLength;
        } elseif (!empty($binaryLength)) {
            return (int) $binaryLength;
        } elseif (!empty($yearLength)) {
            return (int) $yearLength;
        } else {
            return;
        }
    }

    /**
     * @param int $decimalPrecision
     * @param int $doublePrecision
     * @param int $floatPrecision
     *
     * @return int|null
     */
    private function getColumnPrecision($decimalPrecision, $doublePrecision, $floatPrecision)
    {
        if (!empty($decimalPrecision)) {
            return (int) $decimalPrecision;
        } elseif (!empty($doublePrecision)) {
            return (int) $doublePrecision;
        } elseif (!empty($floatPrecision)) {
            return (int) $floatPrecision;
        } else {
            return;
        }
    }
}
