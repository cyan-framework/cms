<?php
namespace Cyan\CMS;

use Cyan\Framework\ReflectionClass;

trait TraitComponent
{
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