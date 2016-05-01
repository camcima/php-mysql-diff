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
        preg_match_all(RegExpPattern::tables(), $sqlScript, $matches);

        $tables = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $name = $matches['tableName'][$i];
            $ifNotExists = $matches['ifNotExists'][$i];
            $definition = $matches['tableDefinition'][$i];
            $creationScript = $matches['creationScript'][$i];
            $engine = $matches['engine'][$i];
            $autoIncrement = $matches['autoIncrement'][$i];
            $defaultCharset = $matches['defaultCharset'][$i];
            $comment = $matches['comment'][$i];

            $table = new Table($name);
            $table->setDefinition(trim($definition));
            $table->setCreationScript(trim($creationScript) . ';');

            if ($ifNotExists) {
                $table->setIfNotExists(true);
            }

            if ($engine) {
                $table->setEngine($engine);
            }

            if ($autoIncrement) {
                $table->setAutoIncrement((int) $autoIncrement);
            }

            if ($defaultCharset) {
                $table->setDefaultCharset($defaultCharset);
            }

            if ($comment) {
                $table->setComment(str_replace('\'\'', '\'', $comment));
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
        preg_match_all(RegExpPattern::column(), $table->getDefinition(), $matches);

        $lastColumn = null;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $columnName = $matches['columnName'][$i];
            $columnType = $matches['columnType'][$i];
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
            $column->setColumnType($columnType);

            preg_match(RegExpPattern::dataType(), $columnType, $dataTypeMatches);
            $dataType = $dataTypeMatches['dataType'];
            $unsigned = isset($dataTypeMatches['unsigned']) && !empty($dataTypeMatches['unsigned']);
            $column->setDataType($dataType);
            $column->setUnsigned($unsigned);

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
                $column->setComment(str_replace('\'\'','\'', $comment));
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
        if (preg_match(RegExpPattern::primaryKey(), $table->getDefinition(), $matches) !== 1) {
            return;
        }

        $primaryKeyNames = explode(',', str_replace('`', '', $matches['primaryKey']));

        foreach ($primaryKeyNames as $primaryKeyName) {
            if (preg_match('/^(?<columnName>[^\(]+)\((?<keyLength>\d+)\)/', $primaryKeyName, $keyMatches)) {
                $columnName = $keyMatches['columnName'];
                $keyLength = $keyMatches['keyLength'];
            } else {
                $columnName = $primaryKeyName;
                $keyLength = null;
            }
            $primaryKeyColumn = $table->getColumnByName(trim($columnName));
            $primaryKeyColumn->setPrimaryKey(true);

            if ($keyLength) {
                $primaryKeyColumn->setPrimaryKeyLength($keyLength);
            }

            $table->addPrimaryKey($primaryKeyColumn);
        }
    }

    /**
     * @param Table $table
     */
    public function parseForeignKeys(Table $table)
    {
        preg_match_all(RegExpPattern::foreignKey(), $table->getDefinition(), $matches);

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
        preg_match_all(RegExpPattern::index(), $table->getDefinition(), $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $indexName = $matches['name'][$i];
            $indexColumnNames = explode(',', str_replace('`', '', $matches['columns'][$i]));
            $indexOptions = $matches['options'][$i];
            $spatial = $matches['spatial'][$i];
            $unique = $matches['unique'][$i];
            $fullText = $matches['fullText'][$i];

            $index = new Index($indexName);

            foreach ($indexColumnNames as $indexColumnDefinition) {
                preg_match(RegExpPattern::indexColumn(), $indexColumnDefinition, $definitionMatch);

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
