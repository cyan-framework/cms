<?php
namespace CMS\Library;

use Cyan\Library\ControllerException;
use Cyan\Library\Form;
use Cyan\Library\ReflectionClass;

class Controller extends \Cyan\Library\Controller
{
    use TraitMVC;

    /**
     * @param $class_name
     * @return \Cyan\Library\Controller
     */
    public function getController($class_name)
    {
        return $this->getClass($class_name);
    }
}