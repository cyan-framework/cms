<?php
namespace Cyan\CMS;

use Cyan\Framework\Inflector;

trait TraitModelItem
{
    /**
     * @param int $id
     * @return array
     */
    public function getItem($id = 0)
    {
        $name = Inflector::isSingular($this->getName()) ? $this->getName() : Inflector::singularize($this->getName()) ;
        $table = $this->getTable($name);
        $table->load($id);

        return $table->fetch();
    }
}