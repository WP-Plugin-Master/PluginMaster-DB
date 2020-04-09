<?php

namespace PluginMaster\DB;

use PluginMaster\DB\base\BuilderBase;

class DB implements BuilderBase
{
    private static $instance;
    public $whereValues = [];
    private $table_prefix;
    private $connection;
    private $table;
    private $whereClause = '';
    private $orderQuery = '';
    private $selectQuery = '*';
    private $groupQuery = '';
    private $closerSession = false;
    private $closerCounter;
    private $sql;

    /**
     * DB constructor.
     */
    public function __construct()
    {
        global $table_prefix, $wpdb;
        $this->table_prefix = $table_prefix;
        $this->connection = $wpdb;
    }

    /**
     * @param $name
     * @return DB
     */
    public static function table($name)
    {
        $self = self::getInstance();
        $self->table = $self->table_prefix . $name;
        $self->whereValues = [];
        return $self;
    }

    /**
     * @return DB
     */
    private static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new DB();
        }

        return self::$instance;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return DB
     */
    public function where($column, $operator = null, $value = null)
    {

        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof \Closure) {
            $this->closerSession = true;
            $this->closerCounter = 1;
            $this->whereClause .= ($this->whereClause ? ' AND (' : ' where (');
            call_user_func($column, self::getInstance());
            $this->whereClause .= ')';
            $this->closerSession = false;
            $this->closerCounter = 0;
        } else {
            $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' AND ' : ' where ');
            $this->whereClause .= '`' . $column . '` ' . $finalOperator . ' %s';
            array_push($this->whereValues, (string)$finalValue);
            $this->closerCounter++;
        }
        return self::getInstance();
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return DB
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof \Closure) {
            $this->closerSession = true;
            $this->closerCounter = 1;
            $this->whereClause .= ($this->whereClause ? ' OR (' : ' where (');
            call_user_func($column, self::getInstance());
            $this->whereClause .= ')';
            $this->closerSession = false;
            $this->closerCounter = 0;
        } else {
            $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' OR ' : ' where ');
            $this->whereClause .= '`' . $column . '` ' . $finalOperator . ' %s';
            array_push($this->whereValues, (string)$finalValue);
            $this->closerCounter++;

        }

        return self::getInstance();
    }

    /**
     * @param $columns
     * @param $direction
     * @return DB
     */
    public function orderBy($columns, $direction)
    {
        $this->orderQuery = " ORDER BY `" . $columns . "` " . $direction;
        return self::getInstance();
    }

    /**
     * @param $fields
     * @return DB
     */
    public function select($fields)
    {
        $this->selectQuery = " " . $fields . " ";
        return self::getInstance();
    }

    /**
     * @param $data
     * @return false|int
     */
    public function insert($data)
    {
        $fields = '';
        $values = '';
        $this->whereValues = [];
        foreach ($data as $key => $value) {

            $fields .= '`' . $key . '`,';
            $values .= '%s,';
            array_push($this->whereValues, $value);

        }

        $this->sql = "INSERT INTO `" . $this->table . "` (" . substr($fields, 0, -1) . ") VALUES(" . substr($values, 0, -1) . ")";

        $sql = $this->getPreparedSql();

        $insert = $this->connection->query($sql);

        return $this->connection->insert_id;
    }

    /**
     * @param $query
     * @param $values
     * @return string|void
     */
    protected function getPreparedSql()
    {
        return $this->connection->prepare($this->sql, $this->whereValues);
    }

    /**
     * @param $data
     * @param $where
     * @return false|int
     */
    public function update($data)
    {
        $fields = '';
        $prevWhereValues = $this->whereValues;
        $this->whereValues = [];
        foreach ($data as $key => $value) {

            $fields .= '`' . $key . '` = %s,';
            array_push($this->whereValues, $value);

        }

        $this->whereValues = array_merge($this->whereValues, $prevWhereValues);

        $this->sql = "UPDATE `" . $this->table . "` SET " . substr($fields, 0, -1) . $this->whereClause;

        $sql = $this->getPreparedSql();

        return $this->connection->query($sql);
    }

    /**
     * @param $where
     * @return false|int
     */
    public function delete()
    {

        $this->sql = "DELETE FROM `" . $this->table . '` ' . $this->whereClause;
        $sql = $this->getPreparedSql();
        return $this->connection->query($sql);
    }

    /**
     * @return array|object|void|null
     */
    public function first()
    {
        $this->getSelectQuery();
        return $this->connection->get_row($this->getPreparedSql(), OBJECT);
    }

    /**
     * @return string
     */
    private function getSelectQuery()
    {
        $this->sql = "SELECT " . $this->selectQuery . " FROM $this->table " . $this->whereClause . $this->groupQuery . $this->orderQuery;
        return $this->sql;
    }

    /**
     * @return array|object|null
     */
    public function get()
    {
        $this->getSelectQuery();
        return $this->connection->get_results($this->getPreparedSql());
    }

    /**
     * @return string
     */
    public function toSql()
    {
        return $this->sql ? $this->sql : $this->getSelectQuery();
    }

}
