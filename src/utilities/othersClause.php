<?php


namespace PluginMaster\DB\utilities;



trait othersClause
{

    protected $orderQuery = '';
    protected $selectQuery = '*';
    protected $groupQuery = '';
    protected $limitQuery = '';
    protected $offsetQuery = '';


    /**
     * @param $number
     * @return $this
     */
    public function limit($number)
    {
        $this->limitQuery = " LIMIT $number " ;
        return $this;
    }


    /**
     * @param $number
     * @return $this
     */
    public function offset($number)
    {
        $this->offsetQuery = " OFFSET $number " ;
        return $this;
    }


    /**
     * @param $columns | columns with comma separator
     * @param $direction
     * @return othersClause
     */
    public function orderBy($columns, $direction)
    {
        $this->orderQuery = " ORDER BY " . $columns . " " . $direction;
        return $this;
    }

    /**
     * @param $columns | columns with comma separator
     * @param $direction
     * @return othersClause
     */
    public function groupBy($columns)
    {
        $this->orderQuery = " GROUP BY " . $columns . " " ;
        return $this;
    }



}
