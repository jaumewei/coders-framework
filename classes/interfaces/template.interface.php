<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Desciptor de contenido para las plantillas de notificación y tickets.
 */
interface ITemplate{
    /**
     * @param \TripManStringProvider $lang Gestor de cadenas para las traducciones
     */
    function getTemplateData( \TripManStringProvider $lang = null );
    /**
     * @return string Idioma del contenido
     */
    function getLanguage();
    /**
     * @param string $var Nombre del parámetro
     * @param mixed $default Valor por omisión
     * @return mixed Getter genérico para acceder a las propiedades del modelo
     */
    function get( $var, $default );
}