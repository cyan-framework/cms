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
        $item = $table->fetch();

        $this->onAfterLoadHook($item);

        return $item;
    }

    public function changeState($id,$state_id)
    {
        $name = Inflector::isSingular($this->getName()) ? $this->getName() : Inflector::singularize($this->getName()) ;
        $table_config = $this->getTable($name)->getConfig();

        $query = $this->getDbo()->getDatabaseQuery()->update($table_config['table_name'])->set('state_id = ?')->where($table_config['table_key'].' IN '.sprintf('(%s)',implode(',',$id)))->parameters([$state_id]);
        $sth = $this->getDbo()->prepare($query);
        $sth->execute($query->getParameters());

        return true;
    }

    /**
     * @param $item
     */
    protected function onAfterLoadHook(&$item)
    {
        
    }

    /**
     * @param array $data
     */
    public function save(array $data)
    {
        return $this->getTable($this->getName())->save($data);
    }
}