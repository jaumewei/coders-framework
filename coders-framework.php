<?php defined('ABSPATH') or die;
/*******************************************************************************
 * Plugin Name: Coders Framework
 * Plugin URI: https://coderstheme.org
 * Description: Framework Prototype
 * Version: 1.0.0
 * Author: Jaume Llopis
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_framework
 * Class: CodersApp
 * 
 * @author Jaume Llopis <jaume@mnkcoder.com>
 ******************************************************************************/
abstract class CodersApp{
    
    const TYPE_INTERFACES = 0;
    const TYPE_CORE = 100;
    const TYPE_PROVIDERS = 200;
    const TYPE_SERVICES = 300;
    const TYPE_MODELS = 400;
    const TYPE_EXTENSIONS = 500;
    
    /**
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @var \CodersApp[] Singleton of Instances
     */
    private static $_instanceMgr = [];
    /**
     * @var \CODERS\Framework\HookManager
     */
    private $_hookMgr = null;
    /**
     * Componentes cargados
     * @var array
     */
    private $_components = [
        self::TYPE_INTERFACES => [
            'service',
            'plugin',
            'model',
            'template',
            'widget'],
        self::TYPE_CORE => [
            'component',
            'hook-manager',
            'dictionary',
            'controller',
            'renderer',
            'service',
        ],
        self::TYPE_PROVIDERS => [
            
        ],
        self::TYPE_SERVICES => [
            
        ],
        self::TYPE_MODELS => [
            
        ],
        self::TYPE_EXTENSIONS => [
            
        ],
    ];
    /**
     * 
     */
    protected function __construct() {
        
        $this->__initializeFramework()->__hook();
        
    }
    /**
     * @return string
     */
    public final function __toString() {
        return self::nominalize(get_class($this));
    }
    /**
     * @return string
     */
    public static final function applicationName(){
        $application = get_called_class();
        if(substr($application, 0,6) === 'Coders'){
            $to = strrpos($application, 'App');
            if( $to > 6 ){
                return substr($application, 6, $to - 6 ) ;
            }
        }
        return '';
    }
    /**
     * Ruta local de contenido de la aplicación
     * @return string
     */
    public static final function applicationPath(){
        
        return sprintf('%s/../coders-%s/',
                plugin_dir_path(__FILE__) ,
                self::nominalize( self::applicationName() ) );
    }
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public static final function applicationURL( ){
        
        return preg_replace( '/coders-framework/',
                sprintf('coders-%s',self::applicationName()),
                plugin_dir_url(__FILE__) );
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|boolean
     */
    public static final function __callStatic( $name, $arguments) {
        switch( $name ){
            case 'instance':
                return self::__instance( sprintf('coders-%s',self::nominalize( self::applicationName())));
        }
        
        return FALSE;
    }
    /**
     * @return CodersApp
     */
    private final function __initializeFramework(){
        foreach( $this->_components as $type => $list ){
            foreach( $list as $member ){
                
                $path = self::componentPath($member, $type);

                if( $path !== FALSE && file_exists($path)){
                    
                    require_once $path;
                        
                    $class = self::componentClass($member, $type);
                    
                    if( $class !== FALSE ){
                        //
                    }
                }
            }
        }
        return $this;
    }
    /**
     * @param string $view
     * @param boolean $getLocale
     * @return string
     */
    public final function endPoint( $view , $getLocale = FALSE ){
        
        if( $getLocale ){

            return $view;
        }

        $locale = get_locale();

        $translations = array(
            'es-ES' => 'intranet',
            'en-GB' => 'network',
            'en-US' => 'network',
        );

        return array_key_exists($locale, $translations) ? $translations[$locale] : $view;
    }
    /**
     * Esto irá mejor en el renderizador del sistema
     * @param string $view
     */
    public static final function redirect_template( $view = 'default' ){
        
        $path = sprintf('%s/html/%s.template.php',__DIR__,$view);

        if(file_exists($path)){
            require $path;
        }
        else{
            printf('<!-- TEMPLATE_NOT_FOUND[%s] -->',$view);
        }
    }
    /**
     * Cargar gestor de hooks
     * @return \CodersApp
     */
    private final function __hook(){

        if(class_exists('\CODERS\Framework\HookManager')){
            $this->_hookMgr = new \CODERS\Framework\HookManager( $this );
        }
        
        return $this;
    }

    /**
     * 
     * @param String $app
     * @return \CodersApp
     */
    protected static final function __instance( $app ){
        
        if(!array_key_exists($app,self::$_instanceMgr)){
            //si es nulo, simplemente devuelve nulo (se utilizará como invalidador)
            self::$_instanceMgr[ $app ] = self::importApplication( $app );
            
        }
        
        return self::$_instanceMgr[$app];
    }
    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    public final function getOption( $option ,  $default = null ){
        
        $option_key = sprintf('%s_%s', strval($this),$option);
        
        return get_option($option_key, $default);
    }
    /**
     * @param string $option
     * @param mixed $value
     * @param bool $autoload
     * @return boolean
     */
    protected final function setOption( $option , $value ,$autoload = FALSE ){
        
        $option_key = sprintf('%s_%s', strval($this),$option);
        
        return update_option($option_key, $value, $autoload );
    }
    /**
     * Registra un componente del framework
     * @param string $component
     * @param int $type
     * @return \CodersApp
     */
    protected function register( $component , $type = self::TYPE_MODELS ){
        
        if( $type > self::TYPE_CORE ){
            if(array_key_exists($type, $this->_components) && !in_array( $component ,$this->_components[$type]) ){
                $this->_components[ $type ][] = $component;
            }
        }
        
        return $this;
    }
    /**
     * 
     * @param mixed $element
     * @return string
     */
    public static final function nominalize( $element ){
        $class_name =  is_object($element) ? get_class( $element ) : $element;
        if( !is_null($class_name)){
            if(is_string($class_name)){
                $name = explode('\\', $class_name );
                return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-',  $name[ count($name) - 1 ] ) );
            }
        }
        return $class_name;
    }
    /**
     * 
     * @param mixed $element
     * @return string
     */
    public static final function classify( $element ){
        $chunks = explode('-', $element);
        $output = array();
        foreach( $chunks  as $string ){
            $output[] = strtoupper( substr($string, 0,1) ) . substr($string, 1, strlen($string)-1);
        }
        return implode('', $output);
    }
    /**
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @param string $application
     * @return \CodersApp
     */
    private static final function importApplication( $application ){

        //$path = sprintf('%s/modules/%s/%s.module.php',CODERS_FRAMEWORK_BASE ,$name,$name);
        $path = sprintf('%s/../%s/application.php',__DIR__,$application);
        
        $class = sprintf('%sApp',self::classify($application) );
        
        if(file_exists($path)){

            require_once $path;
            
            if(class_exists($class) && is_subclass_of( $class , self::class,TRUE)){

                return new $class();
            }
            else{
                die(sprintf('INVALID APPLICATION [%s]',$class) );
            }
        }
        else{
            die(sprintf('INVALID PATH [%s]',$path) );
        }
        
        return NULL;
    }
    /**
     * @param String $component
     * @param int $type
     * @return String|boolean
     */
    protected static final function componentClass( $component , $type = self::TYPE_MODELS ){
        
        switch( $type ){
            case self::TYPE_INTERFACES:
                return sprintf('\CODERS\Framework\I%s', self::classify($component));
            case self::TYPE_CORE:
                return sprintf('\CODERS\Framework\%s', self::classify($component));
            case self::TYPE_PROVIDERS:
                return sprintf('\CODERS\Framework\Providers\%s', self::classify($component));
            case self::TYPE_SERVICES:
                return sprintf('\CODERS\Framework\Services\%s', self::classify($component));
            case self::TYPE_MODELS:
                return sprintf('\CODERS\Framework\Models\%sModel', self::classify($component));
            case self::TYPE_EXTENSIONS:
                return sprintf('\CODERS\Framework\Plugins\%sPlugin', self::classify($component));
        }
        
        return FALSE;
    }
    /**
     * 
     * @param String $component
     * @param int $type
     * @return String | boolean
     */
    protected static final function componentPath( $component , $type = self::TYPE_MODELS ){

        switch( $type ){
            case self::TYPE_INTERFACES:
                return sprintf('%s/classes/interfaces/%s.interface.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_CORE:
                return sprintf('%s/classes/core/%s.class.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_PROVIDERS:
                return sprintf('%s/classes/providers/%s.provider.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_SERVICES:
                return sprintf('%s/classes/services/%s.interface.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_MODELS:
                return sprintf('%s/classes/models/%s.model.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_EXTENSIONS:
                return sprintf('%s/classes/plugins/%s.plugin.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
        }
            
        return FALSE;
    }
    /**
     * Inicialización
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @return \CodersApp
     */
    /*public static final function instance(){
        
        return self::$_instance;
    }*/
    /**
     * Inicialización
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @param string $application
     * @return \CodersApp|NULL
     */
    public static function init( $application = '' ){
        if( !defined('CODERS_FRAMEWORK_BASE')){
            //first instance to call
            define('CODERS_FRAMEWORK_BASE',__DIR__);
        }
        else{
            return strlen($application) ? self::__instance( $application ) : NULL;
        }
        
        return NULL;
    }
}

/**
 * Inicializar aplicación
 */
CodersApp::init();




