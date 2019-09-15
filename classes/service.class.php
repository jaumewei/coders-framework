<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Manejador de servicios de la intranet
 */
abstract class Service extends Component implements IService{
    /**
     * Instancia un servicio y lo agrega automáticamente a la cola de servicios de la aplicación
     * @param array $settings
     */
    protected function __construct( array $settings = null ) {

        if( !is_null($settings)){
        
            foreach( $settings as $var=>$val){
                //$this->_settings[$var] = $val;
                $this->set($var, $val);
            }
        }
    }
    /**
     * Instrucciones a ejecutar antes de procesar elservicio
     */
    abstract protected function onBeforeDispatch();
    /**
     * Ejecución del servicio
     */
    abstract protected function onDispatch();
    /**
     * Instrucciones a ejecutar después de ejecutar el servicio
     */
    abstract protected function onAfterDispatch();
    /**
     * Ejecuta el servicio
     * @return bool Resultado de la ejecución del servicio
     */
    public function dispatch(){
        
        $this->onBeforeDispatch();
        
        $result = $this->onDispatch();
        
        $this->onAfterDispatch();
        
        return $result;
        
    }
    /**
     * Crea una instancia de un servicio solicitado
     * @param string $service
     * @param array $data Inicialización o datos adjuntosd del servicio
     * @return \TripManIService | null
     */
    public static final function createInstance( $service, array $data = null ){
        
        $class = sprintf('TripMan%sService',$service);

        if( !class_exists($class)){

            $path = sprintf('%scomponents/services/%s.service.php',
                    MNK__TRIPMAN__DIR,  strtolower($service) );

            if(!file_exists($path)){

                TripManLogProvider::error(
                        TripManStringProvider::__('Servicio no encontrado %s',$path),
                        'TripManService');
                return null;
            }
            
            require_once($path);
            
            //comprueba que existe la clase y que implementa IService
            if( !class_exists($class) || !is_subclass_of($class, 'TripManIService',true)){

                TripManLogProvider::error(
                        TripManStringProvider::__('Servicio no encontrado %s',$class),
                        'TripManService');

                return null;
            }
        }

        //si llega hasta aqui, se crea y registra el servicio
        $instance = new $class( $data );

        //momento para registrar el servicio en la aplicación
        //todos los servicios se procesarán desde una lista al finalizar con
        //la respuesta de usuario
        TripManager::instance()->registerService($instance);

        return $instance;
    }

    /**
     * Retorna el tipo de servicio de la instancia
     * @return string
     */
    public function getName(){
        
        $class = parent::getName();
        
        $offset = strlen($class) - strrpos($class, 'Service');
        
        return substr(strtolower( $class ), strlen(TripManager::PLUGIN_NAME) , -$offset);
    }
    /**
     * Establece un valor o propiedad del servicio
     * @param String $var
     * @param mixed $val
     */
    public function set( $var, $val ){
        parent::set($var, $val);
    }
    /**
     * Obtiene un valor o propiedad del servicio
     * @param String $var
     * @param mixed $default
     * @return mixed
     */
    public function get( $var, $default = null ){
        return parent::get($var, $default);
    }
}