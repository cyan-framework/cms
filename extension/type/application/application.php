<?php
namespace Cyan\Framework;

require_once 'helper' . DIRECTORY_SEPARATOR . 'helper.php';

class ExtensionTypeApplication extends ExtensionType
{
    use \Cyan\Framework\TraitSingleton;

    public function register($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }
        
        
    }

    public function discover($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }

        $apps = [];
        foreach (glob($base_path.'*/install.xml') as $app_path) {
            $app_name = str_replace('/install.xml',null,str_replace($base_path,null,$app_path));
            $apps[] = $app_name;
        }

        return $apps;
    }

    public function install($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }
    }
}

ExtensionTypeApplicationHelper::initialize();