<?php
namespace Cyan\Framework;

class ExtensionTypePlugin extends \Cyan\Framework\ExtensionType
{
    use \Cyan\Framework\TraitSingleton;

    /**
     * Register plugins
     */
    public function register($path)
    {
        if (!file_exists($path) && !is_dir($path)) {
            throw new \Cyan\CMS\ArchitectureException(sprintf('path %s not exists',$path));
        }

        $Cyan = \Cyan::initialize();
        $App = $this->getContainer('application');
        $plugin_manager = $App->getContainer('factory_plugin');

        $plugin_types = glob($path.'/*', GLOB_ONLYDIR);
        foreach ($plugin_types as $plugin_type) {
            $plugin_paths = glob($plugin_type.'/*', GLOB_ONLYDIR);
            foreach ($plugin_paths as $plugin_path) {
                $class_name = sprintf('Plugin%s%s', ucfirst(strtolower(basename($plugin_type))), ucfirst(strtolower(basename($plugin_path))));
                $file_path = $plugin_path.DIRECTORY_SEPARATOR.basename($plugin_path).'.php';
                if (file_exists($file_path)) {
                    $plugin_callback = require_once $file_path;
                    if (is_callable($plugin_callback)) {
                        $type = basename($plugin_type);
                        $name = basename($plugin_path);

                        $plugin_manager->create($type, $name, $plugin_callback);
                    } elseif (class_exists($class_name)) {
                        $type = basename($plugin_type);
                        $name = basename($plugin_path);

                        $reflection_class = new ReflectionClass($class_name);
                        if (!in_array('Cyan\Framework\TraitSingleton',$reflection_class->getTraitNames())) {
                            throw new FactoryException(sprintf('%s class must use Cyan\Trait\Singleton', $class_name));
                        }
                        unset($reflection_class);

                        $plugin_manager->create($type, $name, $class_name::getInstance());
                    }
                }
            }
        }
    }
}