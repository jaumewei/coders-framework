<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

/**
 * 
 */
 class DocumentRender extends Renderer{
    /**
     * @var URL
     */
    const GOOGLE_FONTS_URL = 'https://fonts.googleapis.com/css?family';

    /**
     * @var array Scripts del componente
     */
    private $_scripts = array();
    /**
     * @var array Estilos del componente
     */
    private $_styles = array();
    /**
     * @var array Links del componente
     */
    private $_links = array();
    /**
     * @var array Metas adicionales del componente
     */
    private $_metas = array();
    /**
     * Layout and context to display the view
     * @var string
     */
    private $_layout,$_context,$_title = '';

    protected function __construct(\CodersApp $app) {
        
        parent::__construct($app);

        $this->__registerAssets( );
    }
    /**
     * Inicializa las dependencias del componente
     * @param string $hook
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function __registerAssets( $hook = 'wp_enqueue_scripts' ){

        $metas = $this->_metas;
        $links = $this->_links;
        $styles = $this->_styles;
        $scripts = $this->_scripts;
        //public metas and linnks
        if (!is_admin() && class_exists('\CODERS\Framework\Views\HTML')) {

            add_action($hook, function() use( $metas, $links ) {

                foreach ($metas as $meta_id => $atts) {

                    print \CODERS\Framework\Views\HTML::meta($atts, $meta_id);
                }

                foreach ($links as $link_id => $atts) {

                    print \CODERS\Framework\Views\HTML::link(
                                    $atts['href'], $atts['type'], array_merge($atts, array('id' => $link_id)));
                }
            });
        }
        //styles
        add_action($hook, function() use( $styles ) {

            foreach ($styles as $style_id => $url) {

                wp_enqueue_style($style_id, $url);
            }
        });
        //Scripts
        add_action($hook, function() use( $scripts ) {

            foreach ($scripts as $script_id => $content) {

                if (isset($content['deps'])) {
                    wp_enqueue_script($script_id, $content['url'], $content['deps'], false, TRUE);
                } else {
                    wp_enqueue_script(
                            $script_id, $content['url'], array(), false, TRUE);
                }
            }
        });

        return $this;
    }
    /**
     * Registra un meta en la cabecera
     * @param string $meta_id
     * @param array $attributes
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerMeta( $meta_id , array $attributes ){
        
        if( !isset( $this->_metas[ $meta_id ] ) ){
            $this->_metas[$meta_id] = $attributes;
        }
        
        return $this;
    }
    /**
     * Registra un link en la cabecera
     * @param string $link_id
     * @param string $link_url
     * @param array $attributes
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerLink( $link_id , $link_url , array $attributes = null ){
        
        if( !isset( $this->_links[ $link_id ] ) ){
            
            if(is_null($attributes)){
                $attributes[ 'href' ] = $link_url;
            }
            else{
                $attributes[ 'href' ] = $link_url;
            }
            
            $this->_links[$link_id] = $attributes;
        }
        
        return $this;
    }
    /**
     * Registra un estilo
     * @param string $style_id
     * @param string $style_url
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerStyle( $style_id , $style_url ){
        
        if(strlen($style_url)){

            if( !isset( $this->_styles[ $style_id ] ) ){

                if( !self::containsUrl($style_url) ){

                    $style_url = $this->getLocalStyleUrl($style_url);
                }

                $this->_styles[$style_id] = $style_url;
            }
        }
        else{
            /**
             * @todo WARNING!!!!
             * no se ha encontrado el recurso CSS definido!!!! anotar en algún log
             */
        }
        
        return $this;
    }
    /**
     * Registra un script
     * @param string $script_id
     * @param string $script_url
     * @param mixed $deps Dependencias del script
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerScript( $script_id , $script_url , $deps = null ){
        
        if( !isset( $this->_scripts[ $script_id ] ) ){
            
            if( !self::containsUrl($script_url) ){
                
                $script_url = $this->getLocalScriptUrl($script_url);
            }

            $this->_scripts[$script_id] = array( 'url' => $script_url );
            
            if( !is_null($deps)){
                $this->_scripts[$script_id]['deps'] = !is_array($deps) ? explode( ',', $deps ) : $deps;
            }
        }
        
        return $this;
    }
    /**
     * Registra una fuente de Google Fonts
     * @param string $font
     * @param mixed $weight
     */
    protected final function registerGoogleFont( $font , $weight = null ){
        
        $font_id = 'font-' . preg_replace( '/ /' , '-' , strtolower($font));

        $font_url = self::GOOGLE_FONTS_URL . '=' . $font ;
        
        if( !is_null($weight)){

            if( !is_array($weight)){
 
                $weight = explode( ',' , $weight );
            }
            
            $font_url .= ':' . implode(',', $weight);
        }
        
        return $this->registerStyle( $font_id, $font_url );
    }
    /**
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderHeader(){
        
        wp_head();
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderFooter(){
        
        wp_footer();
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderContent(){
        
        $layout = $this->getLayout();

        if(file_exists($layout)){

            require $layout;
        }
        else{
            printf('<h1> LAYOUT %s NOT FOUND </h1>',$layout);
            printf('<!-- LAYOUT %s NOT FOUND -->',$layout);
        }

        return $this;
    }
    /**
     * @return URL Url de desconexión de la sesión de WP
     */
    public static final function renderLogOut(){
        return wp_logout_url( site_url() );
    }
    /**
     * Retorna la ruta URI del layout de la vista seleccionada o devuelve nulo si no existe
     * @param string $layout
     * @return string | boolean
     */
    protected final function getLayout( ){
        
        $app = \CodersApp::current();
        
        if( $app !== FALSE ){
            return sprintf('%s/%s/views/layouts/%s.layout.php',
                    $app->appPath(),
                    is_admin() ? 'admin' : 'public',
                    $this->_layout);
        }
        
        return FALSE;
    }
    /**
     * @return string
     */
    protected function getContext(){ return $this->_context; }
    /**
     * @return string
     */
    protected function getTitle(){ return $this->_title; }
    /**
     * @param string $layout
     * @param string $context
     * @param string $title
     * @return \CODERS\Framework\Views\DocumentRender
     */
    public function setLayout( $layout = 'default' , $context = 'main' , $title = '' ){
        
        $this->_layout = $layout;
        
        $this->_context = $context;
        
        $this->_title = $title;
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    public function display() {
        //HEADER & OPEN DOCUMENT
        return $this->renderHeader()
                ->renderContent()
                ->renderFooter();
    }
}



