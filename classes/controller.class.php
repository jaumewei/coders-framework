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
    
    private $_appName,$_appKey;
    
    //private $_appName;
    
    /**
     * @param \CodersApp $app
     */
    protected function __construct( \CodersApp $app ) {
        
        $this->_appName = $app->endPointName();
        
        $this->_appKey = $app->endPointKey();
    }
    /**
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\FormRender
     */
    protected function displayForm( Request $R ){
        
        if(!class_exists('\CODERS\Framework\Views\FormRender')){

            require_once( sprintf('%s/components/renders/form.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        $view = new \CODERS\Framework\Views\FormRender( );
        
        return $view->setup($R->endPoint(), $R->module());
    }
    /**
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\MapRender
     */
    protected function displayMap(Request $R ){

        if(!class_exists('\CODERS\Framework\Views\MapRender')){

            require_once( sprintf('%s/components/renders/document.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        $view = new \CODERS\Framework\Views\MapRender( );
        
        return $view->setup($R->endPoint(), $R->module());
    }
    /**
     * 
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\CalendarRender
     */
    protected function displayCalendar( Request $R ){

        if (!class_exists('\CODERS\Framework\Views\CalendarRender')) {

            require_once( sprintf('%s/components/renders/calendar.render.php', CODERS_FRAMEWORK_BASE) );
        }

        $view = new \CODERS\Framework\Views\CalendarRender();
        
        return $view->setup($R->endPoint(), $R->module());
    }
    /**
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\Views\Renderer | boolean
     */
    protected function displayView( Request $R ){
       
        if(!class_exists('\CODERS\Framework\Views\ViewRender')){

            require_once( sprintf('%s/components/renders/view.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        $view =  new \CODERS\Framework\Views\ViewRender();
        
        return $view->setup($R->endPoint() , $R->module());
    }
    /**
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\DocumentRender
     */
    protected function displayDocument( Request $R ){

        if(!class_exists('\CODERS\Framework\Views\DocumentRender')){

            require_once( sprintf('%s/components/renders/document.render.php',CODERS_FRAMEWORK_BASE) );
        }
        
        $view = new \CODERS\Framework\Views\DocumentRender();
        
        return $view->setup($R->endPoint(), $R->module());
    }
    /**
     * @param \CODERS\Framework\IModel $content
     * @param array $headers
     */
    protected function json( IModel $content , array $headers = array( ) ){
        
        //headers
        
        json_encode( $content->toArray() );
    }
    /**
     * Ejecuta el controlador
     * @param \CODERS\Framework\Request|NULL $request
     * @return bool
     */
    public function __execute( Request $request = NULL ){
        
        $action = sprintf('%s_action', !is_null($request) ? $request->action() : 'default' );
        
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
    protected function error_action( Request $request ){
        
        var_dump($request);
        
        return FALSE;
    }
    /**
     * Acción por defecto del controlador
     */
    abstract protected function default_action( Request $request = NULL );
    /**
     * @param \CodersApp $app
     * @param \CODERS\Framework\Request $R
     * @return \CODERS\Framework\Controller | boolean
     */
    public static final function request( \CodersApp $app, Request  $R ){
        
        return self::create($app, $R->context(), $R->isAdmin());
        
        /**
         * @deprecated since version number
         */
        
        $path = sprintf('%s/modules/%s/controllers/%s.controller.php', $app->appPath(),
                //select administrator or public module
                $R->isAdmin() ? 'admin' : 'public', $R->context());

        $class = sprintf('\CODERS\Framework\Controllers\%sController', $R->context());

        if (file_exists($path)) {

            require_once $path;

            if (class_exists($class) && is_subclass_of($class, \CODERS\Framework\Controller::class, TRUE)) {

                return new $class($app);
            }
        }

        return FALSE;
    }
    /**
     * 
     * @param \CodersApp $app
     * @param string $controller
     * @param boolean $admin
     * @param string $parent
     * @return \CODERS\Framework\Controller|boolean
     */
    public static final function create( \CodersApp $app , $controller , $admin = FALSE ){

        $path = sprintf('%s/modules/%s/controllers/%s.controller.php',
                $app->appPath(),
                $admin ? 'admin' : 'public',
                strtolower( $controller ) );

        $class = sprintf('\CODERS\Framework\Controllers\%sController', $controller);

        if (file_exists($path)) {

            require_once $path;

            if ( class_exists($class) && is_subclass_of($class, \CODERS\Framework\Controller::class, TRUE)) {

                return new $class($app);
            }
        }

        return FALSE;
    }
    /**
     * Redirige un controlador a otro (mucho ojo a las redirecciones, máximo 3)
     * @param \\CODERS\Framework\Request $request
     * @return \TripManController
     */
    public function redirect( Request  $request ){
        if( $this->_redirections < self::MAX_REDIRECTIONS ){
            return self::loadController($request->getContext());
        }
        return $this;
    }
    /**
     * @return string
     */
    public function getPageTitle(){ return __('Page Title','coders_framework'); }
    /**
     * @return string
     */
    public function getOptionTitle(){ return __('Menu Title','coders_framework'); }
    /**
     * @return string
     */
    public function getName(){ return strval($this); }
    /**
     * @return string
     */
    public function getParent(){ return ''; }
    /**
     * @return array
     */
    public function getCapabilities(){ return 'administrator'; }
    /**
     * @return string
     */
    public function getIcon(){ return 'dashicons-grid-view'; }
    /**
     * @return int
     */
    public function getPosition(){ return 50; }
    /**
     * @return string
     */
    public function getAppName(){ return $this->_appName; }
    /**
     * @return string
     */
    public function getAppKey(){ return $this->_appKey; }
}


