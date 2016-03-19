<?php
namespace CMS\Library;

use Cyan\Library\FilesystemPath;

class Layout extends \Cyan\Library\Layout
{
    use TraitFunctions;

    /**
     * Return an instance of the class.
     */
    public static function getInstance($name = null)
    {
        $args = array_slice(func_get_args(), 1);
        $name = $name ?: 'default';
        $static = get_called_class();
        $key = sprintf('%s::%s', $static, $name);
        if(!array_key_exists($key, static::$instances))
        {
            static::$instances[$key] = new self($name, []);
        }

        if (isset($args[0])) {
            static::$instances[$key]->setData($args[0], true);
        }
        if (isset($args[1])) {
            static::$instances[$key]->setOptions($args[1], true);
        }

        return static::$instances[$key];
    }

    /**
     * Render layout
     *
     * @param $layout
     * @param array $data
     * @param array $options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function display($layout, array $data, array $options = [])
    {
        return self::getInstance($layout, $data, $options);
    }

    /**
     * Render layout
     *
     * @param null $layout
     * @param array $data
     * @param array $options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function render($layout = null, array $data = [], array $options = [])
    {
        if (!empty($layout)) {
            $newLayout = self::display($layout, $data, $options);

            foreach ($this->getContainers() as $container_name) {
                if (!$newLayout->hasContainer($container_name)) {
                    $newLayout->setContainer($container_name,$this->getContainer($container_name));

                    if ($container_name == 'view' && $this->getContainer('view')->hasContainer('application') && !$newLayout->hasContainer('application')) {
                        $newLayout->setContainer('application', $this->getContainer('view')->getContainer('application'));
                    }
                }
            }

            return $newLayout->render();
        }

        $output = '';
        $layout_path = str_replace('.',DIRECTORY_SEPARATOR,$this->layout).'.php';
        if ($file = FilesystemPath::find(self::addIncludePath(),$layout_path)) {
            $Cyan = \Cyan::initialize();
            ob_start();
            include $file;
            $output = ob_get_clean();
        }

        return $output;
    }
}