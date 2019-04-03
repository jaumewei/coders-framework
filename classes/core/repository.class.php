<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Allow only child classes to access the repository core attributes
 */
abstract class Repository extends Component{
    
    protected function __create( array $meta , $content = '' ){
        
    }
    
    protected function __save( ){
        
    }
    
    protected function __upload( $input ){
        
        $file = array_key_exists($input, $_FILES) ? $_FILES[ $input ] : array();
        
        if( count( $file )){
            
            
            
        }
        
        return FALSE;
    }
}


