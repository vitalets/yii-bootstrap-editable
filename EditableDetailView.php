<?php
/**
 * EditableDetailView class file.
 * 
 * This widget makes editable several attributes of single model, shown as name-value table
 * 
 * @author Vitaliy Potapov <noginsk@rambler.ru>
 * @link https://github.com/vitalets/yii-bootstrap-editable
 * @copyright Copyright &copy; Vitaliy Potapov 2012
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version 1.0.0
 */
 
Yii::import('ext.editable.EditableField');
Yii::import('zii.widgets.CDetailView');

class EditableDetailView extends CDetailView
{
    //common url for all editables
    public $url = '';

    //set bootstrap css
    public $htmlOptions = array('class'=> 'table table-bordered table-striped table-hover table-condensed');

    public function init()
    {
        if (!$this->data instanceof CModel) {
            throw new CException('Property "data" should be of CModel class.');
        }

        parent::init();
    }

    protected function renderItem($options, $templateData)
    {
        if (!isset($options['editable']) || (isset($options['editable']) && $options['editable'] !== false)) {    
            //ensure $options['editable'] is array
            if(!isset($options['editable'])) $options['editable'] = array();            
            
            //take common url
            if (!array_key_exists('url', $options['editable'])) {
                $options['editable']['url'] = $this->url;
            }

            $editableOptions = CMap::mergeArray($options['editable'], array(
                'model'     => $this->data,
                'attribute' => $options['name'],
                'emptytext' => ($this->nullDisplay === null) ? Yii::t('zii', 'Not set') : strip_tags($this->nullDisplay),
            ));
            
            //if value in detailview options provided, set text directly
            if(array_key_exists('value', $options) && $options['value'] !== null) {
                $editableOptions['text'] = $templateData['{value}'];
                $editableOptions['encode'] = false;
            }

            $editable = $this->controller->createWidget('EditableField', $editableOptions);
            if($editable->enabled) {
                ob_start();
                $editable->run();
                $templateData['{value}'] = ob_get_clean();
            }
        } 

        parent::renderItem($options, $templateData);
    }

}

