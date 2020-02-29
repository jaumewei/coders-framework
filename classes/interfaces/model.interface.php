<?php namespace CODERS\Framework;
defined('ABSPATH') or die;
/**
 * Model definition methods
 */
interface IModel{
    /**
     * 
     */
    function __toString();
    /**
     * 
     */
    function get( $name );
    /**
     * @param string $var
     * @return boolean
     */
    function has( $var );
}