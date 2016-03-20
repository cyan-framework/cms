<?php
namespace Cyan\Library;

require_once 'helper' . DIRECTORY_SEPARATOR . 'helper.php';

class ExtensionTypeApplication extends ExtensionType
{
    use \Cyan\Library\TraitSingleton;

    public function register()
    {

    }
}

ExtensionTypeApplicationHelper::initialize();