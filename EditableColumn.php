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

        $this->attachAjaxUpdateEvent();
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

        $editable = $this->grid->controller->createWidget('EditableField', $options);

        //if not enabled --> just render text
        if (array_key_exists('enabled', $this->editable) && $this->editable['enabled'] === false) {
            $editable->renderText();
            return;
        }

        //manually make selector non unique to match all cells in column
        $selector = get_class($editable->model) . '_' . $editable->attribute;
        $editable->htmlOptions['rel'] = $selector;

        $editable->renderLink();

        //manually render client script once
        if (!$this->isScriptRendered) {
            $script = $editable->registerClientScript();
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $selector.'-event', '
                $("#'.$this->grid->id.'").parent().on("ajaxUpdate.yiiGridView", "#'.$this->grid->id.'", function() {'.$script.'});
            ');
            $this->isScriptRendered = true;
        }
    }
    
   /**
   * Unfortunatly Yii does not support custom js events in it's widgets.
   * So we need to invoke it manually to ensure update of editables on grid ajax update.
   * 
   * issue in Yii github: https://github.com/yiisoft/yii/issues/1313
   * 
   */
    protected function attachAjaxUpdateEvent()
    {
        $trigger = '$("#"+id).trigger("ajaxUpdate");';
        
        //check if trigger already inserted by another column
        if(strpos($this->grid->afterAjaxUpdate, $trigger) !== false) return;
        
        //inserting trigger
        if(strlen($this->grid->afterAjaxUpdate)) {
            $orig = $this->grid->afterAjaxUpdate;
            if(strpos($orig, 'js:')===0) $orig = substr($orig,3);
            $orig = "\n($orig).apply(this, arguments);";
        } else {
            $orig = '';
        }
        $this->grid->afterAjaxUpdate = "js: function(id, data) {
            $trigger $orig
        }";
    }
}