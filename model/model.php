<?php
namespace Cyan\CMS;

use Cyan\Framework\Inflector;
use Cyan\Framework\ReflectionClass;
use Cyan\Framework\TraitContainer;
use Cyan\Framework\TraitError;
use Cyan\Framework\TraitEvent;

/**
 * Class Model
 * @package Cyan\CMS
 */
abstract class Model
{
    use TraitComponent, TraitClass, TraitTable, TraitForm, TraitModel, TraitContainer, TraitEvent, TraitError;

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
     * Return name
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getName()
    {
        if (empty($this->name)) {
            $reflection = new ReflectionClass(__CLASS__);
            $self_class_name = strtolower($reflection->getShortName());

            $class_parts = explode('_', Inflector::underscore(get_called_class()));
            $class_name = implode(array_slice($class_parts, array_search($self_class_name,$class_parts) + 1));

            if (empty($class_name)) {
                $remove_parts = [];
                $remove_parts[] = $self_class_name;
                $remove_parts[] = strtolower($this->getContainer('application')->getName());
                $class_name = implode(array_unique(array_diff($class_parts, $remove_parts)));
            }

            $this->name = $class_name;
        }

        return $this->name;
    }

    /**
     * Get Database
     *
     * @return \Cyan\Framework\Database
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