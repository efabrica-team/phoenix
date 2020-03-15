<?php

namespace Phoenix\Database\Adapter\Behavior;

use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;

trait StructureBehavior
{
    public function getStructure(): Structure
    {
        $database = $this->loadDatabase();
        $structure = new Structure();
        $tables = $this->loadTables($database);
        $columns = $this->loadColumns($database);
        $indexes = $this->loadIndexes($database);
        $foreignKeys = $this->loadForeignKeys($database);
        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            $migrationTable = $this->createMigrationTable($table);
            $this->addColumns($migrationTable, isset($columns[$tableName]) ? $columns[$tableName] : []);
            $this->addIndexes($migrationTable, isset($indexes[$tableName]) ? $indexes[$tableName] : []);
            $this->addForeignKeys($migrationTable, isset($foreignKeys[$tableName]) ? $foreignKeys[$tableName] : []);
            $migrationTable->create();
            $structure->update($migrationTable);
        }
        return $structure;
    }

    protected function createMigrationTable(array $table): MigrationTable
    {
        return new MigrationTable($table['table_name'], false);
    }

    protected function addColumns(MigrationTable $migrationTable, array $columns): void
    {
        foreach ($columns as $column) {
            $this->addColumn($migrationTable, $column);
        }
    }

    abstract protected function loadDatabase(): string;

    abstract protected function loadTables(string $database): array;

    abstract protected function loadColumns(string $database): array;

    abstract protected function loadIndexes(string $database): array;

    abstract protected function loadForeignKeys(string $database): array;

    abstract protected function addColumn(MigrationTable $migrationTable, array $column): void;

    private function addIndexes(MigrationTable $migrationTable, array $indexes): void
    {
        foreach ($indexes as $name => $index) {
            $columns = $index['columns'];
            ksort($columns);
            if ($name === 'PRIMARY') {
                $migrationTable->addPrimary($columns);
                continue;
            }
            $migrationTable->addIndex(array_values($columns), $index['type'], $index['method'], $name);
        }
    }

    private function addForeignKeys(MigrationTable $migrationTable, array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $columns = $foreignKey['columns'];
            ksort($columns);
            $referencedColumns = $foreignKey['referenced_columns'];
            ksort($referencedColumns);
            $migrationTable->addForeignKey(
                array_values($columns),
                $foreignKey['referenced_table'],
                array_values($referencedColumns),
                $foreignKey['on_delete'],
                $foreignKey['on_update']
            );
        }
    }
}
