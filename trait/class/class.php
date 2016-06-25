<?php
namespace Cyan\CMS;

use Cyan\Framework\ReflectionClass;
use Cyan\Framework\TraitException;

trait TraitClass
{
    /**
     * @param string $class_name
     */
    protected function getClass($class_name,$subclass_of='Cyan\Framework\Controller',array $arguments = [], \Closure $newInstance = null)
    {
        $required_traits = [
            'Cyan\Framework\TraitSingleton'
        ];

        $reflection_class = new ReflectionClass($class_name);
        foreach ($required_traits as $required_trait) {
            if (!in_array($required_trait,$reflection_class->getTraitNames())) {
                throw new TraitException(sprintf('%s class must use %s', $class_name, $required_trait));
            }
        }

        if (!is_subclass_of($class_name,$subclass_of)) {
            throw new TraitException(sprintf('%s class must be a instance of %s', $class_name,$subclass_of));
        }

        if (is_callable($newInstance)) {
            $instance = call_user_func_array($newInstance, $arguments);
        } else {
            $instance = !empty($arguments) ? call_user_func_array([$class_name,'getInstance'],$arguments) : $class_name::getInstance();
        }

        if ($this->hasContainer('application')) {
            if (!$instance->hasContainer('application')) {
                $instance->setContainer('application', $this->getContainer('application'));
            }
            if (!$instance->hasContainer('factory_plugin')) {
                $instance->setContainer('factory_plugin', $this->getContainer('application')->getContainer('factory_plugin'));
            }
        }
        if (is_callable([$instance,'initialize'])) {
            $instance->initialize();
        }

        return $instance;
    }
}