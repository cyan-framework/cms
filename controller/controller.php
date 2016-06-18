<?php
namespace Cyan\CMS;

use Cyan\Framework\ControllerException;
use Cyan\Framework\Form;
use Cyan\Framework\ReflectionClass;

class Controller extends \Cyan\Framework\Controller
{
    use TraitMVC;

    /**
     * @param $class_name
     * @return \Cyan\Framework\Controller
     */
    public function getController($class_name)
    {
        return $this->getClass($class_name);
    }
}