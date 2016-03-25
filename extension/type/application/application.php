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
}

ExtensionTypeApplicationHelper::initialize();