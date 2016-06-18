<?php
namespace Cyan\CMS;

use Cyan\Framework\Form;
use Cyan\Framework\ReflectionClass;
use Cyan\Framework\TraitException;

trait TraitMVC
{
    use TraitFunctions, TraitComponent, TraitClass, TraitForm, TraitModel, TraitView;
}