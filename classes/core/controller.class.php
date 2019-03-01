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
     * Crea un gestor de vistas
     * @return \TripManRenderer
     */
    protected final function createView(){
        return TripManRenderer::createRender(TripManager::instance()->getProfile());
    }
    /**
     * Ejecuta el controlador
     * @param TripManRequestProvider $request
     * @return bool
     */
    public function action( TripManRequestProvider $request ){
        
        $callback = sprintf('%s_action', $request->getAction());
        
        if(method_exists($this, $callback)){
            
            return $this->$callback( $request );
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
        return parent::__toString();
    }
    /**
     * Genera un error (visual o redirigido a un log)
     * @param TripManRequestProvider $request
     * @return boolean
     */
    protected function error_action( TripManRequestProvider $request ){
        
        $model = TripManager::createModel('Error',$request);
        
        $callback = $request->get(TripManRequestProvider::EVENT_DATA_CALLBACK,'');
        
        $display = TripManRenderer::createRender(TripManager::instance()->getProfile());
        
        if(strlen($callback)){
            //botón para reddirigir a otro contexto
            $display->set(TripManRequestProvider::EVENT_DATA_CALLBACK, $callback);
        }
        
        $display->set('display_notifier', false)->setModel($model)->render('error');
        
        return FALSE;
    }
    /**
     * Acción por defecto del controlador
     */
    abstract protected function default_action( TripManRequestProvider $request );
    /**
     * Carga un controlador. Retorna un controlador de error si no se ha encontrado el deseado
     * @param string $context Controlador a cargar
     * @param string|null $module Carga un controlador del modulo en uso
     * @return \TripManController
     */
    public static final function loadController( $context , $module = null ){
        
        if(is_null($module)){
            $module = TripManager::instance()->getProfile();
        }
        
        $base_path = sprintf('%scomponents/controllers/%s.controller.php',
                MNK__TRIPMAN__DIR,  strtolower( $context ) );
        
        $module_path = !is_null($module) ?
                sprintf('%smodules/%s/controllers/%s.controller.php',
                MNK__TRIPMAN__DIR,  $module,  strtolower( $context ) ) :
                null;
        
        $class = sprintf('TripMan%sController',$context);
        
        //var_dump($base_path);
        //var_dump($module_path);
        //var_dump($class);
        
        
        if( !is_null($module)){
            
            if( file_exists($module_path) ){
                
                require_once $module_path;
                
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
     * @param \TripManRequestProvider $request
     * @return \TripManController
     */
    public function redirect( \TripManRequestProvider $request ){
        if( $this->_redirections < self::MAX_REDIRECTIONS ){
            return self::loadController($request->getContext());
        }
        return $this;
    }
    /**
     * @return string Nombre del controlador (contexto)
     */
    public function getName() {
        //parent::getName();
        
        $class = get_class($this);
        //Controller
        $suffix_length = strlen($class) - strrpos($class, 'Controller');
        //TripMan[NOMBRE]Controller
        return strtolower( substr($class, 7, strlen($class) - $suffix_length - 7 ) );
    }
}