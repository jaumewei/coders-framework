<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Manejador de servicios de la intranet
 */
abstract class Service extends Component{
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
     * Ejecuta el servicio
     * @return bool Resultado de la ejecución del servicio
     */
    public function dispatch(){
        
        return TRUE;
        
    }
    /**
     * Crea una instancia de un servicio solicitado
     * @param string $service
     * @param array $data Inicialización o datos adjuntosd del servicio
     * @return \CODERS\Framework\Service | boolean
     */
    public static final function createInstance( $service, array $data = null ){
        
        $class = sprintf('\CODERS\Framework\Services\%sService',$service);

        if( !class_exists($class)){

            $path = sprintf('%scomponents/services/%s.service.php',
                    CODERS_FRAMEWORK_BASE,  strtolower($service) );

            if(!file_exists($path)){

                Providers\Log::error(sprintf( 'INVALID_SERVICE [%s]',$path));
                
                return FALSE;
            }
            
            require_once($path);
            
            //comprueba que existe la clase y que implementa IService
            if( !class_exists($class) || !is_subclass_of($class, '\CODERS\Framework\Service',true)){

                Providers\Log::error('INVALID_SERCICE [%s]',$class);

                return FALSE;
            }
        }

        //si llega hasta aqui, se crea y registra el servicio
        return new $class( $data );
    }
}