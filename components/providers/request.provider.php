<?php namespace CODERS\Framework\Providers;

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
    const EVENT_DATA_COMMAND = '_action';
    //tipo de evento de contexto de la aplicación para cargar el módulo apropiado
    const EVENT_DATA_CONTEXT = '_context';
    //para algunos contextos es necesario devolver una acción de retorno (ajax, extensiones, etc)
    const EVENT_DATA_CALLBACK = '_callback';
    //vista seleccionada para mostrar
    const EVENT_SELECTED_VIEW = '_view';
    //prefijo para anexar a TODOS los valores de la aplicación que entren por POST/GET para evitar colisiones con wp
    const EVENT_DATA_PREFIX = 'tripman_';
    /**
     * @var TripManRequestProvider Evento Importado
     */
    //private static $_event = null;
    /**
     * @var string Token
     */
    //private static $_token = null;
    
    private $_module;
    
    private $_data = array();
    private $_action = 'default';
    private $_context = 'main';
    private $_userId = 0;
    //private $_profile;

    /**
     * @param array $eventData
     */
    private function __construct( array $eventData = null ) {
        
        //ojo controlar donde se carga el id que puede ser que sea 0 antes de cargar la página
        $this->_userId = get_current_user_id();
        
        $this->_module = TripManager::instance()->getProfile();

        if( !is_null($eventData) ){
            foreach( $eventData as $var => $val){
                switch( $var ){
                    case self::EVENT_DATA_COMMAND:
                        $this->_action = strtolower($val);
                        break;
                    case self::EVENT_DATA_CONTEXT:
                        if( !isset($eventData[self::EVENT_DATA_COMMAND])){
                            //extraer acción del evento si viene compuesta
                            $composed = explode('.',  strtolower( $val ) );
                            $this->_context = $composed[0];
                            if( count($composed) > 1 ){
                                $this->_action = $composed[1];
                            }
                        }
                        else{
                            $this->_context = strtolower($val);
                        }
                        break;
                    default:
                        $this->_data[$var] = $val;
                        break;
                }
            }
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return sprintf('%s.%s',$this->_context,$this->_action);
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
     * @return \CODERS\Framework\Providers\Request
     */
    public static final function import(){
        
        if( is_null( self::$_event ) ){

            $input = array();

            $get = filter_input_array(INPUT_GET);
            
            $post = filter_input_array(INPUT_POST);

            if (!is_null($get) && count($get)) {
                foreach( $get as $var => $val ){
                    //parcheando wordpresss
                    $import = self::prefixExtract($var);
                    if( !is_null($import)){
                        //$input[ $import ] = sanitize_text_field( $val );
                        $input[ $import ] = strip_tags( $val );
                    }
                }
            }

            if (!is_null($post) && count($post)) {
                foreach( $post as $var => $val ){
                    //parcheando wordpresss
                    $import = self::prefixExtract($var);
                    if( !is_null($import)){
                        //$input[ $import ] = sanitize_text_field( $val );
                        if(is_array($val)){
                            $input[ $import ] = $val;
                        }
                        else{
                            $input[ $import ] = strip_tags( $val );
                        }
                    }
                }
            }
            
            self::$_event = new TripManRequestProvider(count($input) ? $input : null );
        }
        
        return self::$_event;
    }
    /**
     * @param URL $resource URL del recurso solicitado
     */
    public static final function requestAsset( $resource, $profile = null ){
        
        if( !is_null( $profile ) ){
            
            $module_uri = '%smodules/%s/views/assets/%s';
            
            $module_path = sprintf($module_uri, MNK__TRIPMAN__DIR,
                    $profile , $resource);
            
            if(file_exists($module_path)){

                return sprintf($module_uri,MNK__TRIPMAN__URL,$profile,$resource);
            }
        }
        
        $base_path = sprintf('%sassets/%s',MNK__TRIPMAN__DIR,$resource);
        
        if(file_exists($base_path)){
            return sprintf('%sassets/%s',MNK__TRIPMAN__URL,$resource);
        }
        
        return false;
    }
    /**
     * Genera una URL de la aplicación en la administración
     * @param string $context Contexto simple [context] o compuesto [context.action]
     * @param array $args Parámetros y valores importados por la aplicación
     * @return URL
     */
    public static final function requestAdminRoute( $context = null, array $args = null ){
        //url de administración airbox por defecto
        $url = admin_url( 'admin.php?page='. TripManager::PLUGIN_NAME );

        if( !is_null($context) ){
            
            $composed = explode('.',$context);
            
            $url .= count($composed) > 1 ?
                    sprintf('&%s=%s&%s=%s',
                            self::prefixContext(),$composed[0],
                            self::prefixAction(),$composed[1]) :
                    sprintf('&%s=%s',self::prefixContext(), $composed[0]) ;
        }

        if( !is_null( $args ) ){
            foreach( $args as $var=>$val ){
                $url .= sprintf('&%s=%s',self::prefixAttach( $var ),$val);
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

        $default_id = TripManager::loaded() ? TripManager::instance()->getPostID() : 0;
        
        $post_id = !is_null($module) ?
                //ID del post por módulo
                TripManager::getProfilePage($module) :
                //ID del post actual traducido al original
                $default_id;

        if( $post_id  > 0 ){
            //traducir el post
            $translated_id = TripManStringProvider::parseTranslationId( $post_id );
            
            $url = site_url( '?page_id=' . $translated_id );

            $varkey = '&';

            //guarreando con los permalinks
            global $wp_rewrite;
            /**
             * @todo Por resolver este apartado
             * La puta mierda de worpress complica las cosas aqui.
             * Problemna con wordpres  o wpml
             * Es necesario generar el link del post traducido, pero este no
             * incluye de ninguna manera el slug (post_name), ninguna de las funciones
             * disponibles facilitan este valor, dejando la url apuntando a la página
             * inicial por defecto
             */
            if( $wp_rewrite->get_page_permastruct() === '%pagename%' ){

                $post = get_post( $translated_id );
                //$url = site_url( $post->post_name );
                $url = get_page_link( $post );

            }
            //definir  el inicio de la query GET si no se ha establecido todavía
            if( strrpos( $url, '?' ) === false ){ $varkey = '?'; }

            if( TripManStringProvider::isTranslationActive() && TripManStringProvider::getLocale() !== TripManStringProvider::LANGUAGE_DEFAULT ){
                
                if( strrpos('lang=', $url) === false ){
                    //agrega este parche para las traducciones..... mucho ojo con esto
                    $url .= sprintf('%slang=%s',
                            $varkey,
                            TripManStringProvider::getLocale(true));
                    //mucho mas ojo aun!!!!!
                    $varkey = '&';
                }
            }

            if( !is_null($context) ){

                $composed = explode('.',$context);

                $url .= count($composed) > 1 ?
                        sprintf('%s%s=%s&%s=%s',
                                $varkey,self::prefixContext(),$composed[0],
                                self::prefixAction(),$composed[1]) :
                        sprintf('%s%s=%s',$varkey,self::prefixContext(), $composed[0]) ;
            }

            if( !is_null( $args ) ){
                foreach( $args as $var=>$val ){
                    $url .= sprintf('&%s=%s',self::prefixAttach( $var ),$val);
                }
            }

            return $url;
        }

        return get_site_url();

    }
    /**
     * Define el prefijo para no petar las querys con otras variables de wordpress
     * WORDPRESS ES UNA PUTA MIERDA!!
     * @param string $data Cadena o valor a procesar con un prefijo
     * @return string Valor prefijado
     */
    public static final function prefixAttach( $data ){
        return self::EVENT_DATA_PREFIX . $data;
    }
    /**
     * Extraer los valores GET/POST evitando conflictos con otras variables WP
     * @param string $input
     * @return string|null
     */
    private static final function prefixExtract( $input ){

        $prefix = strpos($input, self::EVENT_DATA_PREFIX);
        
        $offset = strlen(self::EVENT_DATA_PREFIX);

        if( $prefix === 0){
            
            return substr($input,
                    $offset ,
                    strlen($input) - $offset);
        }
        
        return null;
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixContext(){
        return self::prefixAttach(self::EVENT_DATA_CONTEXT);
    }
    /**
     * Retorna la variable de contexto con el prefijo requerido
     * @return string
     */
    public static final function prefixAction(){
        return self::prefixAttach(self::EVENT_DATA_COMMAND);
    }
    /**
     * Agrega un valor
     * @param string $property
     * @param string $value
     * @return \TripManRequestProvider
     */
    public final function add( $property, $value ){
        if( !isset($this->_data[$property])){
            //fuerza siempre el valor textual
            $this->_data[$property] = strval( $value );
        }
        return $this;
    }
    /**
     * Importa un parámetro del evento
     * @param string $property
     * @param mixed $default
     * @return mixed
     */
    public final function get( $property, $default =null){
        
        return isset( $this->_data[$property]) ?
            $this->_data[$property] :
            $default;
    }
    /**
     * Retorna un callback definido o vacío si no hay nada
     * @return string
     */
    public final function getCallBack(){
        return $this->get(self::EVENT_DATA_CALLBACK,'');
    }
    /**
     * Establece un callback para gestionar puntos de retorno en determinados eventos
     * @param string $action
     * @return \TripManRequestProvider
     */
    public final function setCallBack( $action ){
        
        $this->_data[self::EVENT_DATA_CALLBACK] = $action;

        return $this;
    }
    /**
     * Obtiene una cookie
     * @param string $cookie
     * @param mixed $default
     * @return string
     */
    public static final function getCookie( $cookie, $default = null ){
        
        $input = filter_input( INPUT_COOKIE, self::prefixAttach($cookie) );
        
        return !is_null($input) ? $input : $default;
    }
    /**
     * Establece una cookie en WP agregando el prefijo de la aplicación para evitar colisiones
     * 
     * @param string $cookie
     * @param mixed $value
     * @param int $time
     * @return bool
     */
    public static final function setCookie( $cookie, $value = null, $time = 10 ){
        
        if(current_filter() === 'wp' ){
            $maximum = TripManager::getOption('tripman_token_persistency',10);

            if( $time > $maximum ){
                //máximo a 50 minutos
                $time = $maximum;
            }

            return setcookie(
                    self::prefixAttach($cookie), $value,
                    time() + ( $time  * 60) );
        }

        return false;
    }
    /**
     * Importa un valor directamente de las variables GET/POST que no será procesada por
     * el prefijo de la aplicación (para capturar variables externas a la aplicación cuando se
     * requiera)
     * @param string $var
     * @param mixed $default
     * @return string
     */
    public static final function importValue( $var, $default = null ){
        
        $post = filter_input(INPUT_POST, $var);
        
        $get = filter_input(INPUT_GET, $var);
        
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
     * @param string $format Formato de extracción de ls valores de la lista, texto por defecto
     * @return array
     */
    public final function getArray( $property, $separator = ',', $format = TripManDictionary::FIELD_TYPE_TEXT ){
        $input = explode($separator, $this->get($property, ''));
        $list = array();
        foreach ( $input as $value) {
            switch( $format ){
                case TripManDictionary::FIELD_TYPE_NUMBER:
                    $list[] = intval($value);
                    break;
                case TripManDictionary::FIELD_TYPE_FLOAT:
                    $list[] = floatval($value);
                    break;
                default:
                    $list[] = $value;
                    break;
            }
        }
        return $list;
    }
    /**
     * @return int WP User ID
     */
    public final function getUID(){ return $this->_userId; }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public static final function requestClientIP(){
        
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }
    /**
     * @return array Devuelve todos los datos adjuntos en el evento
     */
    public final function getData(){
        return $this->_data;
    }
    /**
     * @return string Event Type
     */
    public final function getAction(){ return $this->_action; }
    /**
     * @return string Contexto
     */
    public final function getContext(){ return $this->_context; }
    /**
     * Define el módulo objetivo de la request
     * @return string
     */
    public final function getModule(){ return $this->_module; }
    /**
     * Redirige una request para delegar a un nuevo contexto/acción de controlador
     * @param string $action ACCION o CONTEXTO.ACCION del evento
     * @param array $data
     * @return \TripManRequestProvider
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
            $this->_data = $data;
        }
        
        return $this;
    }
    /**
     * Genera y registra un token en las cookies del navegador
     * Solo se admite el registro de tokens dentro de plugins_loaded
     * @return string
     */
    public static final function checkToken(){
        
        $token_key = TripManager::PLUGIN_NAME.'_token';
        
        $token = TripManRequestProvider::importValue($token_key,'');
        //solo admitir el registro de tokens dentro del  hook plugins_loaded
        if( strlen( $token ) > 0 && current_action() === 'plugins_loaded' ){
            
            self::$_token = $token;
            
            setcookie($token_key, $token, 500 );
        }
        
        return self::$_token;
    }
    /**
     * @return string
     */
    public static final function getToken(){
        return !is_null(self::$_token) ? self::$_token : '';
    }
    /**
     * Elimina el token
     * @return boolean
     */
    public static final function clearToken(){

        if( current_action() === 'plugins_loaded' ){
        
            $token_key = self::PLUGIN_NAME.'_token';
            
            return setcookie( $token_key , null , -3600 );
        }
    }
    /**
     * @return boolean
     */
    public static final function isAdmin(){
        return is_admin();
    }
}


