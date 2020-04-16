<?php


namespace PluginMaster\DB\utilities;



trait joinClause
{


    protected $join;
    protected $joinClosure;
    protected $joinOn = '';

    /**
     * @param $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @return mixed|DB
     */
    public function join($table, $first, $operator = null, $second = null)
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
     * @param $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @return mixed|DB
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
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
     * @param $first
     * @param $operator
     * @param $second
     * @return mixed
     */
    public function on($first, $operator, $second)
    {
        $this->joinOn .= ($this->joinOn ? ' AND ' : '') . $first . ' ' . $operator . ' ' . $second;
        return $this;
    }

    /**
     * @param $first
     * @param $operator
     * @param $second
     * @return mixed
     */
    public function orOn($first, $operator, $second)
    {
        $this->joinOn .= ($this->joinOn ? ' OR ' : '') . $first . ' ' . $operator . ' ' . $second;
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return mixed
     */
    public function onWhere($column, $operator = null, $value = null)
    {
        if (!$value) {
            $value = $operator;
            $operator = ' = ';
        }

        $this->joinOn .= ($this->joinOn ? ' AND ' : '') . $column . ' ' . $operator . ' %s ';
        array_push($this->whereValues, (string)$value);
        return $this;
    }



}
