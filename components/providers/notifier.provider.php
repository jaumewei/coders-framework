<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Clase de soporte para poder ejecutar notificaciones en lote en función de los modelos proveidos
 * Implementar todos los métodos de notificación e instancia de servicios aqui, separando y limpiando
 * la clase TemplateProvider para que trabaje exclusivamente con plantillas.
 */
final class Notifier{
    /**
     * Modelo para generar las plantillas
     * @var \TripManITemplate | NULL
     */
    private $_content = null;
    /**
     * Gestores de cadenas
     * @var \TripManStringProvider[] 
     */
    private static $_stringMgrs = array();
    /**
     * @var boolean Activada notificación por SMS
     */
    private $_enabledSMS = FALSE;
    /**
     * @var boolean Activada notificación por MAIL
     */
    private $_enabledEmail = FALSE;
    
    public final function __construct( \TripManITemplate $content ) {
        
        $this->_content = $content;
        
        if( count(self::$_stringMgrs) === 0 ){
            
            $locale = TripManStringProvider::getLocale();

            foreach( TripManStringProvider::listLanguages( TRUE ) as $lang ){

                self::$_stringMgrs[ $lang ] = ( $lang === $locale ) ?
                        TripManStringProvider::instance() :
                        TripManStringProvider::loadLanguage( $lang ) ;
            }
        }
        
        $this->_enabledEmail = TripManager::getOption(
                'tripman_notify_email',
                TripManager::PLUGIN_OPTION_ENABLED) === TripManager::PLUGIN_OPTION_ENABLED;

        $this->_enabledSMS = TripManager::getOption(
                'tripman_notify_sms',
                TripManager::PLUGIN_OPTION_ENABLED) === TripManager::PLUGIN_OPTION_ENABLED;
    }
    /**
     * @return string
     */
    public final function __toString() {
        return get_class($this);
    }    
    /**
     * recupera el gestor de cadenas según el idioma solicitado
     * @param string $lang
     * @return \TripManStringProvider
     */
    private final function getTranslator( $lang ){
        return isset(self::$_stringMgrs[$lang]) ? 
                self::$_stringMgrs[ $lang ] :
                TripManStringProvider::instance();
    }
    /**
     * Genera una nototificación por SMS
     * @param \TripManTemplateProvider $template
     * @return \TripManSMSService
     */
    private final function sendSMS( \TripManTemplateProvider $template ){
        
        if( !$this->_enabledSMS ){
            TripManLogProvider::debug( TripManStringProvider::__(
                    '-->Servicio SMS desactivado' ), 'TripManNotifierProvider');
            return FALSE;
        }
        
        $telephone = $template->getTelephone();
        
        if( strlen($template->getShort()) && !is_null($telephone) && strlen($telephone) ){
            //crea y registra la instancia del servicio de SMS en la lista
            $sms = TripManager::createService( 'SMS',
                    array('content'=>$template->getShort()));

            $sms->telephone = $telephone;

            TripManLogProvider::debug(
                TripManStringProvider::__(
                    '-->Generando SMS para <strong>%s</strong> (+1)',$sms->telephone ),
                    'TripManNotifierProvider');

            return true;
        }
        else{
            TripManLogProvider::debug(
                TripManStringProvider::__(
                    '-->No hay un contenido o un destinatario SMS que procesar' ),
                    'TripManNotifierProvider');
        }
        return false;
    }
    /**
     * @param \TripManTemplateProvider $template plantilla a remitir
     * @return \TripManMailerService Servicio de MAiling
     */
    private final function sendEmail(\TripManTemplateProvider $template ){

        if( !$this->_enabledEmail ){
            TripManLogProvider::debug( TripManStringProvider::__(
                    '-->Servicio de mailing desactivado' ), 'TripManNotifierProvider');
            return FALSE;
        }
        
        $receiver = $template->getEmail();
        
        if( strlen($template->getContent()) && !is_null($receiver) && strlen( $receiver) ){
            //crea y registra la instancia del servicio de mailing en la lista
            $mailer = TripManager::createService( 'Mailer', array(
                        'content'=>$template->getContent(),
                        'subject'=>$template->getSubject()));

            $mailer->email = $receiver;
            
            TripManLogProvider::debug(
                TripManStringProvider::__(
                    '-->Generando Email para <strong>%s</strong> (+1)',$mailer->email ),
                    'TripManNotifierProvider');

            return TRUE;
        }
        else{
            TripManLogProvider::debug(
                TripManStringProvider::__(
                    '-->No hay un contenido o un destinatario Email que procesar' ),
                    'TripManNotifierProvider');
        }
        return FALSE;
    }
    /**
     * Genera una lista de notificaciones por email y SMS dados los parámetros seleccionados
     * @param string $type Tipo/Evento que genera el envío
     * @param string $context Destinatario (PUBLIC|AMIN|AGENT)
     * @param string $extra Opcional, información explícita a incluir en la plantilla
     * @return int
     */
    public final function enqueueNotifications( $type , $context = null, $extra = '' ){

        $counter = 0;
        
        //gestionar contextos/destinatarios
        if( is_null( $context ) ){
            $context = array();
        }
        elseif( !is_array( $context ) ){
            //si no es array, conviertelo en array y evitamos mas logicas
            $context = array( $context );
        }
        $default_lang = TripManStringProvider::getDefaultLanguage();
        //definición de idiomas (cambiar por cargador de idioma para traducciones mediante TripmanSTringProvider)
        $adminStrings = $this->getTranslator(
                $default_lang );
        $clientStrings = $this->getTranslator(
                $this->_content->get('booking_language', $default_lang ) );
        $agentStrings = $this->getTranslator( 
                $this->_content->get('agent_language', $default_lang ) );

        //lista plantillas por evento/tipo y contexto
        $template_list = TripManTemplateProvider::listTemplates( $type, $context );
        
        //ITERAR TODAS LAS PLANTILLAS DEL TIPO DEFINIDO
        foreach( $template_list  as $template ){
            //seleccionar los destinatarios en función del contexto
            switch( $template->getContext() ){
                case TripManager::PROFILE_ADMIN:
                    if( $template->getLanguage() === $adminStrings->getLanguage() ){
                        $content = $this->_content->getTemplateData( $adminStrings );
                        //adjuntar datos definidos por el usuario
                        $content[TripManTemplateProvider::TEMPLATE_EXTRA] = $extra ;
                        //generar los contenidos de la plantilla (rellenar con datos)
                        $instance = $template->fillTemplate( $content );
                        //sin filtro de idioma
                        if($this->sendEmail($instance ) ){ $counter++; }
                        if($this->sendSMS($instance ) ){ $counter++; }
                    }
                    break;
                case TripManager::PROFILE_AGENT:
                    if( $template->getLanguage() === $agentStrings->getLanguage() ){
                        $content = $this->_content->getTemplateData( $agentStrings );
                        //adjuntar datos definidos por el usuario
                        $content[TripManTemplateProvider::TEMPLATE_EXTRA] = $extra ;
                        //generar los contenidos de la plantilla (rellenar con datos)
                        $instance = $template->fillTemplate( $content );
                        // de momento todas las notificaciones en español
                        if(strlen($instance->getContent())){
                            $this->sendEmail( $instance );
                            $counter++;
                        }
                        if(strlen($instance->getShort())){
                            $this->sendSMS( $instance );
                            $counter++;
                        }
                    }
                    break;
                case TripManager::PROFILE_PUBLIC:
                    if( $template->getLanguage() === $clientStrings->getLanguage() ){
                        $content = $this->_content->getTemplateData( $clientStrings );
                        //adjuntar datos definidos por el usuario
                        $content[TripManTemplateProvider::TEMPLATE_EXTRA] = $extra ;
                        //generar los contenidos de la plantilla (rellenar con datos)
                        $instance = $template->fillTemplate( $content );
                        //procesar plantillas de solo el idioma de cliente
                        if(strlen($instance->getContent())){
                            $this->sendEmail( $instance );
                            $counter++;
                        }
                        if(strlen($instance->getShort())){
                            $this->sendSMS( $instance );
                            $counter++;
                        }
                    }
                    break;
            }
        }

        return $counter;
    }
    /**
     * genera una notificación masiva de todos los elementos proveidos
     * @param \TripManITemplate[] $contentList
     * @param string $type
     * @param string $context
     * @param string $extra
     * @return int contador de notificaciones emitidas (todas las reservas + todos los contextos + email y sms)
     */
    public static final function massNotify( array $contentList , $type, $context = null , $extra = '' ){
        
        $counter = 0;
        
        if( count( $contentList ) ){
            
            foreach( $contentList as $content ){

                $notify = new TripManNotifierProvider($content);
                
                $counter = $notify->enqueueNotifications($type, $context, $extra);
            }
        }
        
        return $counter;
    }
}


