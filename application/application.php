<?php
namespace Cyan\CMS;

use Cyan\Framework\ApplicationWeb;
use Cyan\Framework\Extension;
use Cyan\Framework\ExtensionTypeComponent;
use Cyan\Framework\FactoryPlugin;
use Cyan\Framework\ReflectionClass;

/**
 * Class Application
 * @package Cyan\CMS
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
     * Initialize resources configuration
     *
     * @var bool
     */
    protected $initializeResourcesConfig = [
        'database' => true,
        'user' => true
    ];

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

        if ($this->canInitializeResource('database')) {
            $database_environment = isset($app_config['database_environment']) ? $app_config['database_environment'] : 'local' ;
            $database_environment_identifier = sprintf('config:application.%s.database.%s',$this->getName(),$database_environment);
            $database_config = $this->Cyan->Finder->getIdentifier($database_environment_identifier, [], []);
            if (empty($database_config)) {
                $database_config = $this->Cyan->Finder->getIdentifier(sprintf('config:database.default.%s',$database_environment), [], []);
            }
            if (!empty($database_config)) {
                $this->Database->setConfig($database_config->toArray())->connect();
            } else {
                throw new ApplicationException(sprintf('Database Environment "%s" not found in path %s',$database_environment,$this->Cyan->Finder->getPath($database_environment_identifier)));
            }
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

        parent::initialize();
    }

    /**
     * Check if can initialize a application resource
     * 
     * @param $name
     * 
     * @return bool
     */
    public function canInitializeResource($name)
    {
        return isset($this->initializeResourcesConfig[$name]) ? $this->initializeResourcesConfig[$name] : false ;
    }

    /**
     * @param $name
     * @param bool $value
     * @param bool $override
     * @return $this
     */
    public function setInitializeResource($name, $value = true, $override = false)
    {
        if (isset($this->initializeResourcesConfig[$name]) && $override || !isset($this->initializeResourcesConfig[$name])) {
            $this->initializeResourcesConfig[$name] = $value;
        }

        return $this;
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
    protected function checkClass($class_name, $class_path, $instance_of = 'Cyan\Framework\Controller')
    {
        if (!class_exists($class_name)) {
            throw new ApplicationException(sprintf('Class "%s" not found in %s',$class_name, $class_path));
        }

        $required_traits = [
            'Cyan\Framework\TraitSingleton'
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