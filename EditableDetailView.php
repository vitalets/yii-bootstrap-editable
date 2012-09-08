<?php

Yii::import('ext.editable.EditableField');
Yii::import('zii.widgets.CDetailView');

class EditableDetailView extends CDetailView
{
    //common url for all editables
    public $url = '';

    //set bootstrap css
    public $htmlOptions = array('class'=> 'table table-bordered table-striped table-hover table-condensed');

    protected $originalNullDisplay = null;

    public function init()
    {
        if (!$this->data instanceof CModel) {
            throw new CException('Property "data" should be of CModel class.');
        }

        //we need set nullDisplay = '' to render correctly. but save original value to pass into js emptytext param
        $this->originalNullDisplay = ($this->nullDisplay === null) ? Yii::t('zii', 'Not set') : $this->nullDisplay;
        $this->nullDisplay = '';

        parent::init();
    }

    protected function renderItem($options, $templateData)
    {
        //if editable set to false --> not editable
        $isEditable = array_key_exists('editable', $options) && $options['editable'] !== false;

        //if name not defined or it is not safe --> not editable
        $isEditable = !empty($options['name']) && $this->data->isAttributeSafe($options['name']);
       
        if ($isEditable) {
            //convert to array
            if(!array_key_exists('editable', $options) || !is_array($options['editable'])) $options['editable'] = array();

            //take common url
            if (!array_key_exists('url', $options['editable'])) {
                $options['editable']['url'] = $this->url;
            }

            $editableOptions = CMap::mergeArray($options['editable'], array(
                'model'     => $this->data,
                'attribute' => $options['name'],
                'emptytext' => $this->originalNullDisplay,
            ));

            //if value in detailview options provided, set text directly
            if(array_key_exists('value', $options)) {
                $editableOptions['text'] = $templateData['{value}'];
                $editableOptions['encode'] = false;
            }

            $templateData['{value}'] = $this->controller->widget('EditableField', $editableOptions, true);
        }

        parent::renderItem($options, $templateData);
    }

}

