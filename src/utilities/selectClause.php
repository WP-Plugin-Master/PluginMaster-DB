<?php


namespace PluginMaster\DB\utilities;



trait selectClause
{

    protected $selectQuery = ' * ';


    /**
     * @param $fields
     * @return selectClause
     */
    public function select($fields)
    {
        $this->selectQuery = " " . $fields . " ";
        return $this;
    }



}
