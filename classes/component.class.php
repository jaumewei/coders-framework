<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Define un modelo de componente básico para gestionar configuraciones en las 
 * subclases
 */
abstract class Component{
    /**
     * Configuración del componente
     * @var array
     */
    private $_settings = array();
    /**
     * @return string
     */
    public function __toString() {
        
        $class = explode('\\',get_class($this));
        
        return $class[ count( $class ) - 1 ];
    }
    /**
     * @return array
     */
    public function __settings(){

        return $this->_settings;
    }
    /**
     * @return string
     */
    public function __class(){
        return get_class($this);
    }
    /**
     * @return string
     */
    public function __nominalize(){
        return \CodersApp::nominalize($this);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name,'');
    }
    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->set($name, $value);
    }
    /**
     * Obtiene un valor
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get( $var, $default = null ){
        return isset($this->_settings[$var]) ?
            $this->_settings[$var] :
            $default;
    }
    /**
     * Establece un valor
     * @param string $var
     * @param mixed $val
     * @return \TripManComponent
     */
    public function set( $var, $val ){
        $this->_settings[$var] = $val;
        return $this;
    }
    /**
     * @return string
     */
    public function getName(){
        
        return get_class( $this );
    }
    /**
     * @return string Local component Path
     */
    public function getPath(){
        
        $class = new \ReflectionClass(get_called_class());
        // within either sub or parent class, provided the instance is a sub class
        //$ref = new \ReflectionObject($this);
        // filename
        return dirname( $class->getFileName() );
    }
}


