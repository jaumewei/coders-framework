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
    const EVENT_COMMAND = '_action';
    //tipo de evento de contexto de la aplicación para cargar el módulo apropiado
    const EVENT_CONTEXT = '_context';

    private $_appKey;
    
    private $_get = [];
    private $_post = [];
    private $_action = 'default';
    private $_context = 'main';
    private $_userId = 0;
    //private $_profile;

    /**
     * @param string $appKey
     */
    private function __construct( $appKey ) {
        
        $this->_appKey = $appKey;
        
        //ojo controlar donde se carga el id que puede ser que sea 0 antes de cargar la página
        $this->_userId = get_current_user_id();
        
        
        $get = filter_input_array(INPUT_GET);

        $post = filter_input_array(INPUT_POST);

        if (!is_null($get) && count($get)) {
            $this->_get = self::filterInput($get, $appKey);
        }

        if (!is_null($post) && count($post)) {
            $this->_post = self::filterInput($post, $appKey );
        }
        
        $this->_action = $this->get(self::EVENT_COMMAND,'default');
        $this->_context = $this->get(self::EVENT_CONTEXT,'main');
        $this->remove(self::EVENT_COMMAND);
        $this->remove(self::EVENT_CONTEXT);
    }
    /**
     * @return string
     */
    public function __toString() {
        return strtolower( sprintf('%s.%s',$this->_context,$this->_action) );
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
     * Extraer los valores GET/POST evitando conflictos con otras variables WP
     * @param string $input
     * @return string|null
     */
    private static final function extractPrefix( $input , $prefix ){

        $from = strpos($input, $prefix );
        
        $offset = strlen($prefix);

        if( $from === 0){
            
            return substr($input,
                    $offset ,
                    strlen($input) - $offset);
        }
        
        return '';
    }
    /**
     * 
     * @param array $input
     * @param string $prefix
     * @return array
     */
    private static final function filterInput( array $input , $prefix ){
        $output = [];
        foreach( $input as $key=>$val ){
            $in =  self::extractPrefix($key, $prefix);
            if(strlen($in)){
                $output[ $key ] = is_array($val) ? $val : strip_tags($val);
            }
        }
        return $output;
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixContext(){
        return self::prefixAttach(self::EVENT_CONTEXT);
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixAction(){
        return self::prefixAttach(self::EVENT_COMMAND);
    }
    /**
     * Agrega un valor
     * @param string $property
     * @param string $value
     * @return \CODERS\Framework\Request
     */
    public final function add( $property, $value ){
        if( !isset($this->_get[$property])){
            //fuerza siempre el valor textual
            $this->_get[$property] = strval( $value );
        }
        return $this;
    }
    /**
     * Importa un parámetro del evento
     * @param string $input
     * @param mixed $default
     * @return mixed
     */
    public final function get( $input, $default =null , $from = INPUT_REQUEST ){
        
        switch( $from ){
            case INPUT_POST:
                return array_key_exists($input, $this->_post) ?
                    $this->_post[$input] :
                    $default;
            case INPUT_GET:
                return array_key_exists($input, $this->_get) ?
                $this->_get[ $input ]:
                $default;
            case INPUT_COOKIE:
                $input = filter_input( INPUT_COOKIE, self::prefixAttach($input) );
                return !is_null($input) ? $input : $default;
            case INPUT_REQUEST:
                //POST
                return array_key_exists($input, $this->_post) ? $this->_post[ $input ] :
                //GET
                    array_key_exists($input, $this->_get) ? $this->_get[ $input ]: $default;
        }
    }
    /**
     * @param string $key
     * @return \CODERS\Framework\Request
     */
    public final function remove( $key ){
        if(array_key_exists( $key, $this->_get)){
            unset( $this->_get[$key]);
        }
        if(array_key_exists( $key, $this->_post)){
            unset( $this->_post[$key]);
        }
        return $this;
    }
    /**
     * @return \CODERS\Framework\Request
     */
    public final function reset( $from = INPUT_REQUEST ){
        switch( $from ){
            case INPUT_REQUEST:
                $this->_get = array();
                $this->_post = array();
                break;
            case INPUT_POST:
                $this->_post = array();
                break;
            case INPUT_GET:
                $this->_get = array();
                break;
        }
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
                    self::attachPrefix($cookie,$this->_appKey), $value,
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
    public final function UID(){ return $this->_userId; }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public static final function remoteAddress(){
        
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }
    /**
     * @return array Devuelve todos los datos adjuntos en el evento
     */
    public final function data(){
        return array_merge( $this->_get,$this->_post);
    }
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
    public final function application(){
        return $this->_appKey;
    }
    /**
     * Redirige una request para delegar a un nuevo contexto/acción de controlador
     * @param string $action ACCION o CONTEXTO.ACCION del evento
     * @param array $data
     * @return \CODERS\Framework\Request
     */
    public final function redirect( $action = 'default', array $data = null ){
        
        $composed = explode('.',  strtolower( $action ) );
        
        if( count($composed)  > 1 ){
            $this->_context = $composed[0];
            $this->_action = $composed[1];
        }
        else{
            $this->_action = $composed[0];
        }
        
        if( !is_null($data)){
            $this->reset();
            $this->_get = $data;
        }
        
        return $this;
    }
}