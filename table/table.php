<?php
namespace CMS\Library;

use Cyan\Library\ReflectionClass;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitEvent;

class Table
{
    use TraitContainer, TraitEvent;

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
}