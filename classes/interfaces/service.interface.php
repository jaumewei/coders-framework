<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Interfaz aplicable a servicios cargados automáticamente por el sistema
 * o instanciados por la acción  de un usuario
 */
interface IService{
    /**
     * Ejecuta el servicio
     * @return boolean Resultado de la ejecución
     */
    public function dispatch();
    /**
     * Establece la configuración del servicio mediante un setter genérico
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor del parámetro
     */
    public function set( $param , $value );
}