<?php
namespace Cyan\CMS;

use Cyan\Framework\Config;
use Cyan\Framework\DatabaseTable;
use Cyan\Framework\TraitContainer;
use Cyan\Framework\TraitEvent;

/**
 * Class Table
 * @package Cyan\CMS
 */
class Table extends DatabaseTable
{
    use TraitContainer, TraitEvent;

    /**
     * Cyan Class
     *
     * @var \Cyan
     */
    protected $Cyan;

    /**
     * @var string
     */
    protected $table_key = 'id';

    /**
     * @var Config
     */
    protected $config;

    /**
     * Table constructor.
     * @param string $table_name
     * @param string $table_key
     * @param array $config
     */
    public function __construct($table_name, $table_key = 'id', array $config = [])
    {
        $this->Cyan = \Cyan::initialize();
        $this->table_key = $table_key;

        $this->config = Config::getInstance($table_name);
        if (!empty($config)) {
            $this->config->loadArray($config);
        }

        parent::__construct($this->Cyan->getContainer('application')->Database->connect(), $table_name);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Initialize Model
     */
    public function initialize()
    {
        $factory_plugin = $this->Cyan->getContainer('application')->getContainer('factory_plugin');
        $factory_plugin->assign('table', $this);
    }

    /**
     * Load by ID
     *
     * @param $id
     * @return $this
     */
    public function load($id)
    {
        $this->query->where($this->table_key.' = ?');
        $this->query->parameters([$id]);

        return $this;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function save(array $data)
    {
        $query = $this->db->getDatabaseQuery();

        $is_new = false;

        $this->onBeforeSaveHook($data);
        $this->trigger('BeforeSave', $this, $data);

        if (isset($data[$this->table_key]) && intval($data[$this->table_key])) {
            $key_value = $data[$this->table_key];
            unset($data[$this->table_key]);
            $sql = $query->update($this->getTable());
            $sql->where($this->table_key.' = '.$key_value);
            foreach ($data as $field => $value) {
                $sql->set($field.' = ?');
            }
            $sql->parameters(array_values($data));
        } else {
            $is_new = true;
            $sql = $query->insert($this->getTable());
            unset($data[$this->table_key]);
            foreach ($data as $field => $value) {
                $sql->columns($field);
            }
            $sql->values($data);
        }

        $sth = $this->db->prepare($sql);
        $return = $sth->execute($sql->getParameters());
        if ($is_new) $data[$this->table_key] = $sth->lastInsertId();

        $this->trigger('AfterSave', $this, $data, $is_new, $data[$this->table_key]);

        $this->onAfterSaveHook($data, $is_new, $data[$this->table_key]);

        return $return;
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