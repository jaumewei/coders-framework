<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class AdminPageRenderer extends Renderer{

    protected function __construct( $parent = NULL ) {
        $this->register( $parent );
    }
    
    private final function register( $parent = NULL ){
        
        //each item is a Page setup class
        add_menu_page(
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapabilities(),
            $this->getName(),
            array($this,'action'),
            $this->getIcon(),
            $this->getPosition() );
    }

    public final function action(){

        //first execute the request capture
        
        //then the page display
        
        return FALSE;
    }
    
    protected function display(){
        
    }

    protected function request(){
        
    }
    
    public function getPageTitle(){
        return __('Page Title','coders_framework');
    }
    public function getMenuTitle(){
        return __('Menu Title','coders_framework');
    }
    public function getName(){
        return __('menu-name','coders_framework');
    }
    public function getCapabilities(){
        return 'administrator';
    }
    public function getIcon(){
        return 'dashicons-grid-view';
    }
    public function getPosition(){
        return 50;
    }
    /**
     * 
     * @param \CodersApp $app
     * @param string $page
     * @return \CODERS\Framework\Views\AdminPageRenderer | boolean
     */
    public static final function create( \CodersApp $app , $page ){
        
        
        return FALSE;
    }
}
