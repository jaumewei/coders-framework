<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

use CODERS\Framework\Dictionary;

/**
 * 
 */
abstract class Renderer extends \CODERS\Framework\Component{
    /**
     * @var \CODERS\Framework\Dictionary
     */
    private $_model = NULL;
    
    private $_app, $_module;

    /**
     * @param string $appName
     * @param string $module
     */
    /*protected function __construct( $appName , $module ) {
        
        $this->app = $appName;
        
        $this->module = $module;
    }*/
    protected function getView( $view , $type = 'html' ){
        
        $path = sprintf('%smodules/%s/views/%s/%s.php',
                \CodersApp::appRoot($this->_app),
                $this->_module, $type, $view);
        
        return $path;
    }
    /**
     * @param string $application
     * @param string $module
     * @return \CODERS\Framework\Renderer
     */
    public function setup( $application , $module ){

        $this->_app = $application;

        $this->_module = $module;

        return $this;
    }
    //private $_html = NULL;
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        
        if( preg_match('/^input_/', $name) ){
            return $this->__input(substr($name, 6));
        }
        elseif( preg_match(  '/^list_[a-z_]*_options$/' , $name ) ){
            return $this->__options(substr($name, 5, strlen($name) - 5 - 8 ) );
        }
        elseif( preg_match(  '/^list_/' , $name ) ){
            return $this->__options(substr($name, 5));
        }
        elseif( preg_match(  '/^value_/' , $name ) ){
            return $this->__value(substr($name, 6));
        }
        elseif( preg_match(  '/^display_/' , $name ) ){
            return $this->__display(substr($name, 8));
        }
        elseif( preg_match(  '/^label_/' , $name ) ){
            return $this->__label(substr($name, 6));
        }
        
        return parent::get($name);
    }
    /**
     * @param string $tag
     * @param mixed $attributes
     * @param mixed $content
     * \CODERS\Framework\Views\HTML
     */
    protected static function __html( $tag, $attributes = array( ), $content = NULL ){
        
        if(class_exists('\CODERS\Framework\Views\HTML')){

            return HTML::html($tag, $attributes, $content );
        }
        
        return '<!-- HTML COMPONENT NOT LOADED! -->';
    }
    /**
     * @param string $input
     * @return string
     */
    protected function __input( $input ){
        
        if( !is_null( $this->_model) &&  $this->_model->hasField($input)){
            
            switch( $this->_model->getFieldType($input)){
                case Dictionary::TYPE_DROPDOWN:
                    return HTML::inputDropDown(
                            $input,
                            $this->_model->listOptions($input),
                            $this->_model->getValue($input),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_DROPDOWN:
                    return HTML::inputList(
                            $input,
                            $this->_model->listOptions($input),
                            $this->_model->getValue($input),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_OPTION:
                    return HTML::inputOptionList(
                            $input,
                            $this->_model->listOptions($input),
                            $this->_model->getValue($input),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_USER:
                case Dictionary::TYPE_ID:
                    return '<b>not implemented</b>';
                case Dictionary::TYPE_CHECKBOX:
                    return $this->__html('input', array(
                        'type' => 'checkbox',
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                    ));
                case Dictionary::TYPE_NUMBER:
                case Dictionary::TYPE_FLOAT:
                case Dictionary::TYPE_PRICE:
                    return HTML::inputNumber(
                            $input,
                            $this->_model->getValue($input),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_FILE:
                    return HTML::inputFile($input, array(
                        'class' => 'form-input'
                    ));
                case Dictionary::TYPE_DATE:
                case Dictionary::TYPE_DATETIME:
                    return $this->__html('input', array(
                        'type' => 'date',
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                    ));
                case Dictionary::TYPE_EMAIL:
                    return $this->__html('input', array(
                        'type' => Dictionary::TYPE_EMAIL,
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                    ));
                case Dictionary::TYPE_TELEPHONE:
                    return $this->__html('input', array(
                        'type' => Dictionary::TYPE_TELEPHONE,
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                    ));
                case Dictionary::TYPE_PASSWORD:
                    return $this->__html('input', array(
                        'type' => Dictionary::TYPE_PASSWORD,
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                    ));
                case Dictionary::TYPE_TEXTAREA:
                    return $this->__html('input', array(
                        'type' => Dictionary::TYPE_TEXTAREA,
                        'name' => $input,
                        'class' => 'form-input',
                    ),$this->_model->getValue($input));
                default:
                    return $this->__html('input', array(
                        'type' => 'text',
                        'name' => $input,
                        'value' => $this->_model->getValue($input),
                        'class' => 'form-input',
                        ))->__toHtml();
            }
        }

        return sprintf('<!-- INPUT %s NOT FOUND -->',$input);
    }
    /**
     * @param string $field
     * @return string
     */
    protected function __value( $field ){
        
        if( !is_null( $this->_model)){

            return $this->_model->$field;
        }

        return sprintf('<!-- DATA %s NOT FOUND -->',$field);
    }
    /**
     * @param string $field
     * @return array
     */
    protected function __options( $field ){
        
        return !is_null($this->_model) ?
                $this->_model->listOptions($field) :
                array();
    }
    /**
     * @param string $field
     * @return string
     */
    protected function __label( $field ){

        if( !is_null( $this->_model)){
            $meta = $this->_model->getFieldMeta($field);
            return array_key_exists('label', $meta) ? $meta['label'] : $field;
        }

        return sprintf('<!-- label %s not found -->',$field);
    }
    /**
     * 
     * @param string $display
     * @return string
     */
    public function __display( $display ){

        $path = $this->getView($display);
        //$path = sprintf( '%s/html/%s.php', __DIR__, $display );
        
        if(file_exists($path )){
            require $path;
        }
        
        return sprintf('<!-- display_%s -->',$display);
    }
    /**
     * @return \CODERS\Framework\Views\Renderer
     */
    public function display( $view = 'default' ){
        
        $path = $this->getView( $view );
        
        if(file_exists($path)){
            
            require $path;
        }
        else{
            printf('<!-- VIEW NOT FOUND [%s] -->' , $view );
        }

        return $this;
    }
    /**
     * @param string $title
     * @return \CODERS\Framework\Views\Renderer
     */
    public final function setTitle( $title ){
        
        $this->_title = $title;
        
        return $this;
    }
    /**
     * @param \CODERS\Framework\IModel $model
     * @return \CODERS\Framework\Views\Renderer Instancia para chaining
     */
    public function setModel( \CODERS\Framework\IModel $model ){

        $this->_model = $model;

        return $this;
    }
    /**
     * @return \CODERS\Framework\IModel Modelo de datos
     */
    protected function getModel(){ return $this->_model; }
    /**
     * @param \CodersApp $app
     * @param string $view
     * @param boolean $admin
     * @return boolean|\CODERS\Framework\Views\DocumentRender
     */
    /*public static final function createDocument( \CODERS\Framework\Controller $context ){
        
        if(!class_exists('\CODERS\Framework\Views\DocumentRender')){

            require_once( sprintf('%s/components/renders/document.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        return new DocumentRender( $context->getAppName() );
    }*/
    /**
     * @return \CODERS\Framework\Views\CalendarRender
     */
    /*public static final function createCalendar(){

        if(!class_exists('\CODERS\Framework\Views\CalendarRender')){

            require_once( sprintf('%s/components/renders/calendar.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        return new CalendarRender( );
    }*/
    /**
     * @return \CODERS\Framework\Views\MapRender
     */
    /*public static final function createMap(){
        if(!class_exists('\CODERS\Framework\Views\MapRender')){

            require_once( sprintf('%s/components/renders/document.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        return new MapRender( );
    }*/
    /**
     * @return \CODERS\Framework\Views\FormRender
     */
    /*public static final function createForm(){
        
        if(!class_exists('\CODERS\Framework\Views\FormRender')){

            require_once( sprintf('%s/components/renders/form.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        return new FormRender( );
    }*/
    /**
     * @return \CODERS\Framework\Views\ViewRender
     */
    /*public static final function createView(){
        if(!class_exists('\CODERS\Framework\Views\ViewRender')){

            require_once( sprintf('%s/components/renders/view.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        return new ViewRender( );
    }*/
}




