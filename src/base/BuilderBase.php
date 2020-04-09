<?php


namespace PluginMaster\DB\base;


interface BuilderBase
{
    static public function table($name);

    public function where($column, $operator = null, $value = null);

    public function orWhere($column, $operator = null, $value = null);

    public function orderBy($columns, $direction);

    public function select($fields);

    public function insert($data);

    public function update($data);

    public function delete();

    public function first();

    public function get();

    public function toSql();


}
