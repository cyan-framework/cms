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
        $table = $this->getTable($this->getTableName());
        $table->load($id);
        $item = $table->fetch();

        $this->onAfterLoadHook($item);

        return $item;
    }

    public function changeState($id,$state_id)
    {
        $table_config = $this->getTable($this->getTableName())->getConfig();

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
        return $this->getTable($this->getTableName())->save($data);
    }

    /**
     * @param $item
     */
    protected function onAfterSaveHook(&$item)
    {

    }
}