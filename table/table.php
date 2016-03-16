<?php
namespace CMS\Library;

use Cyan\Library\Config;
use Cyan\Library\DatabaseTable;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitEvent;

/**
 * Class Table
 * @package CMS\Library
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
}