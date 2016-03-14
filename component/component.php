<?php
namespace CMS\Library;

use Cyan\Library\TraitContainer;
use Cyan\Library\TraitSingleton;

/**
 * Class Component
 * @package CMS\Library
 *
 * @method Component getInstance
 */
class Component
{
    use TraitSingleton,TraitContainer;
}