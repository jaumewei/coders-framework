<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class Controller extends Component{
    
    const MAX_REDIRECTIONS = 3;
    
    /**
     * @var int
     */
    private $_redirections = 0;
    /**
     * Ejecuta el controlador
     * @param \CODERS\Framework\Request $request
     * @return bool
     */
    public function __execute( Request $request ){
        
        $action = sprintf('%s_action', $request->action());
        
        if(method_exists($this, $action)){
            
            return $this->$action( $request );
        }
        
        TripManLogProvider::error(
                TripManStringProvider::__('Opci&oacute;n inv&aacute;lida'),
                $this);
        
        return $this->error_action($request);
    }
    /**
     * @return string
     */
    public function __toString() {
        return \CodersApp::nominalize(parent::__toString());
    }
    /**
     * Genera un error (visual o redirigido a un log)
     * @param \CODERS\Framework\Request $request
     * @return boolean
     */
    protected function error_action( Providers\Request $request ){
        
        var_dump($request);
        
        return FALSE;
    }
    /**
     * Acción por defecto del controlador
     */
    abstract protected function default_action( Providers\Request $request );
    /**
     * Carga un controlador. Retorna un controlador de error si no se ha encontrado el deseado
     * @param string $context Controlador a cargar
     * @return \CODERS\Framework\Controller
     */
    public static final function create( $context ){
        
        $module = '';
        
        $base_path = sprintf('%scomponents/controllers/%s.controller.php',
                MNK__TRIPMAN__DIR,  strtolower( $context ) );
        
        $app_path = !is_null($module) ?
                sprintf('%smodules/%s/controllers/%s.controller.php',
                MNK__TRIPMAN__DIR,  $module,  strtolower( $context ) ) :
                null;
        
        $class = sprintf('TripMan%sController',$context);
        
        if( !is_null($module)){
            
            if( file_exists($app_path) ){
                
                require_once $app_path;
                
            }
            elseif(file_exists($base_path)){
                require_once $base_path;
            }
        }
        elseif(file_exists($base_path)){
            
            require_once $base_path;
        }
        
        if(class_exists($class) && is_subclass_of($class, 'TripManController', true ) ){
            
            return new $class();
        }
        
        $error_path = sprintf('%s/components/controllers/error.controller.php', MNK__TRIPMAN__DIR );

        require_once $error_path;

        return new TripManErrorController();
    }
    /**
     * Redirige un controlador a otro (mucho ojo a las redirecciones, máximo 3)
     * @param \\CODERS\Framework\Request $request
     * @return \TripManController
     */
    public function redirect( Providers\Request  $request ){
        if( $this->_redirections < self::MAX_REDIRECTIONS ){
            return self::loadController($request->getContext());
        }
        return $this;
    }
}


