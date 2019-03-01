<?php defined('ABSPATH') or die;
/**
 * Modelo para gestionar solicitudes de cliente.
 * Útiles en los contextos de error.
 */
final class TripManErrorModel implements TripManIModel{
    
    private $_data;
    private $_conext;
    private $_request;
    private $_action;
    private $_callBack;
    /**
     * @param \TripManRequestProvider $request
     */
    public final function __construct( \TripManRequestProvider $request ) {
        
        $this->_request = strval($request);
        $this->_conext = $request->getContext();
        $this->_action = $request->getAction();
        $this->_data = $request->getData();
        
        $this->_callBack = $request->get(
                TripManRequestProvider::EVENT_DATA_CALLBACK,
                is_admin() ? 'main.default' : '' );
    }
    /**
     * Obtiene los valores del evento
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get($var, $default = null) {
        switch($var){
            case 'request':
                return $this->_request;
            case 'last_error':
                return TripManLogProvider::getLastError()->getMessage();
            case 'logs':
                return TripManLogProvider::listLogs();
            case 'data':
                return $this->_data;
            case TripManRequestProvider::EVENT_DATA_CALLBACK:
                if(is_admin()){
                    return TripManRenderer::renderAdminAction( $this->_callBack,
                        TripManStringProvider::__('Volver'),
                        null,false,'button button-primary');
                }
                elseif(strlen($this->_callBack) ){
                    return TripManRenderer::renderAction( $this->_callBack,
                        TripManStringProvider::__('Volver'),
                        null,false,'button button-primary');
                }
                else{
                    return TripManRenderer::renderLink( get_site_url(),
                        TripManStringProvider::__('Volver'),
                        'button button-primary');
                }
            case TripManRequestProvider::EVENT_DATA_COMMAND:
                return $this->_action;
            case TripManRequestProvider::EVENT_DATA_CONTEXT:
                return $this->_conext;
        }
        
        return $default;
    }
}