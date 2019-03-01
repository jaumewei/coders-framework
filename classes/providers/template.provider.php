<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestiona los posts
 */
final class Template extends TripManComponent{

    //estas notificaciones de momento no se utilizarán
    const TEMPLATE_TYPE_BOOKING_CANCEL = 'booking_cancel';      //notifica la cancelación de una reserva
    const TEMPLATE_TYPE_BOOKING_CHANGE = 'booking_change';      //Notifica el cambio de fecha de reserva

    //Notificaciones del form de venta directa
    const TEMPLATE_TYPE_BOOKING_PENDING = 'booking_pending';      //notifica la creación de una reserva pendiente de pago venta web
    const TEMPLATE_TYPE_BOOKING_CONFIRM = 'booking_confirm';    //notifica el pago de la reserva venta web (y notificación admin)
    const TEMPLATE_TYPE_BOOKING_AGENT = 'booking_agent';        //notifica la creación de una reserva agente
    const TEMPLATE_TYPE_BOOKING_TOUROPERATOR = 'booking_touroperator';      //notifica la creación de una reserva turoperador
    //const TEMPLATE_TYPE_BOOKING_ADMIN = 'booking_admin';      //notifica la creación de una reserva desde el administrador

    //eventos relacionados a una salida
    const TEMPLATE_TYPE_TRIP_CAPABILITY = 'trip_capability';    //notifica al superar el umbral de capacidad
    const TEMPLATE_TYPE_TRIP_FULL = 'trip_complete';            //notifica al llegar al 100% de la capacidad
    const TEMPLATE_TYPE_TRIP_CLOSE = 'trip_close';              //Cierre de la salida (preparado para el día siguiente)
    const TEMPLATE_TYPE_TRIP_CHANGE = 'trip_change';            //Notificación de cambios de la excursión (cancelación, u otros motivos)
    //const TEMPLATE_TYPE_TRIP_CANCEL = 'trip_cancel';                //notifica el cambio de fecha de una salida (obsoleto, se resuelve mediante booking_change)
    //const TEMPLATE_TYPE_TRIP_CHECKIN = 'trip_checkin';          //Plantilla del checkin

    const TEMPLATE_TYPE_TICKET = 'booking_ticket';      //plantilla del ticket para imprimir    
    const TEMPLATE_TYPE_TEST = 'dump_test';                 //prueba para visualizar el contenido de una plantilla
    const TEMPLATE_TYPE_UNDEFINED = 'undefined';
    
    const POST_TYPE = 'tripman_template';
    const POST_ID = 'ID';
    const POST_NAME = 'post_name';
    const POST_STATUS_PUBLISHED = 'publish';
    const POST_STATUS_DRAFT = 'draft';
    const POST_STATUS_PENDING = 'pending';
    
    const TEMPLATE_TITLE = 'post_title';
    const TEMPLATE_CONTENT = 'post_content';
    const TEMPLATE_SHORT = 'post_excerpt';
    //contexto al que va dirigido la plantilla (cliente, administrador, agente ...)
    const TEMPLATE_CONTEXT = 'context';
    //motivo o evento donde se debe generar la notificación
    const TEMPLATE_TYPE = 'model';
    const TEMPLATE_STATUS = 'status';
    //registro del idioma para las traducciones
    const TEMPLATE_LANGUAGE = 'lang';
    
    //const NOTIFY_EMAIL = 'email';
    //const NOTIFY_TELEPHONE = 'telephone';
    
    const TEMPLATE_EXTRA = 'extra';
    
    const OUTPUT_TYPE_EMAIL = 'email';
    const OUTPUT_TYPE_SMS = 'sms';
    const OUTPUT_TYPE_TICKET = 'ticket';

    //información del post
    private $_content = '';
    private $_short = '';
    private $_title = '';
    //datos adjuntos explicitos
    private $_extra = '';
    /**
     * @param array $content Datos a importar
     * @param type $is_template Define si es una plantilla o notificación final
     */
    private function __construct( array $content ) {
        
        foreach( $content as $var => $val ){

            $this->set($var, $val);
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return $this->getPostId() > 0 ?
                sprintf('%s:%s:%s[ID.%s]',strtolower( $this->getContext() ),$this->getType(),$this->getLanguage(),$this->getPostId()):
                sprintf('%s:%s:%s',strtolower( $this->getContext() ),$this->getType(),$this->getLanguage());
    }
    /**
     * Obtener información de la plantilla
     * @param string $field
     * @return string
     */
    public final function __get( $field ) {
        return $this->get($field, '');
    }
    /**
     * Establecer valores
     * @param string $name
     * @param mixed $value
     */
    public final function __set($name, $value) {
        $this->set($name, $value);
    }
    /**
     * Obtiene un valor
     * @param string $var
     * @param mixed $default
     */
    public final function get($var, $default = null) {
        switch( $var ){
            case self::TEMPLATE_CONTENT: return $this->_content;
            case self::TEMPLATE_SHORT: return $this->_short;
            case self::TEMPLATE_TITLE: return $this->_title;
            case self::TEMPLATE_EXTRA: return $this->_extra;
            default: return parent::get($var, $default);
        }
    }
    /**
     * Establece un valor
     * @param string $var
     * @param mixed $val
     * @return \TripManTemplateProvider
     */
    public final function set($var, $val) {
        switch( $var ){
            case self::TEMPLATE_CONTENT:
                $this->_content = $val;
                break;
            case self::TEMPLATE_SHORT:
                $this->_short = $val;
                break;
            case self::TEMPLATE_TITLE:
                $this->_title = $val;
                break;
            case self::TEMPLATE_EXTRA:
                $this->_extra = $val;
                break;
            default:
                parent::set($var, $val);
        }
        return $this;
    }
    /**
     * ID del post de plantilla
     * @return int
     */
    public final function getPostId(){
        return $this->get(self::POST_ID,0);
    }
    /**
     * Contenido
     * @return string
     */
    public final function getContent(){
        return $this->_content;
    }
    /**
     * Título
     * @return string
     */
    public final function getSubject(){
        return $this->_title;
    }
    /**
     * Extracto
     * @return string
     */
    public final function getShort(){
        return $this->_short;
    }
    /**
     * Contexto
     * @return string
     */
    public final function getContext(){
        return $this->get(self::TEMPLATE_CONTEXT,'');
    }
    /**
     * Retorna el idioma seleccionado para la plantilla, por defecto, siempre es_ES
     * @return string
     */
    public final function getLanguage(){
        return $this->get(self::TEMPLATE_LANGUAGE,  TripManStringProvider::LANGUAGE_DEFAULT );
    }
    /**
     * Información extra a adjuntar
     * @return string
     */
    public final function getExtra(){
        return $this->_extra;
    }
    /**
     * Establecer información extra adjunta (explícitamente a la plantilla y copias en uso)
     * OBSOLETO
     * @param string $extra
     */
    public final function setExtra( $extra ){
        $this->set(self::TEMPLATE_EXTRA, $extra);
    }
    /**
     * Tipo de notificación
     * @return string
     */
    public final function getType(){
        return $this->get(self::TEMPLATE_TYPE,self::TEMPLATE_TYPE_UNDEFINED);
    }
    /**
     * Indica si es una plantilla
     * @return bool
     */
    public final function isTemplate(){
        return $this->getPostId() > 0;
    }
    /**
     * @return string Obtiene el email del destinatario en función del contexto
     */
    public final function getEmail(){
        switch( $this->getContext() ){
            case TripManager::PROFILE_ADMIN:
                return TripManager::getOption('admin_email');
            case TripManager::PROFILE_AGENT:
                return $this->get('agent_email','');
            case TripManager::PROFILE_PUBLIC:
                return $this->get('booking_email','');
            default: return '';
        }
    }
    /**
     * @return string Teléofno del destinatario en función del contexto
     */
    public final function getTelephone(){
        switch( $this->getContext() ){
            case TripManager::PROFILE_ADMIN:
                return TripManager::getOption('tripman_telephone');
            case TripManager::PROFILE_AGENT:
                return $this->get('agent_telephone','');
            case TripManager::PROFILE_PUBLIC:
                return $this->get('booking_telephone','');
            default: return '';
        }
    }
    /**
     * @param string $field
     * @return string
     */
    private function inputEncase( $field ){
        return sprintf('{%s}',$field);
    }
    /**
     * Crea una copia de la plantilla con los datos reales a remitir.
     * 
     * Ete método solo genera la copia relenandola con datos desde un array.
     * De por sí no resuelve nada.
     * 
     * @param \TripManIModel $data Modelo de datos
     * @return \TripManTemplateProvider Contenido del mensaje a enviar
     */
    public final function fillTemplate( array $data ){
        TripManLogProvider::debug(
                TripManStringProvider::__('-->Procesando Plantilla <strong>%s</strong>',$this),
                $this);
        //lista de campos a importar sobre la nueva instancia de la plantilla
        $content = array(
            self::TEMPLATE_TITLE => $this->getSubject(),
            self::TEMPLATE_CONTENT => $this->getContent(),
            self::TEMPLATE_SHORT => $this->getShort(),
            //estos filtros siempre son importantes
            self::TEMPLATE_CONTEXT => $this->getContext(),
            self::TEMPLATE_TYPE => $this->getType(),
            self::TEMPLATE_LANGUAGE => $this->getLanguage(),
        );
        
        //importar (reemplazar) todo el contenido
        foreach( $data as $field => $value ){

            switch( $field ){
                //importar los datos necesarios en la plantilla para generar las notificaciones
                case 'booking_email':
                case 'booking_telephone':
                case 'agent_email':
                case 'agent_telephone':
                    $content[ $field ] = $value;
                    break;
            }
            
            //asunto
            $content[self::TEMPLATE_TITLE] = str_replace(
                    $this->inputEncase( $field ), $value,
                    $content[self::TEMPLATE_TITLE]);
            //contenido
            $content[self::TEMPLATE_CONTENT] = str_replace(
                    $this->inputEncase( $field ), $value,
                    $content[self::TEMPLATE_CONTENT]);
            //texto corto
            $content[self::TEMPLATE_SHORT] = str_replace(
                    $this->inputEncase( $field ), $value,
                    $content[self::TEMPLATE_SHORT]);
        }
        //extra en contenido
        $content[self::TEMPLATE_CONTENT] = str_replace(
                $this->inputEncase(self::TEMPLATE_EXTRA),
                $this->getExtra(),
                $content[self::TEMPLATE_CONTENT]);
        //extra en texto corto
        $content[self::TEMPLATE_SHORT] = str_replace(
                $this->inputEncase(self::TEMPLATE_EXTRA),
                $this->getExtra(),
                $content[self::TEMPLATE_SHORT]);

        //tirar por pantalla
        //var_dump($message);
        
        //crea una nueva notificación desde la plantilla
        return new TripManTemplateProvider( $content );
    }
    /**
     * Extrae el contenido de la plantilla
     * @param TripManIModel $model
     * @return HTML
     */
    public final function extract( \TripManITemplate $model ){
        
        $content = $this->fillTemplate($model->getTemplateData());
        
        return $content->getContent();
    }
    /**
     * Extrae el contenido de la plantilla
     * @param TripManIModel $model
     * @return HTML
     */
    public final function createTicket( \TripManITemplate $model ){
        
        $content = $this->fillTemplate($model->getTemplateData());
        
        return $content->getContent();
    }
    /**
     * Carga una lista de plantillas en función de los parámetros indicados.
     * 
     * Dependiendo del contexto de la aplicación donde se deba generar una notificación,
     * este método puede filtrar únicamente las plantillas de los destinatarios a quienes
     * deba llegar el mensaje.
     * 
     * @param string $type Tipo de evento asignado a la notificación
     * @param mixed $context Contexto/Destinatario de la plantilla, puede ser una cadena de texto o un array
     * @param string $language Idioma seleccionado para la plantilla
     * @return \TripManTemplateProvider[] Lista de plantillas
     */
    public static final function listTemplates( $type = null, $context = null, $language = null ){
        
        $templates = array();
        
        $args = array(
            'post_type' => TripManTemplateProvider::POST_TYPE,
            'meta_query' => array(),
            //SIEMPRE SIEMPRE SIEMPRE, PLANTILLAS PUBLICADAS, IGNORAR EL RESTO
            'post_status' => self::POST_STATUS_PUBLISHED,
            'posts_per_page' => 5000);
        //filtra por tipo de evento
        if( !is_null($type)){
            $args['meta_query'][] = array(
                'key' => self::TEMPLATE_TYPE,
                'value' => $type,
            );
        }
        //filtra por idioma
        if( !is_null($language)){
            $args['meta_query'][] = array(
                'key' => self::TEMPLATE_LANGUAGE,
                'value' => $language,
            );
        }
        //filtra por contexto
        if( !is_null($context)){
            
            $query = array(
                'key' => self::TEMPLATE_CONTEXT,
                'value' => $context,
            );
            
            if( is_array($context)){
                //agrega una lista de valores en lugar de uno solo para la selección
                $query['compare'] = 'IN';
            }
            
            $args['meta_query'][] = $query;
        }

        $list = new WP_Query($args);
        
        foreach ($list->posts as $post) {
            
            $templates[] = self::loadTemplate( $post );
        }

        return $templates;
    }
    /**
     * Importa la plantilla desde un post
     * @param mixed $post ID del post o instancia de WP_Post
     * @return \TripManTemplateProvider
     */
    public static final function loadTemplate( $post ){

        
        if(is_numeric($post)){
            $post = get_post( $post );
        }
        elseif( is_object($post) && get_class($post) === 'WP_Post' ){
            //nothing...
        }
        else{
            return null;
        }
        
        $post_data = array(
            self::POST_ID => $post->ID,
            self::TEMPLATE_CONTENT => $post->post_content,
            self::TEMPLATE_TITLE => $post->post_title,
            self::TEMPLATE_SHORT => $post->post_excerpt,
            
            self::TEMPLATE_LANGUAGE => get_post_meta($post->ID, self::TEMPLATE_LANGUAGE, true ),
            self::TEMPLATE_CONTEXT => get_post_meta($post->ID, self::TEMPLATE_CONTEXT , true ),
            self::TEMPLATE_TYPE => get_post_meta($post->ID, self::TEMPLATE_TYPE , true ),
        );
        
        $template =  new TripManTemplateProvider( $post_data );
        
        return $template;
    }
    /**
     * Lista todos los contextos a los que se aplica la norificación
     * No es el mejor sitio para ponerlo, pero de momento vale
     * @return array
     */
    public static final function listContexts(){
        return array(
            TripManager::PROFILE_PUBLIC => 'Cliente',
            TripManager::PROFILE_ADMIN => 'Administrador',
            TripManager::PROFILE_AGENT => 'Agente / Turoperador',
        );
    }
    /**
     * Lista todos los sucesos que generan la notificación. 
     * Definir aquí todos los tipos de notificaciones admisibles
     * @return array
     */
    public static final function listTypes(){
        return array(
            self::TEMPLATE_TYPE_BOOKING_AGENT => 'Al crear la reserva (Agentes)',
            self::TEMPLATE_TYPE_BOOKING_TOUROPERATOR => 'Al crear la reserva (Turoperadores)',

            //self::TEMPLATE_TYPE_BOOKING_ADMIN => 'Al crear a reserva (Administrador y tel&eacute;fono)',

            self::TEMPLATE_TYPE_BOOKING_PENDING => 'Reserva pendiente de pago (Venta WEB)',
            self::TEMPLATE_TYPE_BOOKING_CONFIRM => 'Reserva pagada y confirmada (Venta WEB)',

            //no se utilizan de momento
            self::TEMPLATE_TYPE_BOOKING_CANCEL => 'Al cancelar la reserva',
            self::TEMPLATE_TYPE_BOOKING_CHANGE => 'Al cambiar la reserva',

            self::TEMPLATE_TYPE_TICKET => 'Impreso ticket de reserva',
            self::TEMPLATE_TYPE_TRIP_CAPABILITY => 'Al ir llen&aacute;ndose el cupo de plazas',
            self::TEMPLATE_TYPE_TRIP_CHANGE => 'Al reprogramar la salida (mover reservas)',
            self::TEMPLATE_TYPE_TRIP_FULL => 'Al completarse el cupo de plazas',
            self::TEMPLATE_TYPE_TRIP_CLOSE => 'Cierre de la salida',
            self::TEMPLATE_TYPE_TEST => 'Pruebas de contenido de la plantilla',
            //self::TEMPLATE_TYPE_TRIP_CHECKIN => 'Impreso Checkin',
            //self::TEMPLATE_TYPE_TRIP_CANCEL => 'Al cancelar la salida',
        );
    }
    /**
     * Mostrar el tipo de plantilla
     * @param string $type
     * @return string
     */
    public static final function displayType( $type ){
        
        $list = self::listTypes();
        
        return isset( $list[$type]) ? $list[$type] : TripManStringProvider::__('Indefinido');
    }
    /**
     * Mostrar el contexto de plantilla
     * @param string $context
     * @return string
     */
    public static final function displayContext( $context ){
        
        $list = self::listContexts();
        
        return isset( $list[$context]) ? $list[$context] : TripManStringProvider::__('Indefinido');
    }
    /**
     * Estado de la plantilla (post de wordpress)
     * @param string $status
     * @return string
     */
    public static final function displayStatus( $status ){
        switch( $status ){
            case self::POST_STATUS_PENDING:
                return TripManStringProvider::__('Pendiente de revisi&oacute;n');
            case self::POST_STATUS_DRAFT:
                return TripManStringProvider::__('Borrador');
            case self::POST_STATUS_PUBLISHED:
                return TripManStringProvider::__('Publicado');
            default:
                return $status;
        }
    }
}



