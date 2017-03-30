<?php

namespace Food\AppBundle\Form\Fictional;

/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 17.3.29
 * Time: 15.27
 */
class Table
{
    protected $name;
    protected $fields;

    /**
     * Table constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->fields = [];
    }

    public function addField($field)
    {
        $this->fields[] = $field;
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
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


}