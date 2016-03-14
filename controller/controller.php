<?php
namespace CMS\Library;

use Cyan\Library\ControllerException;
use Cyan\Library\Form;
use Cyan\Library\ReflectionClass;

class Controller extends \Cyan\Library\Controller
{
    use TraitMVC;

    /**
     * Create View
     *
     * @param $name
     *
     * @return View
     *
     * @since 1.0.0
     */
    public function createView($name, $config = [])
    {
        $App = $this->getContainer('application');

        if (!isset($config['base_path'])) {
            $config['base_path'] = sprintf('%s/view',$this->getBasePath());
        }

        $View = $App->getContainer('factory_view')->create($name, $config, 'CMS\Library\View');
        if (!$View->hasContainer('application')) {
            $View->setContainer('application', $App);
            $View->setContainer('factory_plugin', $App->getContainer('factory_plugin'));
        }
        $View->initialize();

        return $View;
    }

    /**
     * @param $class_name
     * @return \Cyan\Library\Controller
     */
    public function getController($class_name)
    {
        return $this->getClass($class_name);
    }
}