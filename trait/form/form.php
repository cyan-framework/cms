<?php
namespace Cyan\CMS;

use Cyan\Framework\Form;

trait TraitForm
{
    /**
     * Get Form
     *
     * @param string $name
     * @param string|null $control_name
     *
     * @return Form
     *
     * @since 1.0.0
     */
    public function getForm($name, $control_name = null)
    {
        $Cyan = \Cyan::initialize();

        if (strpos($name,':') === false) {
            $form_identifier = sprintf('components:%s.form.%s', $this->getComponentName(), $name);
            $field_identifier = sprintf('components:%s.form.fields', $this->getComponentName());
            Form::addFieldPath($Cyan->Finder->getPath($field_identifier));
        } else {
            $form_identifier = $name;
        }
        $form = Form::getInstance($form_identifier, $control_name);

        return $form;
    }
}