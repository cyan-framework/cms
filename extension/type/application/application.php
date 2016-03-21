<?php
namespace Cyan\Framework;

require_once 'helper' . DIRECTORY_SEPARATOR . 'helper.php';

class ExtensionTypeApplication extends ExtensionType
{
    use \Cyan\Framework\TraitSingleton;

    public function register()
    {

    }
}

ExtensionTypeApplicationHelper::initialize();