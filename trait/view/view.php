<?php
namespace Cyan\CMS;

trait TraitView
{
    public function getView($name, $config = [])
    {
        $Cyan = \Cyan::initialize();
        if (strpos($name,':') === false) {
            $prefix = [ucfirst($this->getComponentName()),ucfirst($Cyan->getContainer('application')->getName())];
            $sufix = ucfirst($name);
        } else {
            $parse = parse_url($name);
            $parts = explode(':', $parse['path']);

            $component = array_shift($parts);
            $name = empty($parts) ? $component : end($parts);
            $prefix = [ucfirst($component),ucfirst($Cyan->getContainer('application')->getName())];
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
        $view = $this->getClass($class_name,'Cyan\CMS\View', [$config], function($config) use ($class_name) { return new $class_name($config); });

        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.'layouts');
        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.strtolower($Cyan->getContainer('application')->getName()).DIRECTORY_SEPARATOR.'layouts');

        return $view;
    }
}