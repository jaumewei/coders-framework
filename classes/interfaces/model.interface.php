<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Definición de los proveedores de la aplicación
 */
interface IModel{
    /**
     * 
     */
    function __toString();
    /**
     * Descriptor del getter genérico de los modelos
     */
    function get( $var, $default = null );
    /**
     * @param string $var
     * @return boolean
     */
    function has( $var );
    /**
     * @return array
     */
    function toArray( );
}