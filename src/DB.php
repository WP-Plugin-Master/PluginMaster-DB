<?php

namespace PluginMaster\DB;

use Closure;
use PluginMaster\Contracts\DB\DBInterface;
use PluginMaster\DB\exception\ExceptionHandler;

class DB implements DBInterface
{

    use ExceptionHandler;

    protected string $table;
    protected string $sql;
    protected string $table_prefix;
    protected \wpdb $connection;
    protected array $whereValues = [];
    protected string $whereClause = '';
    protected int $closerCounter;
    protected bool $closerSession = false;
    protected string $selectQuery = ' * ';
    protected string $orderQuery = '';
    protected string $groupQuery = '';
    protected string $limitQuery = '';
    protected string $offsetQuery = '';
    protected string $join;
    protected bool $joinClosure;
    protected string $joinOn = '';


    /**
     * DB constructor.
     */
    public function __construct()
    {
        global $table_prefix, $wpdb;
        $this->table_prefix = $table_prefix;
        $this->connection = $wpdb;
    }

    public static function table(string $name): self
    {
        $self = (new self());
        $self->table = $self->table_prefix . $name;
        $self->whereValues = [];
        return $self;
    }


    /**
     * @param  Closure  $closer
     * @return DB|void
     */
    public static function transaction(Closure $closer): ?self
    {
        $self = new self;
        try {
            $self->connection->query('START TRANSACTION');

            call_user_func($closer, $self);

            $self->connection->query('COMMIT');
            return $self;
        } catch (\Exception $e) {
            $self->connection->query('ROLLBACK');
            $self->exceptionHandler();
        }
    }


    /**
     * @param  string  $fields
     * @return DB
     */
    public function select(string $fields): self
    {
        $this->selectQuery = " " . $fields . " ";
        return $this;
    }


    /**
     * @param  string  $table
     * @param  string|Closure  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return DB
     */
    public function join(string $table, string|Closure $first, ?string $operator = null, ?string $second = null): self
    {
        if ($first instanceof \Closure) {
            $this->joinClosure = true;
            $this->join .= ' INNER JOIN ' . $this->table_prefix . $table . ' ON ';
            call_user_func($first, $this);
            $this->join .= $this->joinOn;
            $this->joinClosure = false;
        } else {
            $this->join .= ' INNER JOIN ' . $this->table_prefix . $table . ' ON ' . $first . ' ' . $operator . ' ' . $second . ' ';
        }
        return $this;
    }

    /**
     * @param  string  $table
     * @param  string|Closure  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return DB
     */
    public function leftJoin(string $table, string|Closure $first, ?string $operator = null, ?string $second = null): self
    {
        if ($first instanceof \Closure) {
            $this->joinClosure = true;
            $this->join .= ' LEFT JOIN ' . $this->table_prefix . $table . ' ON ';
            call_user_func($first, $this);
            $this->join .= $this->joinOn;
            $this->joinClosure = false;
        } else {
            $this->join .= ' LEFT JOIN ' . $this->table_prefix . $table . ' ON ' . $first . ' ' . $operator . ' ' . $second . ' ';
        }

        return $this;
    }

    /**
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return mixed
     */
    public function on(string $first, string $operator, string $second): self
    {
        $this->joinOn .= ($this->joinOn ? ' AND ' : '') . $first . ' ' . $operator . ' ' . $second;
        return $this;
    }

    /**
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return mixed
     */
    public function orOn(string $first, string $operator, string $second): self
    {
        $this->joinOn .= ($this->joinOn ? ' OR ' : '') . $first . ' ' . $operator . ' ' . $second;
        return $this;
    }

    /**
     * @param  string  $column
     * @param  string|null  $operator
     * @param  null  $value
     * @return mixed
     */
    public function onWhere(string $column, ?string $operator = null, mixed $value = null): self
    {
        if (!$value) {
            $value = $operator;
            $operator = ' = ';
        }

        $this->joinOn .= ($this->joinOn ? ' AND ' : '') . $column . ' ' . $operator . ' %s ';
        $this->whereValues[] = (string) $value;
        return $this;
    }


    /**
     * @param  string|Closure  $column
     * @param  string|null  $operator
     * @param  null  $value
     * @return DB
     */
    public function where(string|Closure $column, ?string $operator = null, mixed $value = null): self
    {
        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof Closure) {
            $this->closerSession = true;
            $this->closerCounter = 1;
            $this->whereClause .= ($this->whereClause ? ' AND (' : ' where (');
            call_user_func($column, $this);
            $this->whereClause .= ')';
            $this->closerSession = false;
            $this->closerCounter = 0;
        } else {
            $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' AND ' : ' where ');
            $this->whereClause .= '`' . $column . '` ' . $finalOperator . ' %s';
            $this->whereValues[] = (string) $finalValue;
            $this->closerCounter++;
        }

        return $this;
    }

    /**
     * @param  string  $column
     * @param  string|null  $operator
     * @param  null  $value
     * @return DB
     */
    public function orWhere(string|Closure $column, ?string $operator = null, mixed $value = null): self
    {
        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof Closure) {
            $this->closerSession = true;
            $this->closerCounter = 1;
            $this->whereClause .= ($this->whereClause ? ' OR (' : ' where (');
            call_user_func($column, $this);
            $this->whereClause .= ')';
            $this->closerSession = false;
            $this->closerCounter = 0;
        } else {
            $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' OR ' : ' where ');
            $this->whereClause .= '`' . $column . '` ' . $finalOperator . ' %s';
            $this->whereValues[] = (string) $finalValue;
            $this->closerCounter++;
        }

        return $this;
    }


    /**
     * @param $query
     */
    public function whereRaw($query)
    {
        $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? ' ' : ($this->whereClause ? ' AND ' : ' where ');
        $this->whereClause .= $query;
    }

    /**
     * @param $query
     */
    public function orWhereRaw($query)
    {
        $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? ' ' : ($this->whereClause ? ' OR ' : ' where ');
        $this->whereClause .= $query;
    }


    /**
     * @param  string|int  $number
     * @return $this
     */
    public function limit(string|int $number): self
    {
        $this->limitQuery = " LIMIT $number ";
        return $this;
    }


    /**
     * @param  string|int  $number
     * @return DB
     */
    public function offset(string|int $number): self
    {
        $this->offsetQuery = " OFFSET $number ";
        return $this;
    }


    /**
     * @param  string  $columns  | columns with comma separator
     * @param  string  $direction
     * @return DB
     */
    public function orderBy(string $columns, string $direction): self
    {
        $this->orderQuery = " ORDER BY " . $columns . " " . $direction;
        return $this;
    }

    /**
     * @param  string|int  $column
     * @return DB
     */
    public function groupBy(string|int $column): self
    {
        $this->orderQuery = " GROUP BY " . $column . " ";
        return $this;
    }


    /**
     * @param  array  $data
     * @return int|null
     */
    public function insert(array $data): ?int
    {
        $this->buildInsertQuery($data);
        $sql = $this->getPreparedSql();

        try {
            $this->connection->query($sql);
            if ($this->connection->last_error) {
                throw new \Exception("Error");
            }
            return $this->connection->insert_id;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }

    }


    /**
     * @param  array  $data
     */
    private function buildInsertQuery(array $data)
    {
        $fields = '';
        $values = '';
        $this->whereValues = [];
        foreach ($data as $key => $value) {
            $fields .= '`' . $key . '`,';
            $values .= '%s,';
            $this->whereValues[] = $value;
        }

        $this->sql = "INSERT INTO `" . $this->table . "` (" . substr($fields, 0, -1) . ") VALUES(" . substr($values, 0, -1) . ")";
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
     * @param  array  $data
     * @return bool|int|null
     */
    public function update(array $data): bool|int|null
    {
        $this->buildUpdateQuery($data);

        $sql = $this->getPreparedSql();

        try {
            $update = $this->connection->query($sql);
            if ($this->connection->last_error) {
                throw new \Exception("Error to update table");
            }
            return $update;
        } catch (\Exception $e) {
            $this->exceptionHandler();
        }
    }


    /**
     * @param  array  $data
     */
    private function buildUpdateQuery(array $data): void
    {
        $fields = '';
        $prevWhereValues = $this->whereValues;
        $this->whereValues = [];
        foreach ($data as $key => $value) {
            $fields .= '`' . $key . '` = %s,';
            $this->whereValues[] = $value;
        }

        $this->whereValues = array_merge($this->whereValues, $prevWhereValues);

        $this->sql = "UPDATE `" . $this->table . "` SET " . substr($fields, 0, -1) . $this->whereClause;
    }


    /**
     * @return bool|int|null
     */
    public function delete(): bool|int|null
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
     * @return object|null
     */
    public function first(): object|null
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

        return null;
    }

    /**
     * @return string
     */
    private function getSelectQuery()
    {
        $this->sql = "SELECT " . $this->selectQuery . " FROM $this->table " . $this->join . $this->whereClause . $this->groupQuery . $this->orderQuery . $this->limitQuery . $this->offsetQuery;
        return $this->sql;
    }

    /**
     * @return array|object|null
     */
    public function get(): array|object|null
    {
        $this->getSelectQuery();
        return $this->connection->get_results($this->getPreparedSql());
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->sql ? $this->sql : $this->getSelectQuery();
    }
}
