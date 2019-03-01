<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;
/**
 * Modelo básico para gestionar información genérica
 */
class BaseModel extends TripManComponent{
    
    public function __construct( array $data = null ) {
        if( !is_null($data)){
            foreach( $data as $var => $val){
                $this->set($var, $val);
            }
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return parent::getName();
    }
}