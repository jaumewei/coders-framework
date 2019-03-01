<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Definición de los proveedores de la aplicación
 */
interface IModel{
    /**
     * Descriptor del getter genérico de los modelos
     */
    function get( $var, $default = null );
}