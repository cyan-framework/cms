<?php
namespace Cyan\Library;

class ExtensionTypeComponent extends \Cyan\Library\ExtensionType
{
    use \Cyan\Library\TraitSingleton;

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
        $this->component_name = substr($this->component,4);

        $this->registerLanguage();
        $this->registerFiles(['model','view','controller','table'], $base_path);
        $this->registerRoutes();

        if ($this->base_path == $base_path) {
            $pluginArchitecture = Extension::get('plugin');
            $pluginArchitecture->register($this->base_path);
        }

        $hmvc_base_path = $base_path . DIRECTORY_SEPARATOR . 'components';
        if (file_exists($hmvc_base_path) && is_dir($hmvc_base_path)) {
            $this->register($hmvc_base_path);
        }
    }

    /**
     * @param array $files
     */
    public function registerFiles(array $types, $base_path)
    {
        $App = $this->getContainer('application');
        $Cyan = \Cyan::initialize();

        foreach ($types as $type) {
            $class_name_parts = [];
            $class_name_parts[] = ucfirst($this->component_name);
            $file_path = FilesystemPath::find(self::addIncludePath(), $type.'.php');
            if ($file_path) {
                if (strpos($file_path,$App->getName()) !== false) {
                    $class_name_parts[] = ucfirst($App->getName());
                }
                $class_name_parts[] = ucfirst($type);

                $class_name = implode($class_name_parts);
                $Cyan->Autoload->registerClass($class_name, $file_path);
            }

            foreach (self::addIncludePath() as $search_path) {
                foreach (glob($search_path.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.'*.php') as $file_path) {
                    $class_name_parts = [];
                    $class_name_parts[] = ucfirst($this->component_name);
                    $class_name_parts[] = ucfirst($type);
                    $class_name_parts[] = ucfirst(basename($file_path,'.php'));
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
     * Register routes into application
     */
    private function registerRoutes()
    {
        $App = $this->getContainer('application');
        $route_path = FilesystemPath::find(self::addIncludePath(), 'routes.php');

        if ($route_path) {
            $config_routes = require_once $route_path;
            $class_base = $this->component_name;

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
}