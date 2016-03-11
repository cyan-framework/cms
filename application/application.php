<?php
namespace CMS\Library;

use Cyan\Library\ApplicationWeb;
use Cyan\Library\FactoryPlugin;
use Cyan\Library\FilesystemPath;
use Cyan\Library\ReflectionClass;
use Cyan\Library\RouterException;

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
            $this->Database->setConfig($database_config)->connect();
        } else {
            throw new ApplicationException(sprintf('Database Enviroment "%s" not found in path %s',$database_enviroment,$this->Cyan->Finder->getPath($database_enviroment_identifier)));
        }

        // load plugins
        $this->loadPlugins();

        // assign applications plugins to this class
        $this->getContainer('factory_plugin')->assign('application', $this);

        // add path prefix if set
        if (!empty($this->route_path)) {
            $this->Router->setRoutePathPrefix($this->route_path);
        }

        // load component routes
        $this->loadComponentRoutes($this->Cyan->Finder->getPath('root:components'));

        return parent::initialize();
    }

    /**
     * Load plugins to application factory plugin
     *
     * @throws FactoryException
     */
    private function loadPlugins()
    {
        $plugin_manager = $this->getContainer('factory_plugin');

        $path = $this->Cyan->Finder->getPath('root:plugins');
        $plugin_types = glob($path.'/*', GLOB_ONLYDIR);
        foreach ($plugin_types as $plugin_type) {
            $plugin_paths = glob($plugin_type.'/*', GLOB_ONLYDIR);
            foreach ($plugin_paths as $plugin_path) {
                $class_name = sprintf('Plugin%s%s', ucfirst(strtolower(basename($plugin_type))), ucfirst(strtolower(basename($plugin_path))));
                $file_path = $plugin_path.DIRECTORY_SEPARATOR.basename($plugin_path).'.php';
                if (file_exists($file_path)) {
                    $Cyan = $this->Cyan;
                    $plugin_callback = require_once $file_path;
                    if (is_callable($plugin_callback)) {
                        $type = basename($plugin_type);
                        $name = basename($plugin_path);

                        $plugin_manager->create($type, $name, $plugin_callback);
                    } elseif (class_exists($class_name)) {
                        $type = basename($plugin_type);
                        $name = basename($plugin_path);

                        $reflection_class = new ReflectionClass($class_name);
                        if (!in_array('Cyan\Library\TraitSingleton',$reflection_class->getTraitNames())) {
                            throw new FactoryException(sprintf('%s class must use Cyan\Trait\Singleton', $class_name));
                        }
                        unset($reflection_class);

                        $plugin_manager->create($type, $name, $class_name::getInstance());
                    }
                }
            }
        }
    }

    /**
     * Assign component routes
     *
     * @param $path
     */
    public function loadComponentRoutes($path)
    {
        $app_config = $this->getConfig();

        if (isset($app_config['sef'])) {
            $this->Router->setSef($app_config['sef']);
        } else {
            $Cyan = \Cyan::initialize();
            $this->Router->setSef($Cyan->Router->getSef());
        }

        $components_path = glob($path.'/*', GLOB_ONLYDIR);
        foreach ($components_path as $component_path) {
            $component_folder = basename($component_path);
            $component_name = substr($component_folder,4);

            $app_language = isset($app_config['language']) ? $app_config['language'] : null;
            if (!empty($app_language)) {
                $this->Text->loadLanguageIdentifier('language:'.$app_language.'.'.$component_folder);
            }

            $routes_path = $component_path . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes';
            $route_path = FilesystemPath::find($routes_path, $this->name . '.php');
            if (!$route_path) {
                $route_path = FilesystemPath::find($routes_path, 'default.php');
            }

            if ($route_path) {
                $config_routes = require_once $route_path;

                // check route routes
                if (isset($config_routes['routes'])) {
                    foreach ($config_routes['routes'] as $route_uri => $route_config) {
                        $this->assignRoute($route_uri, $route_config, $component_name, $route_path);
                    }
                }

                // check route config
                if (isset($config_routes['config']) && !empty($config_routes['config'])) {
                    // define default route
                    if (isset($config_routes['config']['default_route'])) {
                        if (!isset($config_routes['default_route_parameters'])) {
                            $config_routes['default_route_parameters'] = [];
                        }

                        // set default route
                        $this->Router->setDefaultRoute($config_routes['config']['default_route'],$config_routes['default_route_parameters']);
                    }
                }
            } else {
                $this->componentRouteNotFound($component_path, $component_name);
            }

            // import default controller php if found
            if ($controller_path = FilesystemPath::find($component_path, 'controller.php')) {
                $class_name = sprintf('%sController', ucfirst($component_name));
                $this->Cyan->Autoload->registerClass($class_name, $controller_path);
                $this->checkClass($class_name, $controller_path);
                unset($reflection_class);
            }

            // import controllers php if exists
            $controllers_path = glob($component_path . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . '*.php');
            foreach ($controllers_path as $controller_path) {
                $class_name = sprintf('%sController%s', ucfirst($component_name), ucfirst(basename($controller_path,'.php')));
                $this->Cyan->Autoload->registerClass($class_name, $controller_path);
                $this->checkClass($class_name, $controller_path);
                unset($reflection_class);
            }

            // import models
            $models_path = glob($component_path . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . '*.php');
            foreach ($models_path as $model_path) {
                $class_name = sprintf('%sModel%s', ucfirst($component_name), ucfirst(basename($model_path,'.php')));
                $this->Cyan->Autoload->registerClass($class_name, $model_path);
                $this->checkClass($class_name, $model_path, 'CMS\\Library\\Model');
            }

            // check if exists components inside component path
            $component_components_path = $component_path . DIRECTORY_SEPARATOR . 'components';
            if (file_exists($component_components_path) && is_dir($component_components_path)) {
                $this->loadComponentRoutes($component_components_path);
            }
        }
    }

    /**
     * @param $component_path
     * @param $component_name
     */
    protected function componentRouteNotFound($component_path, $component_name)
    {

    }

    /**
     * Assign route to application
     *
     * @param $route_uri
     * @param $route_config
     * @param $component_name
     * @param $route_path
     */
    protected function assignRoute($route_uri, $route_config, $component_name, $route_path)
    {
        // add via to use GET if its missing
        if (!isset($route_config['via'])) {
            $route_config['via'] = 'get';
        }

        if (!isset($route_config['handler'])) {
            $route_config['handler'] = [
                'class_name' => sprintf('%sController', ucfirst($component_name)),
                'method' => 'actionIndex'
            ];
        } elseif (!isset($route_config['handler']['class_name'])) {
            $route_config['handler']['class_name'] = sprintf('%sController', ucfirst($component_name));
        } elseif (!isset($route_config['handler']['method'])) {
            $route_config['handler']['method'] = 'actionIndex';
        }

        $required_route_keys = ['route_name'];
        foreach ($required_route_keys as $route_key) {
            if (!isset($route_config[$route_key])) {
                throw new RouterException(sprintf('%s is undefined at "%s" in %s',$route_key, $route_uri, $route_path));
            }
        }

        $allowed_methods = $route_config['via'];
        unset($route_config['via']);
        $route_name = $route_config['route_name'];
        unset($route_config['route_name']);
        $this->Router->route($allowed_methods, $route_name, $route_uri, $route_config);
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