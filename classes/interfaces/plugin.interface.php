<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Interfaz de creación y carga de plugins
 */
interface IPlugin{
    
    const OPTION_ENABLED = 'enabled';
    
    const OPTION_DISABLED = 'disabled';
    
    /**
     * Inicializa el plugin
     */
    function setup();
    /**
     * Obtiene un valor del plugin
     */
    function get( $var, $default = null);
    /**
     * Establece un parámetro del plugin
     */
    function set( $var, $value );
    /**
     * Ejecuta un método del plugin
     * @param string $command Comando a ejecutar
     * @param mixed $data Datos a procesar
     * @return mixed Resultado
     */
    function run( $command = null, $data = null );
}