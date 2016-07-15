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
        $table = $this->getTable($this->getTableName());
        $rows = $table->fetchAll();

        return $rows;
    }
}