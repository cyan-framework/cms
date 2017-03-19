<?php
namespace Cyan\Framework;

class ExtensionTypeTheme extends \Cyan\Framework\ExtensionType
{
    use \Cyan\Framework\TraitSingleton;

    /**
     * Register themes
     */
    public function register($path)
    {
        return;
    }

    public function discover($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }

        $themes = [];
        foreach (glob($base_path.'*/install.xml') as $theme_path) {
            $themes[] = str_replace('/install.xml',null,str_replace($base_path,null,$theme_path));
        }

        return $themes;
    }

    public function install($base_path)
    {
        if (!file_exists($base_path) && !is_dir($base_path)) {
            throw new ExtensionException(sprintf('path %s not exists',$base_path));
        }
    }
}