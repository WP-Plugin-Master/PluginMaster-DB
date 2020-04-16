<?php


namespace PluginMaster\DB\utilities;


trait whereClause
{

    protected $whereValues = [];
    protected $whereClause = '';
    protected $closerCounter;
    protected $closerSession = false;

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return whereClause
     */
    public function where($column, $operator = null, $value = null)
    {

        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof \Closure) {
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
            array_push($this->whereValues, (string)$finalValue);
            $this->closerCounter++;
        }
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return whereClause
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        $finalOperator = $value ? $operator : '=';
        $finalValue = $value ? $value : $operator;

        if ($column instanceof \Closure) {
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
            array_push($this->whereValues, (string)$finalValue);
            $this->closerCounter++;
        }

        return $this;
    }

    public function whereRaw($query)
    {
        $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' AND ' : ' where ');
    }

    public function orWhereRaw($query)
    {
        $this->whereClause .= $this->closerSession && $this->closerCounter == 1 ? '' : ($this->whereClause ? ' OR ' : ' where ');
    }

}
