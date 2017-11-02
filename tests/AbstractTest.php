<?php

namespace Camcima\MySqlDiff;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $filename
     *
     * @return string
     */
    protected function getDatabaseFixture($filename)
    {
        return preg_replace("/\r?\n/", PHP_EOL, file_get_contents(__DIR__ . '/fixtures/' . $filename));
    }
}
