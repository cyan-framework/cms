<?php
namespace Cyan\CMS;
use Cyan\Framework\Database;
use Cyan\Framework\FormFieldOption;
use Cyan\Framework\FormFieldSelect;
use Cyan\Framework\XmlElement;

/**
 * Class FormFieldSql
 * @package Cyan\CMS
 * @since 1.0.0
 */
class FormFieldSql extends FormFieldSelect
{
    /**
     * Unset attributes
     *
     * @var array
     * @since 1.0.0
     */
    protected $unset_attributes = [
        'description',
        'label',
        'type',
        'value'
    ];

    /**
     * Return array of options
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getOptions()
    {
        $table_name = $this->getAttribute('table');
        $option_value = $this->getAttribute('option-value');
        $option_text = $this->getAttribute('option-text');
        $fields = [
            $option_value.' AS value',
            $option_text.' AS text'
        ];
        $this->unsetAttributes(['table','option-value','option-text']);

        $id = $value = $this->getValue();

        $Cyan = \Cyan::initialize();
        // get current application
        $App = $Cyan->getContainer('application');

        /** @var Database $Dbo */
        $Dbo = $App->Database->connect();

        $query = $Dbo->getDatabaseQuery()->from($table_name)->select(implode(',',array_filter($fields)));

        $sth = $Dbo->prepare($query);
        $sth->execute($query->getParameters());
        $rows = $sth->fetchAll(\PDO::FETCH_OBJ);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $node = new XmlElement('<option name="'.$row->text.'" value="'.$row->value.'"></option>');
                $this->options[] = new FormFieldOption($node);
            }
            unset($node);
        }

        return $this->options;
    }
}