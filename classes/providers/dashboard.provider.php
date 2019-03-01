<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor de vistas para el panel de administración
 */
class DashBoard extends TripManComponent{
    
    private $_callback = NULL;
    
    /**
     * @param string $name
     * @param string $title
     * @return \TripManDashBoardProvider
     */
    public static final function registerDashBoard( $name, $title , $callback ){
        
        $dashboard = new TripManDashBoardProvider();
        
        $dashboard->set('name', strtolower( $name ) );
        
        $dashboard->set('title', $title);
        
        $dashboard->_callback = $callback;
        
        wp_add_dashboard_widget(
                $dashboard->getName( TRUE ) ,
                $dashboard->getTitle() ,
                function() use( $dashboard ){
                    
                    $dashboard->display();
                });
    }
    /**
     * @param boolean $fullName Indica si muestra el nombre completo para enlazar el hook
     * @return string
     */
    public final function getName( $fullName = FALSE ){
        return $fullName ?
                sprintf('tripman_admin_%s_dashboard',$this->get('name')) :
                $this->get('name','');
    }
    /**
     * @return string
     */
    public final function getTitle(){
        return TripManStringProvider::__( $this->get('title','') );
    }
    /**
     * 
     */
    public final function display( ) {
        
        $path = sprintf(
                '%scomponents/views/dashboards/%s.php',
                MNK__TRIPMAN__DIR, strtolower( $this->get('name') ) );

        if(file_exists($path)){

            require $path;
        }
        else{
            $error = TripManLogProvider::error('Vista de panel inv&aacute;lida '.$this->get('name'));
            
            print $error->getHTML();
        }
    }
    /**
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public final function get($var, $default = null) {
        
        switch( $var ){
            case 'content':
                if( !is_null($this->_callback)){
                    
                    $callback = $this->_callback;

                    $output = $callback();
                    
                    if(is_array( $output) ){
                        
                        return $output;
                    }
                }
                return array();
            default:
                return parent::get($var, $default);
        }
    }
}