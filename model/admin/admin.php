<?php
namespace Cyan\CMS;

use Cyan\Framework\TraitContainer;
use Cyan\Framework\TraitError;
use Cyan\Framework\TraitEvent;

/**
 * Class ModelAdmin
 * @package Cyan\CMS
 */
class ModelAdmin extends Model
{
    /**
     * @param $key
     * @return mixed
     */
    public function loadByPrimaryKey($key)
    {
        $App = $this->getContainer('application');

        $table_config = $this->getTableConfig($this->getTableName());

        $Dbo = $this->getDbo();

        $row = $Dbo->table($table_config['table_name'])->where($table_config['table_key'].' = ?')->parameters([$key])->fetch();

        return $row;
    }

    /**
     * Save
     *
     * @param $data
     *
     * @return boolean|integer
     */
    public function save(array $data)
    {
        $App = $this->getContainer('application');
        $table_config = $this->getTableConfig($this->getTableName());

        if (isset($table_config['insert_filters'])) {
            $validate_data = $this->Cyan->Filter->getArray($table_config['insert_filters'], $data);
        } else {
            $validate_data = $data;
        }

        if (isset($table_config['required_fields']) && is_array($table_config['required_fields']) && !empty($table_config['required_fields'])) {
            $required_fields = array_diff_key(array_flip($table_config['required_fields']), $validate_data);
            if (!empty($required_fields)) {
                $this->setError($App->Text->sprintf('GLOBAL_ERROR_REQUIRED_FIELDS',implode(', ',$required_fields)));
                return false;
            }
        }

        $this->onBeforeSaveHook($validate_data);

        $this->trigger('BeforeSave', $this, $validate_data);

        $Dbo = $this->getDbo();

        if (!isset($table_config['table_key'])) {
            $sql = $Dbo->getDatabaseQuery()->insert($table_config['table_name'])->columns(implode(',', array_keys($validate_data)))->values($validate_data);
            $is_new = true;
        } else {
            $key_name = $table_config['table_key'];
            $key_value = isset($validate_data[$key_name]) ? $validate_data[$key_name] : 0 ;
            $is_new = ($key_value > 0) ? false : true;

            if ($is_new) {
                $sql = $Dbo->getDatabaseQuery()->insert($table_config['table_name'])->columns(implode(',', array_keys($validate_data)))->values($validate_data);
            } else {
                unset($validate_data[$key_name]);
                $sql = $Dbo->getDatabaseQuery()->update($table_config['table_name'])->set($validate_data)->where($table_config['table_key'].' = ?')->parameters([$key_value]);
            }
        }

        $sth = $Dbo->prepare($sql);
        $sth->execute($sql->getParameters());

        $insert_id = $is_new ? $Dbo->lastInsertID() : $key_value ;
        if (isset($key_name)) {
            $validate_data[$key_name] = $insert_id;
        }

        $this->trigger('AfterSave', $this, $validate_data, $is_new, $insert_id);

        $this->onAfterSaveHook($validate_data, $is_new, $insert_id);

        return $validate_data;
    }

    /**
     * @param $data
     */
    protected function onBeforeSaveHook(&$data)
    {

    }

    /**
     * @param $data
     */
    protected function onAfterSaveHook($data, $is_new, $insert_id)
    {

    }
}