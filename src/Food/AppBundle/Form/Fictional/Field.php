<?php

namespace Food\AppBundle\Form\Fictional;
/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 17.3.29
 * Time: 15.27
 */
class Field
{
    protected $name;

    /**
     * @var Table
     */
    protected $table;

    /**
     * Field constructor.
     * @param $name
     * @param $table
     */
    public function __construct($name, Table $table)
    {
        $this->name = $name;
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }


}