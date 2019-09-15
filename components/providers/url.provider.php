<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor y generador de querys GET y urls
 * En desarrollo para extraer los métodos de enrutado desde la clase Request
 */
final class Url{
    /**
     * @var string
     */
    private $_baseUrl;
    /**
     * @var array
     */
    private $_query = array();
    /**
     * @param string $module
     */
    private final function __construct( $module = null ) {
        
        if( !is_null($module) ){
            switch( $module ){
                case TripManager::PROFILE_ADMIN:
                    //generar link de administración
                    $this->_baseUrl = admin_url( 'admin.php' );
                    $this->add('page',TripManager::PLUGIN_NAME);
                default:
                    //construir la url del post/pagina según el modulo
                    $post_id = $this->getPostID( $module );
                    $this->_baseUrl = $this->buildPermalink( $post_id );
                    $this->add('page_id', $post_id );
            }
        }
        else{
            //construir la url del post/pagina actual
            $this->buildPermalink( $this->getPostID( ) );
        }
    }
    /**
     * @return String
     */
    public final function __toString() { return $this->get(); }
    /**
     * @return URL
     */
    public final function get(){
        
        $separator = strrpos($this->_baseUrl, '?') === FALSE ? '?' : '&';
        
        return $this->_baseUrl . $separator . implode('&', $this->_query);
    }
    /**
     * Retorna el id de post en función del módulo
     * @param string $module
     * @return int
     */
    private final function getPostID( $module = null ){
        
        $default_id = TripManager::loaded() ? TripManager::instance()->getPostID() : 0;
        
        $post_id = !is_null($module) ?
                //ID del post por módulo
                TripManager::getProfilePage($module) :
                //ID del post actual traducido al original
                $default_id;
        //si es preciso, busca el post correspondiente a la traducción del idioma actual
        return TripManStringProvider::parseTranslationId( $post_id );
    }
    /**
     * Genera la URL
     * @global WP_Rewrite $wp_rewrite
     * @param string $post_id
     * @return \TripManUrlProvider
     */
    private final function buildPermalink( $post_id ){
        
        global $wp_rewrite;
        
        if( $wp_rewrite->get_page_permastruct() === '%pagename%' ){
            
            $post = get_post( $post_id );
            
            return get_page_link( $post );
        }
         
        return get_site_url();
    }
    /**
     * Agrega un valor a la url
     * @param string $parameter
     * @param string $value
     * @param boolean $prefix Adjunta el prefijo del parámetro GET para tratarlo exclusivamente en Trip Manager y evitar conflictos
     * @return \RouteProvider
     */
    public final function add( $parameter , $value , $prefix = false ){
        
        $var = $prefix ?
                TripManRequestProvider::prefixAttach($parameter) :
                $parameter;
        
        $this->_query[ $var ] = $value;

        return $this;
    }
    
    /**
     * Genera una URL de la aplicación en la administración
     * @param string $context Contexto simple [context] o compuesto [context.action]
     * @param array $args Parámetros y valores importados por la aplicación
     * @return \TripManUrlProvider
     */
    public static final function requestAdminRoute( $context = null, array $args = null ){
        //url de administración airbox por defecto
        $url = new RouteProvider( TripManager::PROFILE_ADMIN );

        if( !is_null($context) ){
            
            $composed = explode('.',$context);
            
            $url->add(TripManRequestProvider::EVENT_DATA_CONTEXT, $composed[0]);
            
            if( count($composed) > 1 ){

                $url->add( TripManRequestProvider::EVENT_DATA_COMMAND , $composed[ 1 ] );
            }
        }

        if( !is_null( $args ) ){

            foreach( $args as $var => $val ){
                //adjuntar los parámetros con prefijo tirpman_
                $url->add( $var , $val, TRUE );
            }
        }
            
        return $url;
    }
    /**
     * Genera una URL de la aplicación en el acceso público
     * @version 1.1.07e - 2017-06-14
     * ->Corregido error de conversión de url a diferentes idiomas
     * @param string $context Contexto simple [context] o compuesto [context.action]
     * @param array $args Parámetros y valores importados por la aplicación
     * @param string $module Definir otro módulo de destino al actual
     * @return URL
     * @global WP_Rewrite $wp_rewrite
     */
    public static final function requestPublicRoute( $context = null , array $args = null, $module = NULL ){

        $url = new TripManUrlProvider( $module );
        
        if( !is_null($context)){
            
            $composed = explode('.',$context);

            $url->add( TripManRequestProvider::EVENT_DATA_CONTEXT, $composed[ 0 ] , TRUE );
            
            if( count($composed) > 1 ){

                $url->add(TripManRequestProvider::EVENT_DATA_COMMAND, $composed[ 1 ] , TRUE );
            }
        }
        
        if( !is_null( $args ) ){

            foreach( $args as $var => $val ){
                //adjuntar los parámetros con prefijo tirpman_
                $url->add( $var , $val, TRUE );
            }
        }
            
        return $url;
    }
}