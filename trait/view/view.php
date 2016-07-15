<?php
namespace Cyan\CMS;

trait TraitView
{
    /**
     * Get view from class
     *
     * @param $name
     * @param array $config
     * @return mixed
     */
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

        if (!$view->getLayout()->hasContainer('application')) {
            $view->getLayout()->setContainer('application', $this->getContainer('application'));
            if (!$view->getLayout()->hasContainer('factory_plugin')) {
                $view->getLayout()->setContainer('factory_plugin', $this->getContainer('application')->getContainer('factory_plugin'));
            }
        }

        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.'layouts');
        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.strtolower($Cyan->getContainer('application')->getName()).DIRECTORY_SEPARATOR.'layouts');

        return $view;
    }

    /**
     * Create a view
     *
     * @param array $config
     * @return View
     */
    public function createView(array $config)
    {
        $view = new \Cyan\CMS\View($config);

        if (!$view->getLayout()->hasContainer('application')) {
            $view->getLayout()->setContainer('application', $this->getContainer('application'));
            if (!$view->hasContainer('factory_plugin')) {
                $view->setContainer('factory_plugin', $this->getContainer('application')->getContainer('factory_plugin'));
            }
        }
        $view->initialize();

        return $view;
    }
}