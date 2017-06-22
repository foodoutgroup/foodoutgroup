<?php

namespace Food\AppBundle\Form\Fictional;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 17.3.29
 * Time: 15.27
 */
class Import
{
    protected $file;
    protected $locale;
    protected $tables;

    /**
     * Import constructor.
     */
    public function __construct()
    {
        $this->tables = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return ArrayCollection
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param array $table
     */
    public function addTable(Table $table)
    {
        $this->tables[] = $table;
    }


}