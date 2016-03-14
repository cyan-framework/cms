<?php
namespace CMS\Library;

use Cyan\Library\Form;
use Cyan\Library\Layout;
use Cyan\Library\ReflectionClass;
use Cyan\Library\TraitException;

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
        if (strpos($name,':') === false) {
            $model_identifier = sprintf('components:%s.model.%s', $this->getComponentName(), $name);
        } else {
            $model_identifier = $name;
        }

        $parse = parse_url($model_identifier);
        $parts = explode('.',$parse['path']);
        $component_name = ucfirst(substr($parts[0],4));
        $model_name = ucfirst(end($parts));
        $class_name = sprintf('%sModel%s', $component_name,$model_name);
        unset($parts);
        unset($parse);

        $model_path = $this->Cyan->Finder->getPath($model_identifier,'.php');
        if (!file_exists($model_path)) {
            throw new ModelException(sprintf('Model "%s" not found in path: %s', $model_name, $model_path));
        }
        require_once $model_path;
        return $this->getClass($class_name,'CMS\Library\Model');
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
        if (strpos($name,':') === false) {
            $form_identifier = sprintf('components:%s.form.%s', $this->getComponentName(), $name);
            $field_identifier = sprintf('components:%s.form.fields', $this->getComponentName());
            Form::addFieldPath($this->Cyan->Finder->getPath($field_identifier));
        } else {
            $form_identifier = $name;
        }
        $form = Form::getInstance($form_identifier, $control_name);

        return $form;
    }

    public function getView($name, $config = [])
    {
        if (strpos($name,':') === false) {
            $prefix = ucfirst(substr($this->getComponentName(),4));
            $sufix = ucfirst($name);
            $config['layout'] = 'page.'.$name.'.index';
        } else {
            print_r($name);
            die('must implement subview');
        }
        $class_name = sprintf('%sView%s',$prefix,$sufix);
        $view = $this->getClass($class_name,'CMS\Library\View', $config, function($config) use ($class_name) { return $class_name::getInstance($config); });

        $App = $this->getContainer('application');
        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.'layouts');
        Layout::addIncludePath($view->getBasePath().DIRECTORY_SEPARATOR.strtolower($App->getName()).DIRECTORY_SEPARATOR.'layouts');

        return $view;
    }

    /**
     * @param string $class_name
     */
    protected function getClass($class_name,$subclass_of='Cyan\Library\Controller',$arguments = [], \Closure $newInstance = null)
    {
        $required_traits = [
            'Cyan\Library\TraitSingleton'
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