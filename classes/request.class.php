<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Descriptor de eventos del sistema:
 * 
 * - Inputs de usuario: GET y POST desde forms y acciones URL
 * 
 * - Inputs de servicios activos CRON para notificaciones y actualizaciones de estado automáticas del sistema
 * 
 */
class Request{
    //tipo de evento genérico adjuntando datos GET y/o POST
    const ACTION = '_action';
    //tipo de evento de contexto de la aplicación para cargar el módulo apropiado
    const CONTEXT = '_context';

    private $_EP;
    private $_key;
    
    private $_input = [];
    //private $_post = [];
    private $_action = 'default';
    private $_context = 'main';
    private $_ts;
    //private $_userId = 0;
    //private $_profile;

    /**
     * @param \CodersApp $ep
     */
    private function __construct( $endpoint , array $input = array( ) ) {
        
        $this->_EP = $endpoint;
        
        $this->_key = \CodersApp::importKey( $this->_EP );
        
        $this->_ts = time();
        
        foreach( $input as $var => $val ){
            switch( $var ){
                case self::CONTEXT:
                    $this->_context = $val;
                    break;
                case self::ACTION:
                    $this->_action = $val;
                    break;
                default:
                    $this->_input[ $var ] = $val;
                    break;
            }
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        //return strtolower( sprintf('%s.%s.%s.%s',
        return strtolower( sprintf('%s.%s.%s',
                $this->_key,
                //$this->module(),
                $this->_context,
                $this->_action) );
    }
    /**
     * Obtiene un valor de la Request
     * @param string $name
     * @return string
     */
    public final function __get($name) {
        return $this->get($name,'');
    }
    /**
     * @param string $input
     * @param string $prefix
     * @return string
     */
    public static function attachPrefix( $input, $prefix  ){
        return strtolower( sprintf('%s_%s',$prefix,$input) );
    }
    /**
     * @param string $input
     * @return string
     */
    public final function prefix( $input ){
        return strtolower( sprintf('%s.%s',$this->_key, $input) );
    }
    /**
     * 
     * @param array $input
     * @param string $epk
     * @return array
     */
    private static final function filterInput( array $input , $epk = '' ){
        
        $output = [];
        
        //var_dump($input);
        
        foreach( $input as $key => $val ){
            switch( TRUE ){
                case $key === self::ACTION:
                case $key === self::CONTEXT:
                    $output[ $key ] = $val;
                    break;
                case preg_match( sprintf( '/^%s/', $epk . '_' ) , $key):
                    $output[ substr( $key, strlen($epk)+1) ] = strip_tags( $val );
                    break;
            }
            //if( count( $in ) === 2 && $in[0] === $epk ){
            //    $output[ $key[ 1 ] ] = strip_tags( $val );
            //}
        }
        
        return $output;
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixContext(){
        return self::prefixAttach(self::CONTEXT);
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixAction(){
        return self::prefixAttach(self::ACTION);
    }
    /**
     * Agrega un valor
     * @param string $property
     * @param string $value
     * @return \CODERS\Framework\Request
     */
    public final function add( $property, $value ){
        if( !isset($this->_input[$property])){
            //fuerza siempre el valor textual
            $this->_input[$property] = strval( $value );
        }
        return $this;
    }
    /**
     * Importa un parámetro del evento
     * @param string $input
     * @param mixed $default
     * @return mixed
     */
    public final function get( $input, $default = null , $from = INPUT_REQUEST ){
        
        switch( $from ){
            case INPUT_SERVER:
                $input = filter_input(INPUT_SERVER, $input );
                return !is_null($input) ? $input : $default;
            case INPUT_COOKIE:
                $input = filter_input( INPUT_COOKIE, self::prefixAttach($input) );
                return !is_null($input) ? $input : $default;
            case INPUT_REQUEST: default:
                return array_key_exists($input, $this->_input) ? $this->_input[ $input ]: $default;
        }
    }
    /**
     * @param string $key
     * @return \CODERS\Framework\Request
     */
    public final function remove( $key ){
        if(array_key_exists( $key, $this->_input)){
            unset( $this->_input[$key]);
        }
        return $this;
    }
    /**
     * @return \CODERS\Framework\Request
     */
    public final function reset( ){
        
        $this->_input = array();
        
        return $this;
    }

    /**
     * Establece una cookie en WP agregando el prefijo de la aplicación para evitar colisiones
     * 
     * @param string $cookie
     * @param mixed $value
     * @param int $time
     * @return bool
     */
    public final function setCookie( $cookie, $value = null, $time = 10 ){
        
        if(current_filter() === 'wp' ){
            
            $maximum = 10;

            if( $time > $maximum ){
                //máximo a 50 minutos
                $time = $maximum;
            }

            return setcookie(
                    self::attachPrefix($cookie,$this->_EP), $value,
                    time() + ( $time  * 60) );
        }

        return false;
    }
    /**
     * Importa un valor directamente de las variables GET/POST que no será procesada por
     * el prefijo de la aplicación (para capturar variables externas a la aplicación cuando se
     * requiera)
     * @param string $input
     * @param mixed $default
     * @return string
     */
    public static final function input( $input, $default = null ){
        
        $post = filter_input(INPUT_POST, $input);
        
        $get = filter_input(INPUT_GET, $input);
        
        if( !is_null($post) ){
            return $post;
        }
        elseif( !is_null($get)){
            return $get;
        }
        else{
            return $default;
        }
    }
    /**
     * Retorna un valor numérico
     * @param string $property
     * @param int $default
     * @return int
     */
    public final function getInt( $property, $default = 0 ){
        return intval( $this->get($property, $default ) );
    }
    /**
     * Retorna una lista de valores serializados
     * @param string $property Propiedad a extraer
     * @param string $separator Separador de los valores serializados
     * @return array
     */
    public final function getArray( $property, $separator = ',' ){
        return explode($separator, $this->get($property, ''));
    }
    /**
     * @return int WP User ID
     */
    public final function UID(){ return get_current_user_id(); }
    /**
     * @return string
     */
    public final function SID(){ return wp_get_session_token(); }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public static final function remoteAddress(){
        
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }
    /**
     * @return array Devuelve todos los datos adjuntos en el evento
     */
    public final function data(){ return $this->_input; }
    /**
     * @return string Event Type
     */
    public final function action(){ return $this->_action; }
    /**
     * @return string Contexto
     */
    public final function context(){ return $this->_context; }
    /**
     * @return string
     */
    public final function endPoint(){
        return $this->_EP;
    }
    /**
     * @return string
     */
    public final function key(){
        return $this->_key;
    }
    /**
     * @return \CodersApp
     */
    public final function getInstance(){
        return \CodersApp::instance( $this->_EP);
    }
    /**
     * @return boolean
     */
    public final function isAdmin(){ return is_admin(); }
    /**
     * @return string
     */
    public final function module(){ return $this->isAdmin() ? 'admin' : 'public'; }
    /**
     * Redirige una request para delegar a un nuevo contexto/acción de controlador
     * @param string $action ACCION o CONTEXTO.ACCION del evento
     * @param array $data
     * @return \CODERS\Framework\Request
     */
    public final function redirect( $action = 'default', array $data = null ){
        
        $this->reset();
        
        $composed = explode('.',  strtolower( $action ) );
        
        if( count($composed)  > 1 ){
            $this->_context = $composed[0];
            $this->_action = $composed[1];
        }
        else{
            $this->_action = $composed[0];
        }
        
        if( !is_null($data)){
            $this->_input = self::filterInput( $data , $this->_key );
        }
        
        return $this;
    }
    /**
     * @param \CodersApp $app
     * @param array $input
     * @param boolean $filter
     * @return \CODERS\Framework\Request
     */
    public static final function create( $app , array $input = array( ) , $filter = FALSE ){
        
        return new Request( $app , $filter ?
                self::filterInput($input, $app->key()) :
                $input );
    }
    /**
     * @global \WP $wp
     * @return array
     */
    public static final function query_vars(){
        global $wp;

        return $wp->query_vars;
    }
    /**
     * @param int $input
     * @return array
     */
    private static final function __INPUT( $input = INPUT_GET ){
        $vars = filter_input_array($input);
        return !is_null($vars) ? $vars : array();
    }
    /**
     * @param \CodersApp $endpoint
     * @return \CODERS\Framework\Request
     */
    /*public static final function import( $endpoint ){
        
        $epk = \CodersApp::importKey( $endpoint );
        
        return new Request( $endpoint , self::filterInput(
                array_merge( self::__INPUT(INPUT_GET) , self::__INPUT(INPUT_POST) ),
                $epk ) );
    }*/
    /**
     * @global \WP $wp
     * @param \CodersApp $endpoint
     * @return \CODERS\Framework\Request
     */
    public static final function import( $endpoint ){

        global $wp;

        $query = $wp->query_vars;

        //is permalink route
        if ( array_key_exists($endpoint, $query) || 
                ( array_key_exists(\CodersApp::APPQUERY, $query)
                && $endpoint === $query[\CodersApp::APPQUERY] ) ){
         
            $epk = \CodersApp::importKey( $endpoint );
            
            return new Request( $endpoint , self::filterInput(
                    array_merge(self::__INPUT(INPUT_GET),self::__INPUT(INPUT_POST)),
                    $epk) );
        }
        
        return FALSE;
    }
}


