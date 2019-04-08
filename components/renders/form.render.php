<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class FormRender extends Renderer{

    protected function __construct(\CodersApp $app) {
        
        parent::__construct($app);

    }

    public function __get($name) {
        
        if( preg_match('/^form_/', $name) && strlen($name) > 5 ){
            return $this->__input(substr($name, 5));
        }
        else{
            return parent::__get($name);
        }
    }
}
