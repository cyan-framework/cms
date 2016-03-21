<?php
namespace Cyan\CMS;

use Cyan\Framework\Form;
use Cyan\Framework\ReflectionClass;
use Cyan\Framework\TraitException;

trait TraitMVC
{
    use TraitFunctions;

    /**
     * Component Folder
     *
     * @var string
     * @since 1.0.0
     */
    protected $component_name;

    /**
     * Component Base Path
     *
     * @var string
     * @since 1.0.0
     */
    protected $base_path;

    /**
     * Get Model
     *
     * @param $name
     * @return Model
     */
    public function getModel($name)
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

        $class_name = sprintf('%sModel%s',implode($prefix),$sufix);
        if (!class_exists($class_name)) {
            array_pop($prefix);
            $class_name = sprintf('%sModel%s',implode($prefix),$sufix);
        }
        return $this->getClass($class_name,'Cyan\CMS\Model');
    }

    /**
     * Get Form
     *
     * @param string $name
     * @param string|null $control_name
     *
     * @return Form
     *
     * @since 1.0.0
     */
    public function getForm($name, $control_name = null)
    {
        $Cyan = \Cyan::initialize();

        if (strpos($name,':') === false) {
            $form_identifier = sprintf('components:%s.form.%s', $this->getComponentName(), $name);
            $field_identifier = sprintf('components:%s.form.fields', $this->getComponentName());
            Form::addFieldPath($Cyan->Finder->getPath($field_identifier));
        } else {
            $form_identifier = $name;
        }
        $form = Form::getInstance($form_identifier, $control_name);

        return $form;
    }

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

    /**
     * @param string $class_name
     */
    protected function getClass($class_name,$subclass_of='Cyan\Framework\Controller',$arguments = [], \Closure $newInstance = null)
    {
        $required_traits = [
            'Cyan\Framework\TraitSingleton'
        ];

        $reflection_class = new ReflectionClass($class_name);
        foreach ($required_traits as $required_trait) {
            if (!in_array($required_trait,$reflection_class->getTraitNames())) {
                throw new TraitException(sprintf('%s class must use %s', $class_name, $required_trait));
            }
        }

        if (!is_subclass_of($class_name,$subclass_of)) {
            throw new TraitException(sprintf('%s class must be a instance of %s', $class_name,$subclass_of));
        }

        if (is_callable($newInstance)) {
            $instance = $newInstance($arguments);
        } else {
            $instance = !empty($arguments) ? call_user_func_array([$class_name,'getInstance'],$arguments) : $class_name::getInstance();
        }

        if (!$instance->hasContainer('application')) {
            $instance->setContainer('application', $this->getContainer('application'));
        }
        if (!$instance->hasContainer('factory_plugin')) {
            $instance->setContainer('factory_plugin', $this->getContainer('application')->getContainer('factory_plugin'));
        }
        if (is_callable([$instance,'initialize'])) {
            $instance->initialize();
        }

        return $instance;
    }

    /**
     * Get component folder name
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getComponentName()
    {
        if (empty($this->component_name)) {
            $reflection_class = new ReflectionClass($this);
            $file_path = explode(DIRECTORY_SEPARATOR,dirname($reflection_class->getFileName()));
            $path = array_slice($file_path, array_search('components',$file_path) + 1,1);
            $this->component_name = end($path);
            unset($reflection_class);
        }

        return $this->component_name;
    }

    /**
     * @param $name
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function setComponentName($name)
    {
        $this->component_name = $name;

        return $this;
    }

    /**
     * Get Path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getBasePath()
    {
        if (empty($this->base_path)) {
            $reflection_class = new ReflectionClass($this);
            $file_path = explode(DIRECTORY_SEPARATOR,dirname($reflection_class->getFileName()));
            $base_path = array_filter(array_slice($file_path,0,array_search($this->getComponentName(),$file_path) + 1));
            $base_path_prefix = '';
            if (dirname($reflection_class->getFileName())[0] == DIRECTORY_SEPARATOR) {
                $base_path_prefix = DIRECTORY_SEPARATOR;
            }
            $this->base_path = $base_path_prefix.implode(DIRECTORY_SEPARATOR,$base_path);
            unset($reflection_class);
        }

        return $this->base_path;
    }
}