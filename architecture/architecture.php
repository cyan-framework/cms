<?php
namespace CMS\Library;

use Cyan\Library\FilesystemPath;
use Cyan\Library\ReflectionClass;
use Cyan\Library\TraitContainer;
use Cyan\Library\TraitFilepath;

class Architecture
{
    use TraitContainer, TraitFilepath;

    /**
     * @param $adapter
     * @throws ArchitectureException
     */
    public static function getAdapter($adapter)
    {
        $adapter = strtolower($adapter);
        if ($file = FilesystemPath::find(self::addIncludePath(),$adapter.'.php')) {
            require_once $file;
            $class_name = 'ArchitectureAdapter'.ucfirst($adapter);
            if (!class_exists('\\'.$class_name)) {
                throw new ArchitectureException(sprintf('Adapter Class "%s" not found!',$class_name));
            }
            $required_traits = [
                'Cyan\Library\TraitSingleton'
            ];

            $reflection_class = new ReflectionClass($class_name);
            foreach ($required_traits as $required_trait) {
                if (!in_array($required_trait,$reflection_class->getTraitNames())) {
                    throw new ArchitectureException(sprintf('%s class must use %s', $class_name, $required_trait));
                }
            }

            if (!is_callable([$class_name,'register'])) {
                throw new ArchitectureException(sprintf('%s class must implement register method', $class_name));
            }

            return $class_name::getInstance();
        }

        throw new ArchitectureException(sprintf('Adapter "%s" not found!',$adapter));
    }
}

Architecture::addIncludePath(__DIR__ . DIRECTORY_SEPARATOR . 'adapter');