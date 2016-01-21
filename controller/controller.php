<?php
namespace CMS\Library;

use Cyan\Library\ControllerException;
use Cyan\Library\Form;
use Cyan\Library\ReflectionClass;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitEvent;
use Cyan\Library\TraitPrototype;

class Controller extends \Cyan\Library\Controller
{
    use TraitPrototype, TraitContainer, TraitEvent;

    /**
     * Component Base Path
     *
     * @var string
     * @since 1.0.0
     */
    protected $base_path;

    /**
     * Component Folder
     *
     * @var string
     * @since 1.0.0
     */
    protected $component_name;

    /**
     * Create View
     *
     * @param $name
     *
     * @return View
     *
     * @since 1.0.0
     */
    public function createView($name, $config = [])
    {
        $App = $this->getContainer('application');

        if (!isset($config['base_path'])) {
            $config['base_path'] = sprintf('%s/view',$this->getBasePath());
        }

        $View = $App->getContainer('factory_view')->create($name, $config, 'CMS\Library\View');
        if (!$View->hasContainer('application')) {
            $View->setContainer('application', $App);
            $View->setContainer('factory_plugin', $App->getContainer('factory_plugin'));
        }
        $View->initialize();

        return $View;
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

    /**
     * @param $class_name
     * @return \Cyan\Library\Controller
     */
    public function getController($class_name)
    {
        return $this->getClass($class_name);
    }

    /**
     * @param string $class_name
     */
    private function getClass($class_name,$subclass_of='Cyan\Library\Controller')
    {
        $required_traits = [
            'Cyan\Library\TraitSingleton'
        ];

        $reflection_class = new ReflectionClass($class_name);
        foreach ($required_traits as $required_trait) {
            if (!in_array($required_trait,$reflection_class->getTraitNames())) {
                throw new ControllerException(sprintf('%s class must use %s', $class_name, $required_trait));
            }
        }

        if (!is_subclass_of($class_name,$subclass_of)) {
            throw new ControllerException(sprintf('%s class must be a instance of %s', $class_name,$subclass_of));
        }

        $instance = $class_name::getInstance();
        if (!$instance->hasContainer('application')) {
            $instance->setContainer('application', $this->getContainer('application'));
        }
        if (!$instance->hasContainer('factory_plugin')) {
            $instance->setContainer('factory_plugin', $this->getContainer('application')->getContainer('factory_plugin'));
        }
        if (method_exists($instance,'initialize')) {
            $instance->initialize();
        }

        return $instance;
    }
}