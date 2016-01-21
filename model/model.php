<?php
namespace CMS\Library;

use Cyan\Library\ReflectionClass;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitError;
use Cyan\Library\TraitEvent;

/**
 * Class Model
 * @package CMS\Library
 */
abstract class Model
{
    use TraitContainer, TraitEvent, TraitError;

    /**
     * Model name
     *
     * @var string
     */
    protected $name;

    /**
     * Table Name only if diff from model
     *
     * @var string
     */
    protected $table_name = '';

    /**
     * Request
     *
     * @var array
     */
    protected $request;

    /**
     * Cyan Class
     *
     * @var \Cyan
     */
    protected $Cyan;

    /**
     * Initialize Model
     */
    public function initialize()
    {
        $factory_plugin = $this->getContainer('factory_plugin');
        $factory_plugin->assign('model', $this);

        $this->Cyan = \Cyan::initialize();
    }

    /**
     * Model Name
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getName()
    {
        if (empty($this->name)) {
            $reflection_class = new ReflectionClass($this);
            $this->name = $reflection_class->getShortName();
        }

        return $this->name;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->table_name)) {
            $parts = preg_split('/(?=[A-Z])/',$this->getName(), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
            $this->table_name = strtolower(end($parts));
        }

        return $this->table_name;
    }

    /**
     * @param $name
     * @return null
     */
    public function getTablePrimaryKey($name = null)
    {
        if (is_null($name)) {
            $name = $this->getTableName();
        }
        $table_config = $this->getTableConfig($name);

        return isset($table_config['table_key']) ? $table_config['table_key'] : null ;
    }

    /**
     * @param $name
     *
     * @return array
     */
    public function getTableConfig($name)
    {
        if (strpos($name,':') === false) {
            $App = $this->getContainer('application');
            $parts = preg_split('/(?=[A-Z])/',$this->getName(), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
            $component_folder = sprintf('com_%s', strtolower($parts[0]));
            $table_config_identifier = sprintf('components:%s.config.table.%s',$component_folder,$name);
        } else {
            $table_config_identifier = $name;
        }

        return $this->Cyan->Finder->getIdentifier($table_config_identifier, []);
    }

    /**
     * Get Database
     *
     * @return \Cyan\Library\Database
     *
     * @since 1.0.0
     */
    public function getDbo()
    {
        $Dbo = $this->getContainer('application')->Database->connect();

        return $Dbo;
    }

    /**
     * set Request
     *
     * @param $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }
}