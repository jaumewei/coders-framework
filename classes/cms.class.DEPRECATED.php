<?php namespace CODERS\Framework;

defined('ABSPATH') or die;
/**
 * WordPress CMS System Adapter * 
 * @deprecated since version 20190915
 */
final class Cms{
    
    //inicializar aplicación e instancia, widgets y plugins cargados
    const HOOK_INIT = 'init';
    const HOOK_WIDGETS_INIT = 'widgets_init';
    const HOOK_PLUGINS_LOADED = 'plugins_loaded';
    const HOOK_SHUTDOWN = 'shutdown';

    //elementos comunes
    const HOOK_ADMIN_BAR = 'admin_bar_menu';
    
    //administración
    const HOOK_ADMIN_SCRIPTS = 'admin_enqueue_scripts';
    const HOOK_ADMIN_DASHBOARD = 'wp_dashboard_setup';
    
    //wordpress publico
    const HOOK_WP = 'wp';
    const HOOK_SCRIPTS = 'wp_enqueue_scripts';
    
    //administración contenido
    const HOOK_METABOXES = 'add_meta_boxes';
    const HOOK_SAVE_POST = 'save_post';
    
    
    const HOOK_TEMPLATE_REDIRECT = 'template_redirect';
    /**
     * app Endpoint
     * @var string
     */
    private $_EP;
    /**
     * App endpoint key
     * @var string
     */
    private $_EPK;
    /**
     * @var array
     */
    private $_adminOptions = array(
        //register here admin setup
    );
    /**
     *
     * @var \CODERS\Framework\Models\PostModel[] 
     */
    private $_postTypes = array(
        
    );
    /**
     * 
     */
    public final function __construct( \CodersApp $app ) {
        
        $this->_EP = strval($app);
        
        $this->_EPK = $app->endPointKey();
        
        //administración
        $this->hookAdminMenu()
                //ruta publica permalink/GET
                ->hookEndPoint()
                //redirección publica
                ->hookResponse()
                //register post types
                ->hookPostTypes()
                //personalizaciones
                ->hookCustom();
    }
    /**
     * Hooks the application endpoint response, bypassing the requested route through
     * the framework control
     * @return \CODERS\Framework\Cms
     */
    private final function hookResponse(){
        
        $app = $this->_EP;
            
        /* Handle template redirect according the template being queried. */
        add_action( self::HOOK_TEMPLATE_REDIRECT, function() use( $app ){

            $instance = \CodersApp::instance( $app );

            if( $instance !== FALSE ){
                //capture the output to dispatch in the response
                $endpoint = $instance->endPoint( $instance->getOption('endpoint', \CodersApp::DEFAULT_EP ) );
                //use this to validate the current locale endpoint translation
                $endpointLocale = $instance->endPoint( $endpoint , TRUE );
                //check both permalink and page template
                if ( \CODERS\Framework\Cms::queryRoute( $endpointLocale )  ) {

                    /* Make sure to set the 404 flag to false, and redirect  to the contact page template. */
                    global $wp_query;
                    //blow up 404 errors here
                    $wp_query->set('is_404', FALSE);
                    //and execute the response
                    $instance->response( $endpoint );
                    //then go
                    exit;
                }
            }
        } );
        
        return $this;
    }
    /**
     * Redirect End Point URL
     * @return \CODERS\Framework\Cms
     */
    private final function hookEndPoint(){

        $app = $this->_EP;
        
        add_action( self::HOOK_INIT, function() use($app){

            global $wp, $wp_rewrite;
            
            $instance = \CodersApp::instance( $app );

            if( $instance !== FALSE ){
                //import the regiestered locale's endpoint from the settinsg
                $endpoint = $instance->endPoint($instance->getOption(
                        'endpoint' ,
                        \CodersApp::DEFAULT_EP ) , TRUE );
                
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

        return $this;
    }
    /**
     * Hook para la página de administración del plugin
     * @return \CODERS\Framework\Cms
     */
    private function hookAdminMenu(){

        $app = $this->_EP;
        
        $options = $this->_adminOptions;
        
        add_action( 'admin_menu', function() use( $app , $options){
            
            foreach( $options as $item => $content ){
                //each item is a Page setup class
                add_menu_page(
                    $content['page'],
                    $content['menu'],
                    $content['capabilities'],
                    $item,
                    function() use( $app ){
                        //capture and initialize each instance by it's name
                        $instance = \CodersApp::instance($app);
                        
                        if( FALSE !== $instance ){

                            $instance->response();
                        }
                    },
                    $content['icon'],
                    $content['position'] );
            }
            
        }, 10000 );
        
        return $this;
    }
    /**
     * Hook para la página de administración del plugin
     * @return \CODERS\Framework\Cms
     */
    private function hookPostTypes(){
        foreach( $this->_postTypes as $post ){
            register_post_type( $post->type(), $post->definition());
        }
        return $this;
    }
    /**
     * Cargador de hooks personalizados
     * @return \CODERS\Framework\Cms
     */
    private final function hookCustom(){
        
        $app = \CodersApp::instance($this->_EP);

        if( $app !== FALSE ){

            $path = sprintf('%s/hooks.php', $app->appPath());

            if(file_exists($path)){

                require_once $path;

            }
        }
        
        return $this;
    }
    /**
     * @param string $option
     * @param string $menu
     * @param string $title
     * @param string $icon
     * @return \CODERS\Framework\Cms
     */
    public final function addAdminPage( $option , $menu , $title , $icon , $capabilities = 'administrator' , $position = 50 ){
        
        if( !isset( $this->_adminOptions[$option])){
            $this->_adminOptions[$option] = array(
                'menu' => $menu,
                'page' => $title,
                'icon' => $icon,
                'capabilities' => $capabilities,
                'position' => $position,
            );
        }
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Cms
     */
    public final function addPostType(  ){
        
        return $this;
    }

    /**
     * List all available langguates in the CMS
     * @return array
     */
    public final function listLanguages(){
        
        $translations = array( );
        
        $locale = wp_get_installed_translations('core');
        
        foreach (array_keys($locale['default']) as $lang) {
            $translations[] = $lang;
        }

        return $translations;
    }
    /**
     * @return array
     */
    public final function listAdminOptions(){
        return $this->_adminOptions;
    }
    /**
     * 
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
}

