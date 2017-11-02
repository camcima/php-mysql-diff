<?php

namespace Camcima\MySqlDiff;

use Camcima\MySqlDiff\Model\DatabaseDiff;

class DifferTest extends AbstractTest
{
    public function testIsDiffingEqualDatabases()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila.sql'));
        $toDatabase = clone $fromDatabase;

        $differ = new Differ();
        $result = $differ->diffDatabases($fromDatabase, $toDatabase);

        $this->assertInstanceOf(DatabaseDiff::class, $result);
        $this->assertEmpty($result->getNewTables());
        $this->assertEmpty($result->getChangedTables());
        $this->assertEmpty($result->getDeletedTables());
    }

    public function testIsDiffingDifferentDatabases()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila_new.sql'));

        $differ = new Differ();
        $result = $differ->diffDatabases($fromDatabase, $toDatabase);

        $this->assertInstanceOf(DatabaseDiff::class, $result);
        $this->assertCount(1, $result->getNewTables());
        $this->assertEquals('test3', $result->getNewTables()[0]->getName());
        $this->assertCount(1, $result->getChangedTables());
        $this->assertEquals('test2', $result->getChangedTables()[0]->getFromTable()->getName());
        $this->assertEquals('test2', $result->getChangedTables()[0]->getToTable()->getName());
        $this->assertCount(1, $result->getDeletedTables());
        $this->assertEquals('test1', $result->getDeletedTables()[0]->getName());
    }

    public function testIsDiffingDifferentDatabasesWithIgnoredTables()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila_new.sql'));

        $differ = new Differ();
        $result = $differ->diffDatabases($fromDatabase, $toDatabase, ['/^test[12]$/']);

        $this->assertInstanceOf(DatabaseDiff::class, $result);
        $this->assertCount(1, $result->getNewTables());
        $this->assertEquals('test3', $result->getNewTables()[0]->getName());
        $this->assertEmpty($result->getChangedTables());
        $this->assertEmpty($result->getDeletedTables());
    }

    public function testIsDiffingChangedTable()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila_new.sql'));

        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);

        $changedTable = $databaseDiff->getChangedTables()[0];

        $differ->diffChangedTable($changedTable);
    }

    public function testIsDiffingChangedComment()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('comment_change1.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('comment_change2.sql'));

        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);

        $this->assertContains('CHANGE COLUMN `field1` `field1` varchar(50) NOT NULL COMMENT \'New Comment\' FIRST;', $differ->generateMigrationScript($databaseDiff));
    }

    public function testIsGeneratingMigrationScript()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('sakila_new.sql'));

        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);

        $result = $differ->generateMigrationScript($databaseDiff);

        $this->assertEquals($this->getDatabaseFixture('sakila_migration.sql'), $result);
    }

    public function testIsDiffingDeletedForeignKeyColumn()
    {
        $parser = new Parser();

        $fromDatabase = $parser->parseDatabase($this->getDatabaseFixture('fk_deleted_column1.sql'));
        $toDatabase = $parser->parseDatabase($this->getDatabaseFixture('fk_deleted_column2.sql'));

        $differ = new Differ();
        $databaseDiff = $differ->diffDatabases($fromDatabase, $toDatabase);

        $this->assertEquals($this->getDatabaseFixture('fk_deleted_column_migration.sql'), $differ->generateMigrationScript($databaseDiff));
    }
}
