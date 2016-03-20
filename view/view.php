<?php
namespace CMS\Library;
use Cyan\Library\ViewException;

/**
 * Class View
 * @package CMS\Library
 */
class View extends \Cyan\Library\View
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

        $this->set('component', $this->getComponentName());

        $Cyan = \Cyan::initialize();
        $this->buffer_content = $this->layout->render();
        $this->trigger('Render', $this);

        return $this->buffer_content;
    }
}