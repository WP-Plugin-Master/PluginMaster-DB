<?php

namespace PluginMaster\DB;

use PluginMaster\DB\base\BuilderBase;
use PluginMaster\DB\utilities\exceptionHandler;
use PluginMaster\DB\utilities\othersClause;
use PluginMaster\DB\utilities\selectClause;
use PluginMaster\DB\utilities\whereClause;
use PluginMaster\DB\utilities\joinClause;

class DB implements BuilderBase
{

    use whereClause;
    use joinClause;
    use exceptionHandler;
    use othersClause;
    use selectClause;

    protected $table;
    protected $sql;
    protected $table_prefix;
    protected $connection;

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
        $self = (new self());
        $self->table = $self->table_prefix . $name;
        $self->whereValues = [];
        return $self;
    }


    /**
     * @param $closer
     * @return DB|void
     */
    public static function transaction($closer)
    {
        $self = new self;
        try {
            $self->connection->query('START TRANSACTION');

            if ($closer instanceof \Closure) {
                call_user_func($closer, $self);
            }

            $self->connection->query('COMMIT');
            return $self;
        } catch (\Exception $e) {
            $self->connection->query('ROLLBACK');
            return $self->exceptionHandler();
        }
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

        try {
            $insert = $this->connection->query($sql);
            if ($this->connection->last_error) {
                throw new \Exception("Error");
            }
            return $this->connection->insert_id;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }

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

        try {
            $update = $this->connection->query($sql);
            if ($this->connection->last_error) {
                throw new \Exception("Error");
            }
            return $update;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }
    }

    /**
     * @param $where
     * @return false|int
     */
    public function delete()
    {

        $this->sql = "DELETE FROM `" . $this->table . '` ' . $this->whereClause;
        $sql = $this->getPreparedSql();
        try {
            $delete = $this->connection->query($sql);
            if ($delete === false) {
                throw new \Exception("Error");
            }
            return $delete;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }

    }

    /**
     * @return array|object|void|null
     */
    public function first()
    {
        $this->getSelectQuery();
        try {
            $first = $this->connection->get_row($this->getPreparedSql(), OBJECT);
            if ($this->connection->last_error) {
                throw new \Exception("Error");
            }
            return $first;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }
    }

    /**
     * @return string
     */
    private function getSelectQuery()
    {
        $this->sql = "SELECT " . $this->selectQuery . " FROM $this->table " . $this->join . $this->whereClause . $this->groupQuery . $this->orderQuery.$this->limitQuery.$this->offsetQuery;
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
