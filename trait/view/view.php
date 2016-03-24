<?php
namespace Cyan\CMS;

trait TraitView
{
    public function getView($name, $config = [])
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

        if (!isset($config['layout'])) {
            $config['layout'] = 'page.'.$name.'.index';
        }

        $class_name = sprintf('%sView%s',implode($prefix),$sufix);
        if (!class_exists($class_name)) {
            array_pop($prefix);
            $class_name = sprintf('%sView%s',implode($prefix),$sufix);
        }
        $view = $this->getClass($class_name,'Cyan\CMS\View', $config, function($config) use ($class_name) { return $class_name::getInstance($config); });

        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.'layouts');
        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.strtolower($Cyan->getContainer('application')->getName()).DIRECTORY_SEPARATOR.'layouts');

        return $view;
    }
}