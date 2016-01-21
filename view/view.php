<?php
namespace CMS\Library;

/**
 * Class View
 * @package CMS\Library
 */
class View extends \Cyan\Library\View
{
    /**
     * Return link
     *
     * @param $name
     * @param array $config
     */
    public function linkTo($name, array $config = [])
    {

        return !$this->hasContainer('application') ? $name : $this->getContainer('application')->Router->generate($name, $config);
    }

    /**
     * Translate a text
     *
     * @param $text
     * @return mixed
     */
    public function translate($text)
    {
        return !$this->hasContainer('application') ? $text : $this->getContainer('application')->Text->translate($text);
    }

    /**
     * Translate a text using sprintf
     *
     * @param $text
     * @return mixed
     */
    public function sprintf()
    {
        $args = func_get_args();

        return !$this->hasContainer('application') ? call_user_func_array('sprintf', $args) : call_user_func_array([$this->getContainer('application')->Text,'srptinf'], $args);
    }
}