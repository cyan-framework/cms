<?php
namespace CMS\Library;

trait TraitFunctions
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
     * @param $name
     * @param array $config
     */
    public function redirectTo($name, array $config = [])
    {
        $this->getContainer('application')->Router->redirect($this->linkTo($name, $config));
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
     * @return mixed
     */
    public function sprintf()
    {
        $args = func_get_args();

        return !$this->hasContainer('application') ? call_user_func_array('sprintf', $args) : call_user_func_array([$this->getContainer('application')->Text,'sprintf'], $args);
    }
}