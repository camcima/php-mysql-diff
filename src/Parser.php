<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\Column;
use Camcima\MySqlDiff\Model\Database;
use Camcima\MySqlDiff\Model\ForeignKey;
use Camcima\MySqlDiff\Model\Index;
use Camcima\MySqlDiff\Model\IndexColumn;
use Camcima\MySqlDiff\Model\Table;

/**
 * Class Parser.
 */
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

        $tables = $this->parseTables($this->convertStringsToBase64($sqlScript));

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
        $loopCounter = count($matches[0]);
        for ($i = 0; $i < $loopCounter; $i++) {
            $name = $matches['tableName'][$i];
            $ifNotExists = $matches['ifNotExists'][$i];
            $definition = $this->convertStringsFromBase64($matches['tableDefinition'][$i]);
            $creationScript = $this->convertStringsFromBase64($matches['creationScript'][$i]);
            $engine = $matches['engine'][$i];
            $autoIncrement = $matches['autoIncrement'][$i];
            $defaultCharset = $matches['defaultCharset'][$i];
            $comment = base64_decode($matches['comment'][$i]);
            $rowFormat = $matches['rowFormat'][$i];
            $keyBlockSize = $matches['keyBlockSize'][$i];

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

            if ($rowFormat) {
                $table->setRowFormat($rowFormat);
            }

            if ($keyBlockSize) {
                $table->setKeyBlockSize($keyBlockSize);
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
        $loopCounter = count($matches[0]);
        for ($i = 0; $i < $loopCounter; $i++) {
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
            $fractionalSeconds = $matches['fractionalSeconds'][$i];
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

            $column->setLength($this->getColumnLength($intLength, $decimalLength, $doubleLength, $floatLength, $charLength, $binaryLength, $yearLength, $fractionalSeconds));
            $column->setPrecision($this->getColumnPrecision($decimalPrecision, $doublePrecision, $floatPrecision));
            $column->setNullable($nullable !== 'NOT NULL');
            $column->setAutoIncrement(!empty($autoIncrement));

            if (!empty($defaultValue)) {
                $column->setDefaultValue($defaultValue);
            }

            if (!empty($onUpdateValue)) {
                $column->setOnUpdateValue($onUpdateValue);
            }

            if (!empty($comment)) {
                $column->setComment(str_replace('\'\'', '\'', $comment));
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

        $loopCounter = count($matches[0]);
        for ($i = 0; $i < $loopCounter; $i++) {
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

        $loopCounter = count($matches[0]);
        for ($i = 0; $i < $loopCounter; $i++) {
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
     * @param int $fractionalSeconds
     *
     * @return int|null
     */
    private function getColumnLength($intLength, $decimalLength, $doubleLength, $floatLength, $charLength, $binaryLength, $yearLength, $fractionalSeconds)
    {
        if (!empty($intLength)) {
            return (int) $intLength;
        }
        if (!empty($decimalLength)) {
            return (int) $decimalLength;
        }
        if (!empty($doubleLength)) {
            return (int) $doubleLength;
        }
        if (!empty($floatLength)) {
            return (int) $floatLength;
        }
        if (!empty($charLength)) {
            return (int) $charLength;
        }
        if (!empty($binaryLength)) {
            return (int) $binaryLength;
        }
        if (!empty($yearLength)) {
            return (int) $yearLength;
        }
        if (!empty($fractionalSeconds)) {
            return (int) $fractionalSeconds;
        }

        return;
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
        }
        if (!empty($doublePrecision)) {
            return (int) $doublePrecision;
        }
        if (!empty($floatPrecision)) {
            return (int) $floatPrecision;
        }

        return;
    }

    public function convertStringsToBase64($sqlScript)
    {
        $sqlScript = preg_replace_callback('/DEFAULT\s*\'(?<defaultValue>[^\']+)\'/', function ($matches) {
            return sprintf('DEFAULT \'%s\'', base64_encode($matches['defaultValue']));
        }, $sqlScript);

        $sqlScript = preg_replace_callback('/COMMENT\s*\'(?<comment>[^\']+)\'/', function ($matches) {
            return sprintf('COMMENT \'%s\'', base64_encode($matches['comment']));
        }, $sqlScript);

        $sqlScript = preg_replace_callback('/COMMENT\s*=\s*\'(?<comment>([^\']|\'\')+)\'/', function ($matches) {
            return sprintf('COMMENT=\'%s\'', base64_encode($matches['comment']));
        }, $sqlScript);

        return $sqlScript;
    }

    public function convertStringsFromBase64($sqlScript)
    {
        $sqlScript = preg_replace_callback('/DEFAULT\s*\'(?<defaultValue>[^\']+)\'/', function ($matches) {
            return sprintf('DEFAULT \'%s\'', base64_decode($matches['defaultValue']));
        }, $sqlScript);

        $sqlScript = preg_replace_callback('/COMMENT\s*\'(?<comment>[^\']+)\'/', function ($matches) {
            return sprintf('COMMENT \'%s\'', base64_decode($matches['comment']));
        }, $sqlScript);

        $sqlScript = preg_replace_callback('/COMMENT\s*=\s*\'(?<comment>([^\']|\'\')+)\'/', function ($matches) {
            return sprintf('COMMENT=\'%s\'', base64_decode($matches['comment']));
        }, $sqlScript);

        return $sqlScript;
    }
}
