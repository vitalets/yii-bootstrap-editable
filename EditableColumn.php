<?php
/**
 * EditableColumn class file.
 * 
 * This widget makes editable column in GridView
 * 
 * @author Vitaliy Potapov <noginsk@rambler.ru>
 * @link https://github.com/vitalets/yii-bootstrap-editable
 * @copyright Copyright &copy; Vitaliy Potapov 2012
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version 0.1.0
 */

Yii::import('ext.editable.EditableField');
Yii::import('zii.widgets.grid.CDataColumn');

class EditableColumn extends CDataColumn
{
    //editable params
    public $editable = array();

    //flag to render client script only once
    protected $isScriptRendered = false;

    public function init()
    {
        if (!$this->grid->dataProvider instanceOf CActiveDataProvider) {
            throw new CException('EditableColumn can be applied only to grid based on CActiveDataProvider');
        }
        if (!$this->name) {
            throw new CException('You should provide name for EditableColumn');
        }

        parent::init();

        //todo: change original onajaxupdate to work with ajax
        /*
        $this->grid->afterAjaxUpdate = 'js: function(id, data) {
           alert(2);
        }';
        */

    }

    protected function renderDataCellContent($row, $data)
    {
        ob_start();
        parent::renderDataCellContent($row, $data);
        $text = ob_get_clean();

        $options = CMap::mergeArray($this->editable, array(
            'model'     => $data,
            'attribute' => $this->name,
            'text'      => $text,
            'encode'    => false,
        ));

        $cell = $this->grid->controller->createWidget('EditableField', $options);

        //if not enabled --> just render text
        if (array_key_exists('enabled', $this->editable) && $this->editable['enabled'] === false) {
            $cell->renderText();
            return;
        }

        //make selector non unique for all cells
        $selector = get_class($cell->model) . '_' . $cell->attribute;
        $cell->htmlOptions['rel'] = $selector;

        $cell->renderLink();

        if (!$this->isScriptRendered) {
            $options = CJavaScript::jsonEncode($cell->options);
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->id, "$('#{$this->grid->id} a[rel={$selector}]').editable($options);");
            $this->isScriptRendered = true;
        }
    }
}