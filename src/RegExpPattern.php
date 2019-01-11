<?php

namespace Camcima\MySqlDiff;

/**
 * Class RegExpPattern.
 */
class RegExpPattern
{
    /**
     * @var array
     */
    private static $columnTypeRegExps = [
        '(?:tiny|small|medium|big)?int(?:\((?<intLength>\d+)\))?(?:\s+unsigned)?',
        'float(?:\s+unsigned)?(?:\((?<floatLength>\d+),(?<floatPrecision>\d+)\))?',
        'binary',
        'real',
        'decimal\((?<decimalLength>\d+),(?<decimalPrecision>\d+)\)(?:\s+unsigned)?',
        'double(?:\((?<doubleLength>\d+),(?<doublePrecision>\d+)\))?(?:\s+unsigned)?',
        '(?:datetime|time|timestamp)(?:\((?<fractionalSeconds>\d+)\))?',
        'date',
        'year\((?<yearLength>\d)\)',
        'geometry',
        '(?:var|nvar)?char\((?<charLength>\d+)\)',
        '(?:var)?binary\((?<binaryLength>\d+)\)',
        '(?:tiny|medium|long)?text',
        '(?:tiny|medium|long)?blob',
        'enum\(.+\)',
        'set\(.+\)',
    ];

    /**
     * @return string
     */
    public static function tables()
    {
        $pattern = '/(?<creationScript>CREATE\s+TABLE\s+(?<ifNotExists>IF NOT EXISTS)?\s*`(?<tableName>\S+)`\s+';
        $pattern .= '\((?<tableDefinition>.+?)\)';
        $pattern .= '(';
        $pattern .= '(?:\s+ENGINE\s*=\s*(?<engine>[^;\s]+))?\s*';
        $pattern .= '|';
        $pattern .= '(?:AUTO_INCREMENT\s*=\s*(?<autoIncrement>\d+))?\s*';
        $pattern .= '|';
        $pattern .= '(?:DEFAULT CHARSET\s*=\s*(?<defaultCharset>[^;\s]+))?\s*';
        $pattern .= '|';
        $pattern .= '(?:\s+ROW_FORMAT\s*=\s*(?<rowFormat>[^;\s]+))?\s*';
        $pattern .= '|';
        $pattern .= '(?:\s+KEY_BLOCK_SIZE\s*=\s*(?<keyBlockSize>[^;\s]+))?\s*';
        $pattern .= '|';
        $pattern .= '(?:COLLATE\s*=\s*.+?)?\s*';
        $pattern .= '|';
        $pattern .= '(?:COMMENT\s*=\s*\'(?<comment>([^\']|\'\')+)\')?\s*';
        $pattern .= ')*';
        $pattern .= ')(?:\/\*.+?\*\/)?\s*';
        $pattern .= ';/';
        $pattern .= 'si'; // modifier

        return $pattern;
    }

    /**
     * @return string
     */
    public static function column()
    {
        $pattern = '/\s*';
        $pattern .= '`(?<columnName>\S+?)`\s+';
        $pattern .= sprintf('(?<columnType>%s)\s*', implode('|', self::$columnTypeRegExps));
        $pattern .= '(?:CHARACTER SET\s+(?<characterSet>\S+))?\s*';
        $pattern .= '(?:COLLATE\s+(?<collate>\S+))?\s*';
        $pattern .= '(?<nullable>NULL|NOT NULL)?\s*';
        $pattern .= '(?<autoIncrement>AUTO_INCREMENT)?\s*';
        $pattern .= '(?:DEFAULT (?<defaultValue>\S+|\'[^\']+\'))?\s*';
        $pattern .= '(?:ON UPDATE (?<onUpdateValue>\S+))?\s*';
        $pattern .= '(?:COMMENT \'(?<comment>([^\']|\'\')+)\')?\s*';
        $pattern .= '(?:,|$)/';
        $pattern .= 'i'; // modifier

        return $pattern;
    }

    /**
     * @return string
     */
    public static function dataType()
    {
        return '/(?<dataType>[^\(\s]+)\s*(?:\([^\)]+\))?\s*(?<unsigned>unsigned)?/i';
    }

    /**
     * @return string
     */
    public static function primaryKey()
    {
        return '/PRIMARY KEY \((?<primaryKey>(?:`[^`]+`\s*(?:\(\d+\))?,?)+)\)/i';
    }

    /**
     * @return string
     */
    public static function foreignKey()
    {
        $pattern = '/CONSTRAINT `(?<name>\S+?)`\s+FOREIGN KEY\s+';
        $pattern .= '\(`(?<column>\S+?)`\)\s+';
        $pattern .= 'REFERENCES\s+`(?<referenceTable>\S+?)`\s*';
        $pattern .= '\(`(?<referenceColumn>\S+?)`\)\s*';
        $pattern .= '(?<onDelete>ON DELETE .+?)?\s*';
        $pattern .= '(?<onUpdate>ON UPDATE .+?)?\s*';
        $pattern .= '(?:,|$)/';
        $pattern .= 'i'; // modifier

        return $pattern;
    }

    /**
     * @return string
     */
    public static function index()
    {
        $pattern = '/\s*';
        $pattern .= '(?<spatial>SPATIAL)?\s*';
        $pattern .= '(?<unique>UNIQUE)?\s*';
        $pattern .= '(?<fullText>FULLTEXT)?\s*';
        $pattern .= 'KEY\s+`(?<name>\S+?)`\s+';
        $pattern .= '\((?<columns>(?:`[^`]+`(?:\(\d+\))?,?)+)\)\s*';
        $pattern .= '(?<options>[^,]+?)?\s*';
        $pattern .= '(?:,|$)/';
        $pattern .= 'i'; // modifier

        return $pattern;
    }

    /**
     * @return string
     */
    public static function indexColumn()
    {
        $pattern = '/^(?<columnName>[^\(]+)\s*';
        $pattern .= '(?:\((?<firstCharacters>\d+)\))?$/';
        $pattern .= 'i'; // modifier

        return $pattern;
    }
}
