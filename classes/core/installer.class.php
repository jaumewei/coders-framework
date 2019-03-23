<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * 
 */
final class Installer{
    
    private $_name, $_key;
    
    public final function __construct( $name , $key ) {
        
        $this->_key = $key;
        
        $this->_name = $name;
    }
    /**
     * Create all app data
     * @return boolean
     */
    public final function install(){
        
        return TRUE;
    }
    /**
     * Remove all app data
     * @return boolean
     */
    public final function uninstall(){
        
        return TRUE;
    }
}


