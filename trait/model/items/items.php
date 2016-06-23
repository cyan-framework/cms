<?php
namespace Cyan\CMS;

use Cyan\Framework\Inflector;

trait TraitModelItems
{
    /**
     * list of items
     *
     * @return array
     */
    public function getItems()
    {
        $name = Inflector::isSingular($this->getName()) ? $this->getName() : Inflector::singularize($this->getName()) ;
        $table = $this->getTable($name);
        $rows = $table->fetchAll();

        return $rows;
    }
}