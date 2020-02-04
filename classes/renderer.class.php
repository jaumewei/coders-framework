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
     * @return String|HTML HTML output
     */
    protected static function __html( $tag, $attributes = array( ), $content = NULL ){
        
        if( isset( $attributes['class'])){
            if(is_array($attributes['class'])){
                $attributes['class'] = implode(' ', $attributes['class']);
            }
        }
        
        $serialized = array();
        
        foreach( $attributes as $att => $val ){
        
            $serialized[] = sprintf('%s="%s"',$att,$val);
        }
        
        if( !is_null($content) ){

            if(is_object($content)){
                $content = strval($content);
            }
            elseif(is_array($content)){
                $content = implode(' ', $content);
            }
            
            return sprintf('<%s %s>%s</%s>' , $tag ,
                    implode(' ', $serialized) , strval( $content ) ,
                    $tag);
        }
        
        return sprintf('<%s %s />' , $tag , implode(' ', $attributes ) );
    }
    /**
     * @param string $name
     * @return string|HTML
     */
    protected function __input( $name ){
        
        if( !is_null( $this->_model) &&  $this->_model->hasField($name)){
            
            switch( $this->_model->getFieldType($name)){
                case Dictionary::TYPE_DROPDOWN:
                    return self::inputDropDown(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_DROPDOWN:
                    return self::inputList(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_OPTION:
                    return self::inputOptionList(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_USER:
                case Dictionary::TYPE_ID:
                    return '<b>not implemented</b>';
                case Dictionary::TYPE_CHECKBOX:
                    return self::inputCheckBox($name,
                            $this->__value($name),
                            1,
                            array('class'=>'form-input'));
                case Dictionary::TYPE_NUMBER:
                case Dictionary::TYPE_FLOAT:
                case Dictionary::TYPE_PRICE:
                    return self::inputNumber(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_FILE:
                    return self::inputFile(
                            $name,
                            array( 'class' => 'form-input' ));
                case Dictionary::TYPE_DATE:
                case Dictionary::TYPE_DATETIME:
                    return self::inputDate(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_EMAIL:
                    return self::inputEmail(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_TELEPHONE:
                    return self::inputTelephone(
                            $name,
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_PASSWORD:
                    return self::inputPassword(
                            $name,
                            array('class' => 'form-input' ) );
                case Dictionary::TYPE_TEXTAREA:
                    return  self::inputTextArea(
                            $name,
                            $this->__value($name),
                            array( 'cass' => 'form-input' ) );
                default:
                    return self::inputText(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
            }
        }

        return sprintf('<!-- INPUT %s NOT FOUND -->',$name);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function __value( $name ){
        
        if( !is_null( $this->_model)){

            return $this->_model->$name;
        }

        return sprintf('<!-- DATA %s NOT FOUND -->',$name);
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
     * @param string $name
     * @return string
     */
    protected function __label( $name ){

        if( !is_null( $this->_model)){
            $meta = $this->_model->getFieldMeta($name);
            return array_key_exists('label', $meta) ? $meta['label'] : $name;
        }

        return $name;
    }
    /**
     * 
     * @param string $display
     * @return string
     */
    public function __display( $display ){

        $path = $this->getView($display);
        
        if(file_exists($path )){
        
            require $path;
        }
        
        return sprintf('<!-- display_%s -->',$display);
    }
    public function __render( $render ){
        return '';
    }
    /**
     * <meta />
     * @param array $attributes
     * @param string $name
     * @return HTML
     */
    public function renderMeta( array $attributes , $name = null ){
        
        if( !is_null($name)){

            $attributes['name'] = $name;
        }
        
        foreach( $attributes as $attribute => $value ){
            if(is_array($value)){
                $valueInput = array();
                foreach( $value as $valueVar => $valueVal ){
                    $valueInput[] = sprintf('%s=%s',$valueVar,$valueVal);
                }
                $attributes[] = sprintf('%s="%s"',$attribute, implode(', ', $valueInput) );
            }
            else{
                $attributes[] = sprintf('%s="%s"',$attribute,$value);
            }
        }
        
        return $this->__html('meta', $attributes );
    }
    /**
     * <link />
     * @param URL $url
     * @param string $type
     * @param array $attributes
     * @return HTML
     */
    public static final function renderExternalLink( $url , $type , array $attributes = array( ) ){
        
        $attributes[ 'href' ] = $url;
        
        $attributes[ 'type' ] = $type;
        
        return self::__html( 'link', $attributes );
    }
    /**
     * <a href />
     * @param type $url
     * @param type $label
     * @param array $atts
     * @return HTML
     */
    public static function renderLink( $url , $label , array $atts = array( ) ){
        
        $atts['href'] = $url;
        
        if( !isset($atts['target'])){
            $atts['target'] = '_self';
        }
        
        return self::__html('a', $atts, $label);
    }
    /**
     * <ul></ul>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function renderListUnsorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::__html('li', array('class'=>$itemClass) , $item ) :
                    self::__html('li', array(), $item ) ;
        }
        
        return self::__html( 'ul' , $atts ,  $collection );
    }
    /**
     * <ol></ol>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function renderListOrdered( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::__html('li', array('class'=>$itemClass) , $item ) :
                    self::__html('li', array(), $item ) ;
        }
        
        return self::__html( 'ol' , $atts ,  $collection );
    }
    /**
     * <span></span>
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static final function renderSpan( $content , $atts = array( ) ){
        return self::__html('span', $atts , $content );
    }
    /**
     * <img src />
     * @param string/URL $src
     * @param array $atts
     * @return HTML
     */
    public static final function renderImage( $src , array $atts = array( ) ){
        
        $atts['src'] = $src;
        
        return self::__html('img', $atts);
    }
    /**
     * <label></label>
     * @param string $input
     * @param string $text
     * @param mixed $class
     * @return HTML
     */
    public static function renderLabel( $text , array $atts = array() ){

        return self::__html('label', $atts, $text);
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param array $atts
     * @return HTML
     */
    public static function inputNumber( $name, $value = 0, array $atts = array() ){
        
        if( !isset($atts['min'])){ $atts['min'] = 0; }

        if( !isset($atts['step'])){ $atts['step'] = 1; }
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $atts['name'] = $name;
        
        $atts['value'] = $value;
        
        $atts['type'] = 'number';
        
        return self::__html('input', $atts);
    }
    /**
     * <span class="price" />
     * @param string $name
     * @param int $value
     * @param string $coin
     * @return HTML
     */
    public static function price( $name, $value = 0.0, $coin = '&eur', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        return self::__html('span',
                $atts ,
                $value . self::__html('span', array('class'=>'coin'), $coin));
    }
    /**
     * <textarea></textarea>
     * @param string $name
     * @param string $value
     * @param array $atts
     */
    public static function inputTextArea( $name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        
        return self::__html('textarea', $atts, $value);
    }
    /**
     * <input type="text" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputText($name, $value = '', array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'text';
        
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="password" />
     * @param string $name
     * @param array $atts
     * @return HTML
     */
    public static function inputPassword( $name, array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['type'] = 'password';
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="search" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputSearch( $name, $value = '' , array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'search';
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="date" />
     * Versión con jQuery UI
     * <input type="text" class="hasDatepicker" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDate($name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'date';
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="tel" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputTelephone($name, $value = null, array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'tel';
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="email" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputEmail($name, $value = '', array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'email';
        return self::__html( 'input' , $atts );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param array $atts
     * @return HTML
     */
    public static function inputCheckBox( $name, $checked = false , $value = 1, array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'checkbox';
        if($checked){ $atts['checked'] = 1; }
        return self::__html( 'input' , $atts );
    }
    /**
     * Lista de opciones <input type="radio" />
     * @param String $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputOptionList( $name, array $options, $value = null, array $atts = array( ) ){


        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $radioItems = array();
        
        $baseAtts = array( 'type' => 'radio' , 'name' => $name );
        
        if( isset($atts['disabled']) ){
            $baseAtts['disabled'] = 'disabled';
            unset($atts['disabled']);
        }
        
        foreach( $options as $option => $label){
            
            $optionAtts = array_merge($baseAtts,array('value'=>$option));
            
            if( !is_null($value) && $option == $value ){
                $optionAtts['checked'] = 'checked';
            }
            
            $radioItems[ ] = self::__html(
                    'li',
                    array(),
                    self::__html( 'input', $optionAtts, $label) );
        }
        
        return self::__html('ul', $atts, implode('</li><li>',  $radioItems));
    }
    /**
     * <select size="5" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputList($name, array $options, $value = null, array $atts = array() ){
        
        if( !isset($atts['id']) ){
            preg_replace('/-/', '_',  $name );
        }
        
        if( !isset($atts['size'])){
            $atts['size'] = 5;
        }
        
        $items = array();
        
        if( isset($atts['placeholder'])){
            $items[''] = sprintf('- %s -', $atts['placeholder'] );
            unset($atts['placeholder']);
        }
        
        foreach( $options as $option => $label ){
            $items[] = self::__html(
                    'option',
                    $option == $value ? array('value'=> $option,'selected') : array('value'=>$option),
                    $label);
        }
        
        return self::__html('select', $atts, $options );
    }
    /**
     * <select size="1" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDropDown($name, array $options, $value = null, array $atts = array() ){
        
        $atts['size'] = 1;
        
        return self::inputList( $name ,
                $options,
                $value, $atts);
    }
    /**
     * <input type="hidden" />
     * @param string $name
     * @param string $value
     * @return HTML
     */
    public static function inputHidden( $name, $value ){
        
        return self::__html('input', array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ));
    }
    /**
     * <input type="file" />
     * @param string $name
     * @return HTML
     */
    public static function inputFile( $name , array $atts = array( ) ){
        
        $max_filesize = 'MAX_FILE_SIZE';
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_', $name);
        $atts['name'] = $name;
        $atts['type'] = 'file';
        
        $file_size =  pow(1024, 2);
        
        if( isset($atts[$max_filesize]) ){
            $file_size = $atts[$max_filesize];
            unset($atts[$max_filesize]);
        }
        
        return self::inputHidden( $max_filesize, $file_size ) . self::__html('file', $atts );
    }
    /**
     * <button type="*" />
     * @param string $name
     * @param string $value
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static function inputButton( $name, $value , $content, array $atts = array( ) ){
        
        $atts['value'] = $value;
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name ) . '_' . $value;
        $atts['name'] = $name;
        if( !isset($atts['type'])){
            $atts['type'] = 'button';
        }
        return self::__html('button', $atts, $content);
    }
    /**
     * <button type="submit" />
     * @param string $name
     * @param string $value
     * @param string $label
     * @param array $atts
     * @return HTML
     */
    public static function inputSubmit( $name , $value , $label , array $atts = array( ) ){
        
        return self::inputButton($name,
                $value,
                $label,
                array_merge( $atts , array( 'type'=>'submit' ) ));
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
}




