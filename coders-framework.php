<?php defined('ABSPATH') or die;
/*******************************************************************************
 * Plugin Name: Coders Framework
 * Plugin URI: https://coderstheme.org
 * Description: Framework Prototype
 * Version: 1.0.0
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_framework
 * Domain Path: lang
 * Class: CodersApp
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 ******************************************************************************/
abstract class CodersApp{
    //COMPONENTS LOADED THROUGH THE FRAMEWORK SETUP
    const TYPE_INTERFACES = 0;
    const TYPE_CORE = 100;
    //COMPONENTS LOADED THROUGH THE INSTANCE SETUP
    const TYPE_PROVIDERS = 200;
    const TYPE_SERVICES = 300;
    const TYPE_MODELS = 400;
    const TYPE_PLUGINS = 500;
    
    const DEFAULT_EP = 'default';
    /**
     * @var string Base Dir storage for repository setup
     */
    const ROOT_PATH = 'coders_root_path';
    /**
     * CORE COMPONENT DEFINITION
     * @var array
     */
    private static $_framework = [
        self::TYPE_INTERFACES => [
            'service',
            'plugin',
            'model',
            'template',
            'widget'],
        self::TYPE_CORE => [
            'component',
            'db',           //wpdb helper
            'dictionary',
            'request',      //inputs
            'controller',
            'html','renderer',
            'service',
        ],
    ];
    /**
     * Instances must bestored within an array, they're up to be used both in
     * frontend context or in admin/backend- which requires always some preload
     * methods to know all available installed applications.
     * 
     * @var string List of registered instances
     */
    private static $_endPoints = [
        //list here all endpoints by App
    ];
    /**
     * @var string
     */
    /**
     * INSTANCE COMPONENTS
     * @var array
     */
    private $_components = array(
        self::TYPE_PROVIDERS => [ ],
        self::TYPE_SERVICES => [ ],
        self::TYPE_MODELS => [ ],
        self::TYPE_PLUGINS => [ ],
    );
    /**
     * Application custom components and logics
     * @var array
     */
    private $_extensions = array(
        self::TYPE_PROVIDERS => [ ],
        self::TYPE_SERVICES => [ ],
        self::TYPE_MODELS => [ ],
        self::TYPE_PLUGINS => [ ],
    );
    /**
     *
     * @var \CODERS\Framework\Service[]
     */
    private $_services = array(
        //register service contexts here
    );
    /**
     * End point key
     * @var string
     */
    private $_EPK;
    /**
     * End point name
     * @var string
     */
    private $_EPN;
    /**
     * 
     */
    protected function __construct( $key = '' ) {
        //end point name
        $this->_EPN = strval($this);
        //end point key
        $this->_EPK = strlen($key) > 3 ? $key : self::createAppKey($this->_EPN);
        //load all instance required classes and setup
        //$this->importComponents( $this->_components );
        //hook entrypoint and initialize app
        //$this->bindCMS()->__init();
        
        //administración
        $this->registerEndPoint();
    }
    /**
     * @return string
     */
    public final function __toString() {
        
        $class = get_class($this);
        
        if(substr($class, strlen($class)-3) === 'App'){
            
            $class = substr($class, 0, strlen($class)-3);
        }
        
        return self::nominalize($class);
    }
    /**
     * @return string
     */
    public static final function appRoot( $app ){
       
        return (array_key_exists($app, self::$_endPoints)) ?
                sprintf('%s/%s/', preg_replace('/\\\\/', '/', dirname(__DIR__) ),$app) :
                //sprintf('%s/%s/', dirname(__DIR__),$app) :
                '' ;
    }
    /**
     * Ruta local de contenido de la aplicación
     * @return string
     */
    public final function appPath(){
        
        // within either sub or parent class in a static method
        $ref = new ReflectionClass(get_called_class());
        // within either sub or parent class, provided the instance is a sub class
        //$ref = new \ReflectionObject($this);
        // filename
        return dirname( $ref->getFileName() );

        /*return sprintf('%s/../coders-%s/',
                plugin_dir_path(__FILE__) ,
                self::nominalize( self::endPointName() ) );*/
    }
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public final function appURL( ){
        
        return preg_replace( '/coders-framework/',
                //$this->endPointName(),
                $this->endPoint(),
                plugin_dir_url(__FILE__) );
    }
    /**
     * 
     */
    private static final function registerFrameworkMenu(){

        add_action( 'init' , function(){

            if(\CodersApp::isAdmin()){

                add_action( 'admin_menu', function(){
                    //add_menu_page(
                    add_submenu_page(
                        'options-general.php',
                        __('Coders Framework','coders_framework'),
                        __('Coders Framework','coders_framework'),
                        'administrator','coders-framework-manager',
                        function(){

                            $controller_path = sprintf('%s/framework/admin/controller.php',CODERS_FRAMEWORK_BASE);

                            if(file_exists($controller_path)){

                                require_once $controller_path;

                                $C = new \CODERS\Framework\Responses\Framework();

                                $C->execute() || printf('<p>INVALID_MASTERCONTROLLER_RESPONSE [%s]</p>',$controller_path);
                            }
                            else{
                                printf('<p>INVALID_MASTERCONTROLLER_PATH [%s]</p>',$controller_path);
                            }
                        }, 51 );
                },100000);
            }
        });
    }
    /**
     * List all available langguates in the CMS
     * @return array
     */
    protected final function listLanguages(){
        
        $translations = array( );
        
        $locale = wp_get_installed_translations('core');
        
        foreach (array_keys($locale['default']) as $lang) {
            $translations[] = $lang;
        }

        return $translations;
    }
    /**
     * Register all admin controllers here
     * as option => Controller
     * @return \CODERS\Framework\Response[]
     */
    protected function importAdminMenu(){

        return array(
            //option => Controller
        );
    }
    /**
     * Preload all core and instance components
     * @param array $components
     * @param string $app
     */
    private static final function registerComponents( array $components , \CodersApp $app = NULL ){
        foreach( $components as $type => $list ){
            foreach( $list as $member ){
                //load extended classes
                $path = self::componentPath($member, $type , !is_null( $app ) ? strval($app) : '' );
                
                if( $path !== FALSE && file_exists($path)){
                    
                    require_once $path;
                }
                else{
                    throw new Exception( sprintf( 'INVALID_COMPONENT [%s]',$path ) );
                }
            }
        }
    }
    /**
     * Redirect End Point URL
     * @return \CodersApp
     */
    private final function registerEndPoint(){

        if( $this->isAdmin() ){

            $menu = $this->importAdminMenu();

            add_action( 'admin_menu', function() use( $menu ){
                
                foreach ( $menu as $option => $controller) {

                    if (strlen($controller->getParent()) ) {
                        add_submenu_page(
                                $controller->getParent(),
                                $controller->getPageTitle(),
                                $controller->getOptionTitle(),
                                $controller->getCapabilities(),
                                $option,
                                //function() use( $intsance ){ $instance->response(); },
                                array($controller, '__execute'),
                                $controller->getIcon(),
                                $controller->getPosition());
                    }
                    else {
                        //each item is a Page setup class
                        add_menu_page(
                                $controller->getPageTitle(),
                                $controller->getOptionTitle(),
                                $controller->getCapabilities(),
                                $option,
                                //function() use( $intsance ){ $instance->response(); },
                                array($controller, '__execute'),
                                $controller->getIcon(),
                                $controller->getPosition());
                    }
                }
            }, 10000 );
        }
        else{

            $app = $this;
            /* Setup the routing hook */
            add_action( 'init' , function() use($app){

                global $wp, $wp_rewrite;

                if( $app !== FALSE ){
                    //import the regiestered locale's endpoint from the settinsg
                    $endpoint = $app->endPoint($app->getOption('endpoint' ,'default' ) , TRUE );

                    //now let wordpress do it's stuff with the query router
                    $wp->add_query_var( 'template' );   

                    add_rewrite_endpoint( $endpoint , EP_ROOT );

                    $wp_rewrite->add_rule(
                            sprintf('^/%s/?$', $endpoint), 
                            'index.php?template=' . $endpoint,
                            'bottom' );

                    //and rewrite
                    $wp_rewrite->flush_rules();
                }
            } );
            /* Handle template redirect according the template being queried. */
            add_action( 'template_redirect', function() use( $app ){

                if( $app !== FALSE ){
                    //capture the output to dispatch in the response
                    $endpoint = $app->endPoint( $app->getOption('endpoint', \CodersApp::DEFAULT_EP ) );
                    //check both permalink and page template (validate with locale)
                    if ( \CodersApp::queryRoute( $app->endPoint( $endpoint , TRUE ) )  ) {

                        /* Make sure to set the 404 flag to false, and redirect  to the contact page template. */
                        global $wp_query;
                        //blow up 404 errors here
                        $wp_query->set('is_404', FALSE);
                        //and execute the response
                        $app->response( $app->request( ) );
                        //then terminate app and wordpressresponse
                        exit;
                    }
                }
            } );
        }

        return $this;
    }
    /**
     * @param string $value
     * @return string
     */
    public final function generateId( $value = '' ){
        return md5( uniqid(strval($this) . date('YmdHis') . $value , true) );
    }
    /**
     * Initializer
     */
    //abstract protected function __init();
    /**
     * Defines a hierarchy of end-point translations, customizable from the child application classes
     * 
     * end-point => ( lang_id_1 => ep1 , lang_id_2 => ep2 , lang_id_N => epN )
     * 
     * @return string
     */
    protected function importRoutes( ){
        
        return array( $this->endPointName() => array( ) );
    }
    /**
     * @param string $endpoint (default)
     * @param bool $translate Return the endpoint's  locale translation if defined
     * @return string
     */
    public final function endPoint( $endpoint = 'default' , $translate = FALSE ){
        
        if( $endpoint === 'default' ){
            //override default key to the app-name endpoint
            $endpoint = $this->endPointName();
        }

        if( $translate ){
            
            //choose the selected language
            $lang = get_locale();
            
            //list available endpoints
            $eplist = $this->importRoutes();
            
            if( array_key_exists( $endpoint , $eplist ) ){

                return array_key_exists( $lang, $eplist[ $endpoint ] ) ?
                        $eplist[$endpoint][$lang] :
                        $endpoint;
            }
            else{
                //register error in log
            }
        }
        //return  the requested end-point by default when no translation was defined
        return $endpoint;
    }
    /**
     * @param \CODERS\Framework\Request $R
     * @return \CodersApp
     */
    protected function runServices( \CODERS\Framework\Request $R ){
        
        foreach( $this->_services as $svc ){
            
            $svc->dispatch();
        }
        
        return $this;
    }
    /**
     * @return \CODERS\Framework\Request
     * @throws Exception
     */
    protected function request(){

        if( !class_exists( '\CODERS\Framework\Request' ) ){
            throw new Exception('BAD_REQUEST_RESPONSE');
        }
        /**
         * import request
         */
        return \CODERS\Framework\Request::import( $this );
    }
    /**
     * @return \CODERS\Framework\Response
     */
    protected function response( \CODERS\Framework\Request $R ){
        
        try{
            if( $R->isAdmin() ){

                if(array_key_exists($R->getContext(), $this->_adminOptions)){

                    return $this->_adminOptions[ $R->getContext() ]->__execute($R);
                }
                else{
                    throw new Exception(sprintf('INVALID ADMIN CONTROLLER [%s]',$R->getContext()));
                }
            }
            else{
                return \CODERS\Framework\Response::request( $this, $R );
            }
        }
        catch (Exception $ex) {
            die($ex->getMessage());
        }
    }
    /**
     * @return \CODERS\Framework\DB|boolean
     */
    public function db(){
        
        if(class_exists('\CODERS\Framework\DB')){
            return new \CODERS\Framework\DB( $this );
        }
        
        return FALSE;
    }
    /**
     * @return string
     */
    public final function endPointKey(){ return $this->_EPK; }
    /**
     * @return string
     */
    public final function endPointName(){ return $this->_EPN; }
    /**
     * @return int
     */
    public final function countComponents(){
       
        $count = 0;
        
        foreach( $this->_components as $list ){
            $count += count($list);
        }
        
        foreach( $this->_extensions as $list ){
            $count += count($list);
        }
        
        return $count;
    }
    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    public final function getOption( $option ,  $default = null ){
        
        return get_option(sprintf('%s_%s', $this->_EPK,$option), $default);
    }
    /**
     * @param string $option
     * @param mixed $value
     * @param bool $autoload
     * @return boolean
     */
    protected final function setOption( $option , $value ,$autoload = FALSE ){
        
        return update_option(
                sprintf('%s_%s', $this->_EPK,$option),
                $value,
                $autoload );
    }
    /**
     * Registra un componente del framework
     * @param string $component
     * @param int $type
     * @param boolean $isExtension
     * @return \CodersApp
     */
    protected function register( $component , $type = self::TYPE_MODELS , $isExtension = FALSE ){
        
        if( $type > self::TYPE_CORE ){
            if( $isExtension ){
                if(array_key_exists($type, $this->_extensions)
                    && !in_array( $component ,$this->_extensions[$type]) ){
                    $this->_extensions[ $type ][] = $component;
                }
            }
            elseif(array_key_exists($type, $this->_components)
                    && !in_array( $component ,$this->_components[$type]) ){
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
     * @author Coder01 <coder01@mnkcoder.com>
     * @param string $application
     * @return \CodersApp
     */
    private static final function importInstance( $application ){

        //$path = sprintf('%s/modules/%s/%s.module.php',CODERS_FRAMEWORK_BASE ,$name,$name);
        $path = sprintf('%s/../%s/application.php',__DIR__,$application);
        
        $class = sprintf('%sApp',self::classify($application) );
        
        if(file_exists($path)){
            
            require_once $path;
            
            if(class_exists($class) && is_subclass_of( $class , self::class , TRUE ) ){

                return new $class( );
            }
            else{
                throw new Exception(sprintf('INVALID_APPLICATION [%s]',$class) );
            }
        }
        else{
            throw new Exception(sprintf('INVALID_PATH [%s]',$path) );
            //die(sprintf('INVALID PATH [%s]',$path) );
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
            case self::TYPE_PLUGINS:
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
    private static final function componentPath( $component , $type = self::TYPE_MODELS , $application = '' ){

        $path = strlen($application) ?
                ABSPATH .'/wp-content/plugins/'. $application :
                CODERS_FRAMEWORK_BASE;
        
        switch( $type ){
            case self::TYPE_INTERFACES:
                return sprintf('%s/classes/interfaces/%s.interface.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_CORE:
                return sprintf('%s/classes/%s.class.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_PROVIDERS:
                return sprintf('%s/components/providers/%s.provider.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_SERVICES:
                return sprintf('%s/components/services/%s.interface.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_MODELS:
                return sprintf('%s/components/models/%s.model.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_PLUGINS:
                return sprintf('%s/plugins/%s.plugin.php',
                        $path,
                        self::nominalize($component));
        }
            
        return FALSE;
    }
    /**
     * @return array
     */
    public static final function pluginInfo( ){
        
        return get_plugin_data(__FILE__);
    }
    /**
     * @param string $application
     * @return string
     */
    public static final function repoPath( $application ){
        
        $root = get_option( self::ROOT_PATH , '' );
        
        if(strlen($root)  && strlen($application)){

            return sprintf('%s/%s', $root, $application );
        }
        
        return '';
    }
    /**
     * @return array
     */
    public static final function listInstances(){
        //return array_keys(self::$_instance);
        return self::$_endPoints;
    }
    /**
     * @param string $instance
     * @return boolean
     */
    public static final function isLoaded( $instance ){

        //return array_key_exists($instance, self::$_instance);
        return in_array($instance, self::$_endPoints);
    }
    /**
     * @global type $wp
     * @param string $endpoint
     * @return boolean
     */
    public static final function queryRoute( $endpoint ){

        global $wp;

        $query = $wp->query_vars;

        return array_key_exists($endpoint, $query) ||       //is permalink route
                ( array_key_exists('template', $query)      //is post template
                        && $endpoint === $query['template']);
    }
    /**
     * @return boolean
     */
    public static final function isAdmin(){
        
        return is_admin();
    }
    /**
     * @param string $app
     * @return string
     */
    private static final function createAppKey( $app ){
        
        $key = explode('-', $app);
        
        $output = [];

        switch( count($key)){
            case 0:
                return FALSE;
            case 1:
                return substr($key, 0,4);
            case 2:
                for( $k = 0 ; $k < count( $key ) ; $k++ ){
                    $output[] = strtolower( substr($key[$k], 0, 2) );
                }
                break;
            case 3:
                for( $k = 0 ; $k < count( $key ) ; $k++ ){
                    $output[] = strtolower( substr($key[$k],0,$k > 1 ? 2 : 1 ) );
                }
                break;
            default:
                for( $k = 0 ; $k < count( $key ) && $k < 4 ; $k++ ){
                    $output[] = strtolower( substr($key[$k], 0, 1) );
                }
                break;
        }

        
        return implode('', $output);
    }
    /**
     * Preload installer if required
     * @return boolean
     */
    private static final function preloadInstaller(){
        
        $class = '\CODERS\Framework\Installer';
        
        if( !class_exists($class) ){

            $path = sprintf('%s/classes/core/installer.class.php',CODERS_FRAMEWORK_BASE);

            if(file_exists($path)){

                require_once $path;
            }
        }
        
        return class_exists('\CODERS\Framework\Installer');
    }
    /**
     * Creates a setup tool to activate/deactivate applications
     * @param string $dir
     * @param string $key
     * @return \CODERS\Framework\Installer
     */
    public static final function installer( $dir , $key = '' ){
        
        $node = explode('/', preg_replace('/\\\\/', '/', $dir));
        
        $app = $node[ count( $node ) - 1 ];
        
        if( strlen($key) === 0 ){
            
            $key = self::createAppKey($app);
        }

        if( self::preloadInstaller( ) ){
            
            return \CODERS\Framework\Installer::create($app,$key);
        }
        
        return FALSE;
    }
    /**
     * Inicialización
     * @author Coder01 <coder01@mnkcoder.com>
     * @param string $app
     * @param string $key
     * @return boolean
     */
    public static function init( $app ){
        
        if( strlen($app) && !self::isLoaded($app) ){

            try{
                
                $instance = self::importInstance( $app );
                
                if( !is_null($instance)){

                    //self::$_instance[ $app ] = $instance;
                    self::$_endPoints[ strval($instance) ] = array(
                        'class' => get_class($instance),
                        'end-point' => $instance->endPointName()
                    );
                    
                    return TRUE;
                }
            }
            catch (Exception $ex) {
                printf('<p>%s</p>',$ex->getMessage());
            }
        }
        
        return FALSE;
    }
    /**
     * @return boolean
     */
    public static final function initFramework(){
        
        if( !defined('CODERS_FRAMEWORK_BASE')){

            //first instance to call
            define('CODERS_FRAMEWORK_BASE',__DIR__);
            
            try{
                //register all core components
                self::registerComponents( self::$_framework );
                //register the management options
                self::registerFrameworkMenu();
            }
            catch (Exception $ex) {
                printf('<p>%s</p>',$ex->getMessage());
            }
            
            return  TRUE;
        }
        
        return FALSE;
    }
}

/**
 * Inicializar aplicación
 */
CodersApp::initFramework();




