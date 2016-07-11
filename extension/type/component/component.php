<?php
namespace Cyan\Framework;

require_once 'helper' . DIRECTORY_SEPARATOR . 'helper.php';

class ExtensionTypeComponent extends \Cyan\Framework\ExtensionType
{
    use \Cyan\Framework\TraitSingleton;

    /**
     * Base path
     *
     * @var string
     */
    protected $base_path;

    /**
     * Register component
     */
    public function register($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }

        if (!isset($this->base_path)) {
            $this->base_path = $base_path;
        }

        $this->component = basename($base_path);
        $this->component_name = $this->component;

        $this->registerLanguage();
        $this->registerFiles(['model','view','controller','table'], $base_path);
        $this->registerRoutes();
        $this->includeInitialize();

        if ($this->base_path == $base_path) {
            $pluginArchitecture = Extension::get('plugin');
            $pluginArchitecture->register($this->base_path);
        }

        $hmvc_base_path = $base_path . DIRECTORY_SEPARATOR . 'components';
        if (file_exists($hmvc_base_path) && is_dir($hmvc_base_path)) {
            $this->register($hmvc_base_path);
        }
    }

    public function discover($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }

        
    }

    /**
     * @param array $types
     * @param $base_path
     */
    private function registerFiles(array $types, $base_path)
    {
        $App = $this->getContainer('application');
        $Cyan = \Cyan::initialize();

        foreach ($types as $type) {
            $file_path = FilesystemPath::find(self::addIncludePath(), $type.'.php');
            if ($file_path) {
                $clean_path = str_replace('.'.pathinfo($file_path, PATHINFO_EXTENSION),null,$file_path);
                $class_name_parts = array_map('ucfirst', array_filter(explode(DIRECTORY_SEPARATOR,str_replace('com_','',str_replace($base_path,null,$clean_path)))));
                array_unshift($class_name_parts,ucfirst($this->component_name));
                $class_name = implode($class_name_parts);
                $Cyan->Autoload->registerClass($class_name, $file_path);
            }

            foreach (self::addIncludePath() as $search_path) {
                foreach (glob($search_path.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.'*.php') as $file_path) {
                    $clean_path = str_replace('.'.pathinfo($file_path, PATHINFO_EXTENSION),null,$file_path);
                    $class_name_parts = array_map('ucfirst', array_filter(explode(DIRECTORY_SEPARATOR,str_replace('com_','',str_replace($base_path,null,$clean_path)))));
                    array_unshift($class_name_parts,ucfirst($this->component_name));
                    $class_name = implode($class_name_parts);
                    $Cyan->Autoload->registerClass($class_name, $file_path);
                }
            }
        }
    }

    /**
     * Register language into application
     */
    private function registerLanguage()
    {
        $App = $this->getContainer('application');
        if ($App->getConfig()->exists('language')) {
            $app_language = $App->getConfig()->get('language');
            if (!$App->Text->loadLanguageIdentifier('components:'.$this->component.'.'.$App->getName().'.language.'.$app_language.'.'.$this->component)) {
                $App->Text->loadLanguageIdentifier('components:'.$this->component.'.language.'.$app_language.'.'.$this->component);
            }
        }
    }

    /**
     * Include initialize if exists
     */
    private function includeInitialize()
    {
        $App = $this->getContainer('application');

        foreach (['initialize','schema'] as $file) {
            $initialize_path = FilesystemPath::find(self::addIncludePath(), $App->getName() . DIRECTORY_SEPARATOR . $file . '.php');
            if ($initialize_path) {
                require_once $initialize_path;
            } else {
                $initialize_path = FilesystemPath::find(self::addIncludePath(), $file . '.php');
                if ($initialize_path) {
                    require_once $initialize_path;
                }
            }
        }
    }

    /**
     * Register routes into application
     */
    private function registerRoutes()
    {
        $App = $this->getContainer('application');
        $route_path = FilesystemPath::find(self::addIncludePath(), 'routes.php');
        $class_base = $this->component_name;

        if ($route_path) {
            $config_routes = require_once $route_path;
        }

        if (!isset($config_routes)) {
            return;
        }

        // check route routes
        if (isset($config_routes['routes'])) {
            foreach ($config_routes['routes'] as $route_uri => $route_config) {
                $this->assignRoute($route_uri, $route_config, $class_base, $route_path);
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
                $App->Router->setDefaultRoute($config_routes['config']['default_route'],$config_routes['default_route_parameters']);
            }
        }
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
        $App = $this->getContainer('application');

        // add via to use GET if its missing
        if (!isset($route_config['via'])) {
            $route_config['via'] = 'get';
        }

        $class_prefix = ucfirst($component_name);
        $class_sufix = ucfirst($App->getName());
        $class_name = sprintf('%sController', $class_prefix);
        $specific_class_name = sprintf('%sController', $class_prefix.$class_sufix);
        if (class_exists($specific_class_name)) {
            $class_name = $specific_class_name;
        }

        if (!isset($route_config['handler'])) {
            $route_config['handler'] = [
                'class_name' => $class_name,
                'method' => 'actionIndex'
            ];
        } elseif (!isset($route_config['handler']['class_name'])) {
            $route_config['handler']['class_name'] = $class_name;
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

        $App->Router->route($allowed_methods, $route_name, $route_uri, $route_config);
    }

    public function install($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }
    }
}