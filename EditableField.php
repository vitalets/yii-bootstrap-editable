<?php

/**
 * Widget for Bootstrap Editable item
 */
class EditableField extends CWidget
{
    // for all types
    public $model = null;
    public $attribute = null;
    public $type = null;
    public $url = null;
    public $title = null;
    public $emptytext = null;
    public $text = null; //will be used as content
    public $value = null;
    public $placement = null;

    // for select
    public $source = array();
    public $autotext = null;
    public $prepend = null;

    // for date
    public $format = null;
    public $language = null;
    public $weekStart = null;
    public $startView = null;

    //methods
    public $validate = null;
    public $success = null;
    public $error = null;

    //js options
    public $options = array();
    //html options
    public $htmlOptions = array();

    //weather to encode text on output
    public $encode = true;

    //if false text will not be editable, but will be rendered
    public $enabled = null;

    public function init()
    {   //todo: добавить onUpdate !!!
        parent::init();

        if (!$this->model) {
            throw new CException('Parameter "model" should be provided for Editable');
        }
        if (!$this->attribute) {
            throw new CException('Parameter "attribute" should be provided for Editable');
        }
        if (!$this->model->hasAttribute($this->attribute)) {
            throw new CException('Model "'.get_class($this->model).'" does not have attribute "'.$this->attribute.'"');
        }        
                
        if ($this->type === null) {
            $this->type = 'text';
            //try detect type from metadata.
            if (array_key_exists($this->attribute, $this->model->tableSchema->columns)) {
                $dbType = $this->model->tableSchema->columns[$this->attribute]->dbType;
                if($dbType == 'date' || $dbType == 'datetime') $this->type = 'date';
                if(stripos($dbType, 'text') !== false) $this->type = 'textarea';
            }
        }

        //generate text (except select)
        if ($this->text === null && $this->type != 'select') {
            //autotext option assumed --> do not set text from model attribute
            $this->text = $this->model->getAttribute($this->attribute);
        }

        //if enabled not defined directly, set it to true only for safe attributes
        if($this->enabled === null) {
            $this->enabled = $this->model->isAttributeSafe($this->attribute);
        }
        //if not enabled --> just print text        
        if (!$this->enabled) {
            return;
        }

        //lang: take from config is exists
        if ($this->language === null && yii::app()->language) {
            $this->language = yii::app()->language;
        }

        //normalize url from array if needed
        $this->url = CHtml::normalizeUrl($this->url);

        //generate title from attribute label
        if ($this->title === null) {
            //todo: i18n here. Add messages folder to extension
            $this->title = (($this->type == 'select' || $this->type == 'date') ? Yii::t('editable', 'Select') : Yii::t('editable', 'Enter')) . ' ' . $this->model->getAttributeLabel($this->attribute);
        }

        $this->buildHtmlOptions();
        $this->buildJsOptions();
        $this->registerAssets();
    }

    public function run()
    {
        if($this->enabled) {
            $this->registerClientScript();
            $this->renderLink();
        } else {
            $this->renderText();
        }
    }

    public function renderLink()
    {
        echo CHtml::openTag('a', $this->htmlOptions);
        $this->renderText();
        echo CHtml::closeTag('a');
    }

    public function renderText()
    {
        $encodedText = $this->encode ? CHtml::encode($this->text) : $this->text;
        if($this->type == 'textarea') {
             $encodedText = preg_replace('/\r?\n/', '<br>', $encodedText);
        }
        echo $encodedText;
    }

    public function buildHtmlOptions()
    {
        //html options
        $htmlOptions = array(
            'href'      => '#',
            'rel'       => $this->getSelector(),
            'data-pk'   => $this->model->primaryKey,
        );

        //for select we need to define value directly
        if ($this->type == 'select') {
            $this->value = $this->model->getAttribute($this->attribute);
            $this->htmlOptions['data-value'] = $this->value;
        }

        //merging options
        $this->htmlOptions = CMap::mergeArray($this->htmlOptions, $htmlOptions);
    }

    public function buildJsOptions()
    {
        $options = array(
            'type'  => $this->type,
            'url'   => $this->url,
            'name'  => $this->attribute,
            'title' => CHtml::encode($this->title),
        );

        if ($this->emptytext) {
            $options['emptytext'] = $this->emptytext;
        }
        
        if ($this->placement) {
            $options['placement'] = $this->placement;
        }

        switch ($this->type) {
            case 'select':
                if ($this->source) {
                    $options['source'] = $this->source;
                }
                if ($this->autotext) {
                    $options['autotext'] = $this->autotext;
                }
                if ($this->prepend) {
                    $options['prepend'] = $this->prepend;
                }
                break;
            case 'date':
                if ($this->format) {
                    $options['format'] = $this->format;
                }
                if ($this->language) {
                    $options['datepicker']['language'] = $this->language;
                }
                if ($this->weekStart !== null) {
                    $options['weekStart'] = $this->weekStart;
                }
                if ($this->startView !== null) {
                    $options['startView'] = $this->startView;
                }
                break;
            case 'typeahead':
                if ($this->source) {
                    $options['source'] = $this->source;
                }
                break;
        }

        //methods
        if ($this->validate !== null) {
            $options['validate'] = (strpos($this->validate, 'js:') !== 0 ? 'js:' : '') . $this->validate;
        }
        if ($this->success !== null) {
            $options['success'] = (strpos($this->success, 'js:') !== 0 ? 'js:' : '') . $this->success;
        }
        if ($this->error !== null) {
            $options['error'] = (strpos($this->error, 'js:') !== 0 ? 'js:' : '') . $this->error;
        }

        //merging options
        $this->options = CMap::mergeArray($this->options, $options);
    }

    public function registerClientScript()
    {
        $options = CJavaScript::jsonEncode($this->options);
        Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->id, "$('a[rel={$this->htmlOptions['rel']}]').editable($options)");
    }


    public function registerAssets()
    {
        //if bootstrap extension installed, but no js registered -> register it!
        if (($bootstrap = yii::app()->getComponent('bootstrap')) && !$bootstrap->enableJS) {
            $bootstrap->registerCorePlugins(); //enable bootstrap js if needed
        }

        $assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('ext.editable.assets.bootstrap-editable'), false, 1); //publish excluding datepicker locales
        Yii::app()->getClientScript()->registerCssFile($assetsUrl . '/css/bootstrap-editable.css');
        Yii::app()->clientScript->registerScriptFile($assetsUrl . '/js/bootstrap-editable.js', CClientScript::POS_END);

        //include locale for datepicker
        if ($this->type == 'date' && $this->language && substr($this->language, 0, 2) != 'en') {
             //todo: check compare dp locale name with yii's
             $localesUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('ext.editable.assets.bootstrap-editable.js.locales'));
             Yii::app()->clientScript->registerScriptFile($localesUrl . '/bootstrap-datepicker.'. str_replace('_', '-', $this->language).'.js', CClientScript::POS_END);
        }
    }

    public function getSelector()
    {
        return get_class($this->model) . '_' . $this->attribute . ($this->model->primaryKey ? '_' . $this->model->primaryKey : '');
    }
}