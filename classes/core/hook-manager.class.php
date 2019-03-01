<?php namespace CODERS\Framework;

defined('ABSPATH') or die;
/**
 * Gestor de soporte de integración del framework con WordPress
 * 
 * - Se describen los hooks de la aplicación general, el cargador de módulos y varias
 * funciones necesarias del core e integración con el CMS.
 * 
 * - No se definen personalizaciones y gestiones de contenido concreto como tipos de post,
 * widgets u otros elementos que puedan significar parte de extensiones, pero sí debería eventualmente
 * admitir sobrecarga para facilitar la personalización de módulos een tiempo de carga del cms.
 * 
 */
final class HookManager{
    
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
  
    private $_app;

    /**
     * 
     */
    public final function __construct( \CodersApp $app ) {
        
        $this->_app = strval($app);
        
        $this->redirectEndPoint()
                ->redirectTemplate()
                ->customHooks();
    }
    /**
     * Redirect Template
     * @return \CODERS\Framework\HookManager
     */
    private final function redirectTemplate(){
        
        $app = $this->_app;
        
        /* Handle template redirect according the template being queried. */
        add_action( self::HOOK_TEMPLATE_REDIRECT, function() use( $app ){

            global $wp;

            $query = $wp->query_vars;

            $instance = \CodersApp::init($app);
            
            if( !is_null($instance)){

                $view = $instance->getOption('template', 'default');

                $endpoint = $instance->endPoint($view);

                if ( array_key_exists('template', $query) && $endpoint == $query['template']) {

                    /* Make sure to set the 404 flag to false, and redirect  to the contact page template. */
                    global $wp_query;

                    $wp_query->set('is_404', false);

                    $instance->redirect_template( $view );

                    ///add ending wordpress hooks?
                    
                    exit;
                }
            }
        } );
        
        return $this;
    }
    /**
     * Redirect End Point
     * @return \CODERS\Framework\HookManager
     */
    private final function redirectEndPoint(){

        $app = $this->_app;
        
        add_action( self::HOOK_INIT, function() use($app){

            global $wp, $wp_rewrite;
            
            $instance = \CodersApp::init($app);
            
            if( !is_null($instance)){
                
                $view = $instance->getOption('template', 'default');

                $endpoint = $instance->redirect_locale($view);

                $wp->add_query_var( 'template' );   

                add_rewrite_endpoint( $endpoint , EP_ROOT );

                $wp_rewrite->add_rule(
                        sprintf('^/%s/?$', $endpoint), 
                        'index.php?template=' . $endpoint,
                        'bottom' );

                $wp_rewrite->flush_rules();
            }
        } );

        return $this;
    }
    /**
     * Cargador de hooks personalizados
     * @return \CODERS\Framework\HookManager
     */
    private final function customHooks(){
        
        $path = sprintf('%s/hooks.php', \CodersApp::applicationPath());
        
        if(file_exists($path)){
            
            require_once $path;
            
        }
        
        return $this;
    }
}

