<?php
namespace Cyan\CMS;
use Cyan\Framework\ViewException;

/**
 * Class View
 * @package Cyan\CMS
 */
class View extends \Cyan\Framework\View
{
    use TraitMVC;

    /**
     * Set Layout
     *
     * @param $layout
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function setLayout($layout)
    {
        if (!is_string($layout)) {
            throw new ViewException(sprintf('Layout must be string, %s given.',gettype($layout)));
        }
        $this->layout = new Layout($layout,[]);
        if (!$this->layout->hasContainer('view')) {
            $this->layout->setContainer('view', $this);
        }
        if (!$this->layout->hasContainer('application') && $this->hasContainer('application')) {
            $this->layout->setContainer('application', $this->getContainer('application'));
        }

        return $this;
    }

    /**
     * Render a layout
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function display($layout = null)
    {
        $layout = !empty($layout) ? $layout : $this->layout;

        if (!($this->layout instanceof Layout)) {
            $this->setLayout($layout);
        }

        if (!$this->layout->exists('component'))
            $this->set('component', $this->getComponentName());
        if (!$this->layout->exists('view'))
            $this->set('view', $this->getName());

        $Cyan = \Cyan::initialize();
        $this->buffer_content = $this->layout->render();
        $this->trigger('Render', $this);

        return $this->buffer_content;
    }
}