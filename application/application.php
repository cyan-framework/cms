<?php
namespace CMS\Library;

use Cyan\Library\ApplicationWeb;
use Cyan\Library\Extension;
use Cyan\Library\ExtensionTypeComponent;
use Cyan\Library\FactoryPlugin;
use Cyan\Library\ReflectionClass;

/**
 * Class Application
 * @package CMS\Library
 */
class Application extends ApplicationWeb
{
    /**
     * Application Path
     *
     * @var string
     */
    protected $base_path;

    /**
     * Route base path
     *
     * @var string
     */
    protected $route_path;

    /**
     * Initialize Application
     */
    public function initialize()
    {
        //check pdo support
        $drivers = \PDO::getAvailableDrivers();
        if (empty($drivers))
        {
            throw new \PDOException("PDO does not support any driver.");
        }

        if (empty($this->base_path)) {
            $reflection = new ReflectionClass(get_class($this));
            $this->base_path = dirname($reflection->getFileName());
        }

        // register autoload for Library if path exists
        $library_namespace = ucfirst(strtolower(basename($this->base_path))).'\Library';
        $library_path = $this->base_path . DIRECTORY_SEPARATOR . 'library';
        if (file_exists($library_path)) {
            $this->Cyan->Autoload->registerNamespace($library_namespace, $library_path);
        }

        $this->setContainer('factory_plugin', new FactoryPlugin());

        $app_config = $this->getConfig();

        $database_enviroment = isset($app_config['database_environment']) ? $app_config['database_environment'] : 'development' ;
        $database_enviroment_identifier = sprintf('config:database.%s.%s',$this->name,$database_enviroment);
        $database_config = $this->Cyan->Finder->getIdentifier($database_enviroment_identifier,[]);
        if (!empty($database_config)) {
            $this->Database->setConfig($database_config->toArray())->connect();
        } else {
            throw new ApplicationException(sprintf('Database Enviroment "%s" not found in path %s',$database_enviroment,$this->Cyan->Finder->getPath($database_enviroment_identifier)));
        }

        Extension::addIncludePath($this->Cyan->Finder->getPath('vendor:cms.extension.type'));

        // load plugins
        $this->loadPlugins();

        // assign applications plugins to this class
        $this->getContainer('factory_plugin')->assign('application', $this);

        // add path prefix if set
        if (!empty($this->route_path)) {
            $this->Router->setRoutePathPrefix($this->route_path);
        }

        // load component structure
        $this->loadComponents($this->Cyan->Finder->getResource('components'));

        return parent::initialize();
    }

    /**
     * Load plugins to application factory plugin
     *
     * @throws FactoryException
     */
    private function loadPlugins()
    {
        /** @var ExtensionTypePlugin $pluginArchitecture */
        $pluginArchitecture = Extension::get('plugin');
        $pluginArchitecture->setContainer('application', $this);
        $pluginArchitecture->register($this->Cyan->Finder->getResource('plugins'));
    }

    /**
     * Assign component routes
     *
     * @param $path
     */
    public function loadComponents($path)
    {
        $app_config = $this->getConfig();

        if (isset($app_config['sef'])) {
            $this->Router->setSef($app_config['sef']);
        } else {
            $Cyan = \Cyan::initialize();
            $this->Router->setSef($Cyan->Router->getSef());
        }

        $components_path = glob($path.'/*', GLOB_ONLYDIR);
        /** @var ExtensionTypeComponent $componentArchitecture */
        $componentArchitecture = Extension::get('component');
        $componentArchitecture->setContainer('application', $this);
        foreach ($components_path as $component_path) {
            $componentArchitecture->resetPath();
            $componentArchitecture->addPath($component_path . DIRECTORY_SEPARATOR . $this->name);
            $componentArchitecture->addPath($component_path);
            $componentArchitecture->register($component_path);
        }
    }

    /**
     * @param $class_name
     * @param $class_path
     * @param string $instance_of
     */
    protected function checkClass($class_name, $class_path, $instance_of = 'Cyan\Library\Controller')
    {
        if (!class_exists($class_name)) {
            throw new ApplicationException(sprintf('Class "%s" not found in %s',$class_name, $class_path));
        }

        $required_traits = [
            'Cyan\Library\TraitSingleton'
        ];

        $reflection_class = new ReflectionClass($class_name);
        foreach ($required_traits as $required_trait) {
            if (!in_array($required_trait,$reflection_class->getTraitNames())) {
                throw new ApplicationException(sprintf('%s class must use %s', $class_name, $required_trait));
            }
        }

        if (!is_subclass_of($class_name,$instance_of)) {
            throw new ApplicationException(sprintf('%s class must be a instance of %s', $class_name, $instance_of));
        }
    }
}