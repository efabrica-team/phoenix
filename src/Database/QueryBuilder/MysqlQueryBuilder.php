<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;

class MysqlQueryBuilder implements QueryBuilderInterface
{
    use CommonQueryBuilder;
    
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_INTEGER => 'int(%d)',
        Column::TYPE_BOOLEAN => 'int(%d)',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_UUID => 'varchar(%d)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_CHAR => 'char(%d)',
    ];
    
    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_INTEGER => 11,
        Column::TYPE_BOOLEAN => 1,
        Column::TYPE_UUID => 36,
        Column::TYPE_CHAR => 255,
    ];
    
    /**
     * generates create table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function createTable(Table $table)
    {
        $query = 'CREATE TABLE ' . $this->escapeString($table->getName()) . ' (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column);
        }
        $query .= implode(',', $columns);
        $query .= $this->createPrimaryKey($table);
        $query .= $this->createIndexes($table);
        $query .= $this->createForeignKeys($table);
        $query .= ') DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;';
        return [$query];
    }
    
    /**
     * generates drop table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function dropTable(Table $table)
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }
    
    /**
     * generates alter table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function alterTable(Table $table)
    {
        $queries = [];
        if (!empty($table->getIndexesToDrop())) {
            $queries[] = $this->dropIndexes($table);
        }
        
        foreach ($table->getForeignKeysToDrop() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP FOREIGN KEY ' . $this->escapeString($foreignKey) . ';';
        }
        
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        
        $columns = $table->getColumns();
        unset($columns['id']);
        if (!empty($columns)) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($columns as $column) {
                $columnList[] = 'ADD COLUMN ' . $this->createColumn($column);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        
        if (!empty($table->getIndexes())) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $indexes = [];
            foreach ($table->getIndexes() as $index) {
                $indexes[] = 'ADD ' . $this->createIndex($index, $table);
            }
            $query .= implode(',', $indexes) . ';';
            $queries[] = $query;
        }
        
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }
    
    private function createColumn(Column $column)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column);
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        if ($column->getDefault() !== null) {
            $col .= ' DEFAULT ';
            if ($column->getType() == Column::TYPE_INTEGER) {
                $col .= $column->getDefault();
            } elseif ($column->getType() == Column::TYPE_BOOLEAN) {
                $col .= intval($column->getDefault());
            } else {
                $col .= "'" . $column->getDefault() . "'";
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        
        $col .= $column->isAutoincrement() ? ' AUTO_INCREMENT' : '';
        return $col;
    }
    
    private function createPrimaryKey(Table $table)
    {
        if (empty($table->getPrimaryColumns())) {
            return '';
        }
        
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $primaryKeys[] = $this->escapeString($table->getColumn($name)->getName());
        }
        return ',PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndexes(Table $table)
    {
        if (empty($table->getIndexes())) {
            return '';
        }
        
        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $indexes[] = $this->createIndex($index, $table);
        }
        return ',' . implode(',', $indexes);
    }
    
    private function createIndex(Index $index, Table $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        return $index->getType() . ' ' . $this->escapeString($index->getName()) . ' (' . implode(',', $columns) . ')' . (!$index->getMethod() ? '' : ' ' . $index->getMethod());
    }
    
    public function escapeString($string)
    {
        return '`' . $string . '`';
    }
}