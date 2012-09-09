<?php
/**
 * EditableSaver class file.
 * 
 * This component is servar-side part for editable widgets. It performs update of model attribute.
 * 
 * @author Vitaliy Potapov <noginsk@rambler.ru>
 * @link https://github.com/vitalets/yii-bootstrap-editable
 * @copyright Copyright &copy; Vitaliy Potapov 2012
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version 0.1.0
 */
 
class EditableBackend extends CComponent
{
    /**
     * scenarion used in model for update
     *
     * @var mixed
     */
    public $scenario = 'editable';

    /**
     * name of model
     *
     * @var mixed
     */
    public $modelClass;
    /**
     * primaryKey value
     *
     * @var mixed
     */
    public $primaryKey;
    /**
     * name of attribute to be updated
     *
     * @var mixed
     */
    public $attribute;
    /**
     * model instance
     *
     * @var CActiveRecord
     */
    public $model;

    /**
     * attributes to save. Yet not used
     */
    public $attributes = array();

    /**
     * http status code ruterned for errors
     */
    public $errorHttpCode = 400;

    /**
     * Constructor
     *
     * @param mixed $modelName
     * @return EditableBackend
     */
    public function __construct($modelClass)
    {
        if (empty($modelClass)) {
            $this->error('Empty model name!');
        }
        $this->modelClass = ucfirst($modelClass);
    }

    /**
     * main function called to update column in database
     *
     */
    public function update()
    {
        //set params from request
        $this->primaryKey = yii::app()->request->getParam('pk');
        $this->attribute = yii::app()->request->getParam('name');
        $value = yii::app()->request->getParam('value');

        //checking params
        if (empty($this->attribute)) {
            $this->error('Empty attribute!');
        }
        if (empty($this->primaryKey)) {
            $this->error('Empty primaryKey!');
        }

        //loading model
        $this->model = CActiveRecord::model($this->modelClass)->findByPk($this->primaryKey);
        if (empty($this->model)) {
            $this->error('Model ' . $this->modelClass . ' not found by primary key "' . $this->primaryKey . '"');
        }
        $this->model->setScenario($this->scenario);

        //is attribute exists
        if (!$this->model->hasAttribute($this->attribute)) {
            $this->error('Model ' . $this->modelClass . ' does not have attribute "' . $this->attribute . '"');
        }

        //is attribute safe
        if (!$this->model->isAttributeSafe($this->attribute)) {
            $this->error('Model ' . $this->modelClass . ' rules do not allow to update attribute "' . $this->attribute . '"');
        }

        //setting new value
        $this->model->setAttribute($this->attribute, $value);

        //validate
        $this->model->validate(array($this->attribute));
        if ($this->model->hasErrors()) {
            $this->error($this->model->getError($this->attribute));
        }

        //save
        if ($this->beforeUpdate()) {
            //saving
            //TODO: save only changed attributes
            if (!$this->model->save(false)) {
                $this->error('Error while saving value!');
            }
            $this->afterUpdate();
        } else {
            $firstError = reset($this->model->getErrors());
            $this->error($firstError[0]);
        }
    }

    /**
     * This event is raised before the update is performed.
     * @param CModelEvent $event the event parameter
     */
    public function onBeforeUpdate($event)
    {
        $this->raiseEvent('onBeforeUpdate', $event);
    }

    /**
     * This event is raised after the update is performed.
     * @param CEvent $event the event parameter
     */
    public function onAfterUpdate($event)
    {
        $this->raiseEvent('onAfterUpdate', $event);
    }

    /**
     * errors handling
     * @param $msg
     * @throws CHttpException
     */
    protected function error($msg)
    {
        throw new CHttpException($this->errorHttpCode, $msg);
    }

    /**
     * beforeUpdate
     *
     */
    protected function beforeUpdate()
    {
        $this->onBeforeUpdate(new CEvent($this));
        return !$this->model->hasErrors();
    }

    /**
     * afterUpdate
     *
     */
    protected function afterUpdate()
    {
        $this->onAfterUpdate(new CEvent($this));
    }
}
