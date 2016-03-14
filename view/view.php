<?php
namespace CMS\Library;

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
        $this->layout = Layout::getInstance($layout,$layout,[]);
        if (!$this->layout->hasContainer('view')) {
            $this->layout->setContainer('view', $this);
        }

        return $this;
    }
}