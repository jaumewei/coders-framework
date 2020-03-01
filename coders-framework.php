<?php defined('ABSPATH') or die;
/*******************************************************************************
 * Plugin Name: Coders Framework
 * Plugin URI: https://coderstheme.org
 * Description: Framework Prototype
 * Version: 0.1.2
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
     * Instances must bestored within an array, they're up to be used both in
     * frontend context or in admin/backend- which requires always some preload
     * methods to know all available installed applications.
     * 
     * @var \CodersApp[]|callable List of registered instances
     */
    private static $_endpoints = [
        //list here all endpoints by App
    ];
    /**
     * @var array
     */
    private static $_routes = [
        //ilst here all endpoint translations
    ];
    /**
     * @var \CODERS\Framework\Response[]
     */
    private $_menu = array(
        //
    );
    /**
     * @var array
     */
    private $_pluginData = array();
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
        $this->_EPN = $this->generaeEndPoint();
        //end point key
        $this->_EPK = strlen($key) > 3 ? $key : $this->generateAppKey($this->_EPN);
        
        self::initComponents($this->__components());

        $this->registerMenu();
        
        //self::registerEndPoint($this->_EPN, $this)
    }
    /**
     * CORE COMPONENT DEFINITION
     * @var array
     */
    private static final function __framework(){
        return array(
            self::TYPE_CORE => array(
                'request'
            )
        );
    }
    /**
     * CORE COMPONENT DEFINITION
     * @var array
     */
    private static final function __instance(){
        return array(
            self::TYPE_INTERFACES => array(
                'service',
                'plugin',
                'model',
                'template',
                'widget'),
            self::TYPE_CORE => array(
                'component',
                'db',           //wpdb helper
                'dictionary',
                'request',      //inputs
                'response',
                //'html',
                'renderer',
                'service',
            ),
        );
    }
    /**
     * @return array
     */
    private final function __components(){
        return $this->_components;
    }
    /**
     * @return string
     */
    public final function __toString() {
        
        return $this->endPoint();
    }
    /**
     * @return string
     */
    public static final function appRoot( $endpoint ){
       
        return (array_key_exists($endpoint, self::$_endpoints)) ?
                sprintf('%s/%s/', preg_replace('/\\\\/', '/', dirname(__DIR__) ),$endpoint) :
                '' ;
    }
    /**
     * Ruta local de contenido de la aplicación
     * @return string
     */
    public final function appPath(){
        
        // within either sub or parent class in a static method
        $ref = new ReflectionClass(get_called_class());

        return dirname( $ref->getFileName() );
    }
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public final function appURL( ){
        
        return preg_replace( '/coders-framework/',
                $this->endPoint(),
                plugin_dir_url(__FILE__) );
    }
    /**
     * List all available langguates in the CMS
     * @return array
     */
    private static final function importLocale(){
        $locale = wp_get_installed_translations('core');
        $output = array();
        foreach (array_keys($locale['default']) as $lang) {
            $output[] = $lang;
        }
        return $output;
    }
    /**
     * Preload all core and instance components
     * @param array $components
     * @param string $app
     */
    private static final function initComponents( array $components ){
        foreach( $components as $type => $list ){
            foreach( $list as $member ){
                $path = self::componentPath($member, $type );
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
     * @param string $option
     * @return \CodersApp
     */
    protected function registerAdminOption( $option ){
        if( is_admin( ) ){
            if( !array_key_exists( $option , $this->_menu ) ){
                $response = \CODERS\Framework\Response::create(
                        $this,
                        $option,
                        TRUE );
                if( $response !== FALSE ){
                    $this->_menu[ $response->getName( ) ] = $response;
                }
            }
        }
        return $this;
    }
    /**
     * Redirect End Point URL
     * @return \CodersApp
     */
    private final function registerMenu(){
        if( is_admin( ) ){
            $adminMenu = &$this->_menu;
            add_action( 'admin_menu', function( ) use( $adminMenu ){
                foreach ( $adminMenu as $option => $controller) {

                    if (strlen($controller->getParent()) ) {
                        add_submenu_page(
                                $controller->getParent(),
                                $controller->getPageTitle(),
                                $controller->getOptionTitle(),
                                $controller->getCapabilities(),
                                $option,
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
                                array($controller, '__execute'),
                                $controller->getIcon(),
                                $controller->getPosition());
                    }
                }
            }, 10000 );
        }

        return $this;
    }
    /**
     * @param string $endpoint
     * @param string $alias
     * @param string $route
     * @param string $locale
     */
    public static final function registerRoute( $endpoint , $alias , $route , $locale = '' ){
        if( !array_key_exists($endpoint, self::$_routes) ){
            self::$_routes[ $endpoint ] = array();
        }
        if( !array_key_exists($alias, self::$_routes[$endpoint])){
            self::$_routes[ $endpoint ][ $alias ] = array();
            foreach( self::importLocale() as $lang ){
                self::$_routes[ $endpoint ][ $alias ][ $lang ] = $endpoint;
            }
        }
        if(strlen($locale) < 1 ){
            $locale = get_locale();
        }
        self::$_routes[ $endpoint ][ $alias ][ $locale ] = $route;
    }
    /**
     * @param string $endpoint
     * @param function|object $runable
     * @return boolean
     */
    public static final function registerEndPoint( $endpoint , $runable ){
        if( !array_key_exists($endpoint, self::$_endpoints) ){
            if(is_callable($runable) || is_subclass_of($runable, self::class)){
                //register in app chain
                self::$_endpoints[ $endpoint ] = $runable;
                /* SETUP ROUTE | URL */
                add_action( 'init' , function() use( $endpoint ){
                    $alias = \CodersApp::importRoute($endpoint);
                    global $wp, $wp_rewrite;
                    $wp->add_query_var('template');
                    add_rewrite_endpoint($alias, EP_ROOT);
                    $wp_rewrite->add_rule(
                            //friendly URL setup
                            sprintf('^/%s/?$', $alias),
                            //GET url setup
                            sprintf('index.php?template=', $alias),
                            //priority
                            'bottom');
                    //and rewrite
                    $wp_rewrite->flush_rules();
                } );
                /*SETUP RESPONSE*/
                add_action( 'template_redirect', function() use( $endpoint ){
                    $alias = \CodersApp::importRoute($endpoint);
                    $request = \CodersApp::request($alias);
                    if ( $request !== FALSE ) {
                        /* Make sure to set the 404 flag to false, and redirect  to the contact page template. */
                        global $wp_query;
                        //blow up 404 errors here
                        $wp_query->set('is_404', FALSE);
                        //and execute the response
                        \CodersApp::run( $request );
                        //then terminate app and wordpressresponse
                        //do_action('finalize');
                        exit;
                    }
                } );
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * @param string $endpoint
     * @param string $default
     * @return string
     */
    public static final function importKey( $endpoint , $default = '_' ){
        if( array_key_exists($endpoint, self::$_endpoints) &&
            is_subclass_of( self::$_endpoints[ $endpoint ] , self::class ) ){
            
            return self::$_endpoints[  $endpoint ]->epk();
        } 
        
        return $default;
    }
    /**
     * @param String $route
     * @param String $alias
     * @param String $locale
     * @return String|URL
     */
    public static final function importRoute( $route , $alias = '' , $locale = '' ){
        
        if(strlen($alias) < 1 ){
            //root by default
            $alias = $route;
        }
        
        if(strlen($locale) < 1 ){
            //root by default
            $locale = get_locale();
        }
        
        if(array_key_exists($route, self::$_routes)){
            if(array_key_exists($alias, self::$_routes[$route])){
                if(array_key_exists($locale, self::$_routes[$route][$alias])){
                    return self::$_routes[ $route ][ $alias ][ $locale ];
                }
            }
        }
        
        return $route;
    }
    /**
     * @param \CODERS\Framework\Request
     * @return \CodersApp | function | boolean
     */
    public static final function run( \CODERS\Framework\Request $request ){
        if(array_key_exists($request->endPoint(), self::$_endpoints)){
            $app = self::$_endpoints[ $request->endPoint() ];
            switch( TRUE ){
                case is_object($app) && is_subclass_of($app, self::class):
                    //mvc setup
                    return $app->response( $request );
                case is_callable($app):
                    //custom function setup
                    return $app( $request );
                default:
                    //invalid endpoint application!!
                    break;
            }
        }
        return FALSE;
    }
    /**
     * @return string
     */
    public final function epk(){ return $this->_EPK; }
    /**
     * 
     * @return string
     */
    public final function endPoint(){ return $this->_EPN; }
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
     * @param string $endpoint
     * @return \CODERS\Framework\Request
     * @throws Exception
     */
    public static final function request( $endpoint ){

        global $wp;

        $query = $wp->query_vars;

        //is permalink route
        return ( array_key_exists($endpoint, $query) ) ||
                ( array_key_exists('template', $query)
                && $endpoint === $query['template']) ?
                    \CODERS\Framework\Request::import( $endpoint ) :
                    FALSE;
    }
    /**
     * @return \CODERS\Framework\Response
     */
    protected function response( \CODERS\Framework\Request $request ){
                
        try{
            if( is_admin() ){
                if(array_key_exists( $request->getContext( ) , $this->_menu )){
                    return $this->_menu[ $request->getContext() ]->__execute( $request );
                }
                else{
                    throw new Exception(sprintf('INVALID ADMIN CONTROLLER [%s]',$request->getContext()));
                }
            }
            else{
                return \CODERS\Framework\Response::request($this, $request );
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
     * @return int
     */
    public final function countComponents(){
       
        $count = 0;
        
        foreach( $this->_components as $list ){
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
    protected function register( $component , $type = self::TYPE_MODELS ){
        
        if( $type > self::TYPE_CORE && !array_key_exists($type, $this->_components)
                    && !in_array( $component ,$this->_components[$type]) ){
                $this->_components[ $type ][] = $component;
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
     * @param string $path
     * @return \CodersApp
     */
    public static final function create( $path ){

        $dir = explode('/', preg_replace('/\\\\/', '/', $path));
        
        $endpoint = $dir[ count( $dir ) - 1 ];
        
        if ( strlen($endpoint) && !self::loaded($endpoint) ){
            
            //$appPath = sprintf('%s/../%s/application.php',__DIR__,$endpoint);
            $appPath = sprintf('%s/application.php',$path);

            $class = self::classify($endpoint);

            try{
                if(file_exists($appPath)){

                    require_once $appPath;

                    if(class_exists($class) && is_subclass_of( $class , self::class , TRUE ) ){
                        //init required componentes before instance
                        self::initComponents(self::__instance());
                        //instance app
                        $app = new $class( );
                        //register and 
                        return self::registerEndPoint(
                                $app->endPoint(),
                                $app);
                    }
                    else{
                        throw new Exception(sprintf('INVALID_APPLICATION [%s]',$class) );
                    }
                }
                else{
                    throw new Exception(sprintf('INVALID_PATH [%s]',$appPath) );
                }
            }
            catch (Exception $ex) {
                die($ex->getMessage());
            }
        }
        return FALSE;
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
                return sprintf('%s/classes/providers/%s.provider.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_SERVICES:
                return sprintf('%s/classes/services/%s.interface.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_MODELS:
                return sprintf('%s/classes/models/%s.model.php',
                        $path,
                        self::nominalize($component));
            case self::TYPE_PLUGINS:
                return sprintf('%s/plugins/%s/plugin.php',
                        $path,
                        self::nominalize($component));
        }
            
        return FALSE;
    }
    /**
     * 
     * @return Array
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
     * @param string $endpoint
     * @return boolean
     */
    public static final function loaded( $endpoint ){
        //return array_key_exists($instance, self::$_instance);
        return array_key_exists($endpoint, self::$_endpoints);
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
     * @return array
     */
    public static final function list(){
        $list = array();
        foreach( self::$_endpoints as $endpoint => $app ){
        
            $ismvc = is_object($app) && is_subclass_of($app, self::class);
            
            $list[ $endpoint ] = array(
                'name' => self::classify($endpoint),
                'type' => $ismvc ? 'mvc-app' : 'runnable',
                'key' => $ismvc ? $app->epk() : '',
                'url' => sprintf('%s/%s', get_site_url(),$endpoint),
            );
        }
        return $list;
    }
    /**
     * @return boolean
     */
    public static final function isAdmin(){
        
        return is_admin();
    }
    /**
     * @param string $value
     * @return string
     */
    public static final function generateId( $value = '' ){
        return md5( uniqid(strval($this) . date('YmdHis') . $value , true) );
    }
    /**
     * @return string
     */
    private final function generaeEndPoint( ){
        
        $class = explode('\\', get_class($this));
        
        return self::nominalize($class[ count($class) - 1 ]);
    }
    /**
     * @param string $endpoint
     * @return string
     */
    private static final function generateAppKey( $endpoint ){
        $key = explode('-', $endpoint);
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
     * @param string $path
     * @param callable $callback
     * @param string $key 
     */
    public static final function __install( $path , $callback , $key = '' ){
        
        if(!is_admin()){
            return;
        }
        
        $dir = explode('/', preg_replace('/\\\\/', '/', $path));
        
        $endpoint = $dir[ count( $dir ) - 1 ];
        
        if( strlen($key) === 0 ){
            $key = self::generateAppKey($endpoint);
        }
        
        if(is_callable($callback)){
            
            if( $callback( $endpoint , $key ) ){
            }
            else{
            }
        }
    }
    /**
     * @param string $path
     * @param callable $callback
     * @param string $key
     */
    public static final function __uninstall( $path , $callback , $key = '' ){
        
        if(!is_admin()){
            return;
        }
        
        $dir = explode('/', preg_replace('/\\\\/', '/', $path));
        
        $endpoint = $dir[ count( $dir ) - 1 ];
        
        if( strlen($key) === 0 ){
            $key = self::generateAppKey($endpoint);
        }
        if(is_callable($callback)){
            if( $callback( $endpoint , $key ) ){
            }
            else{

            }
        }
    }
    /**
     * @return boolean
     */
    public static final function init(){
        
        if( !defined('CODERS_FRAMEWORK_BASE')){
            //first instance to call
            define('CODERS_FRAMEWORK_BASE',__DIR__);
            try{
                //register all core components
                self::initComponents( self::__framework() );
                //register the management options
                if( is_admin( ) ){
                    add_action( 'init' , function(){
                        add_action( 'admin_menu', function(){
                            //add_menu_page(
                            add_submenu_page(
                                'options-general.php',
                                __('Coders Framework','coders_framework'),
                                __('Coders Framework','coders_framework'),
                                'administrator','coders-framework-manager', function(){
                                    $path = sprintf('%s/framework/view.php',CODERS_FRAMEWORK_BASE);
                                    if( file_exists( $path ) ){
                                        require_once $path;
                                    }
                                }, 51 );
                        },100000);
                    });
                }
                return  TRUE;
            }
            catch (Exception $ex) {
                printf('<p>%s</p>',$ex->getMessage());
            }
        }
        return FALSE;
    }
}
/**
 * Inicializar aplicación
 */
CodersApp::init();




