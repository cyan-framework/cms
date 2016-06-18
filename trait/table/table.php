<?php
namespace Cyan\CMS;

trait TraitTable
{
    public function getTable($name)
    {
        $Cyan = \Cyan::initialize();

        if (strpos($name,':') === false) {
            $prefix = [ucfirst(substr($this->getComponentName(),4)),ucfirst($Cyan->getContainer('application')->getName())];
            $sufix = ucfirst($name);
        } else {
            $parse = parse_url($name);
            $parts = explode(':', $parse['path']);

            $component = array_shift($parts);
            $name = end($parts);
            $prefix = [ucfirst(substr($component,4)),ucfirst($Cyan->getContainer('application')->getName())];
            $sufix = ucfirst($name);
        }

        $class_name = sprintf('%sTable%s',implode($prefix),$sufix);
        if (!class_exists($class_name)) {
            array_pop($prefix);
            $class_name = sprintf('%sTable%s',implode($prefix),$sufix);
        }
        return $this->getClass($class_name,'Cyan\CMS\Table');
    }
}