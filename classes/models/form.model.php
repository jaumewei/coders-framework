<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;

/**
 * Modelo de formulario para procesar registros de entrada de datos
 * 
 * Incluye funciones de validación de datos del form e importación directa desde los eventos generados
 * por la entrada de inputs GET y POST
 */
abstract class FormModel extends \CODERS\Framework\Dictionary implements \CODERS\Framework\IModel{
    //control de spam honeypot
    const FIELD_TYPE_ANTISPAM = 'antispam';
    //agregar campo de recuento de totales del precio del form
    const FIELD_TYPE_PRICE_TOTAL = 'price_total';
    
    const FORM_FIELD_ANTISPAM = 'secret_key';
    /**
     * Título/Cabecera/Identificador del formulario
     * @var string
     */
    //private $_formHeader = null;
    /**
     * Propiedades del formulario (no relacionadas directamente con los valores)
     * @var array
     */
    private $_settings = array();

    /**
     * @param array | null $dataSet Set de datos a importar
     */
    protected function __construct( array $dataSet = null ) {
        //agregar siempre antispam por defecto
        $this->addField(self::FORM_FIELD_ANTISPAM,self::FIELD_TYPE_ANTISPAM);
        
        if( !is_null($dataSet)){

            $this->importData($dataSet);
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return get_class($this);
    }
    /**
     * Define un nuevo tipo de dato
     * @param string $name
     * @param string $type
     * @param array $properties
     * @return \CODERS\Framework\Models\FormModel Instancia para chaining
     */
    protected function addField($name, $type = self::FIELD_TYPE_TEXT, array $properties = null) {
        
        switch( $type ){
            case self::FIELD_TYPE_PRICE_TOTAL:
                //el campo de total es estático siempre, no debe modificarse nunca.
                if( !is_null($properties)){
                    //pero ya define un valor por defecto si no se ha aplicado todavía ninguno
                    //$properties['readonly'] = true;
                    if( !isset( $properties['value']) ){
                        $properties['value'] = 0;
                    }
                }
                else{
                    //$properties = array('readonly'=>true);
                    $properties['value'] = 0;
                }
                //este campo siempre es local. Es un recalculo estático de la página, no debe enviarse
                $properties['local'] = true;
                break;
            case self::FIELD_TYPE_ANTISPAM:
                //define el tipo de dato antispam como oculto (controlado por CSS)
                if( !is_null($properties)){
                    $properties['class'] = 'hidden';
                }
                else{
                    $properties = array('class'=>'hidden');
                }
                //este campo siempre es local
                $properties['local'] = true;
                break;
        }
        
        return parent::addField($name, $type, $properties);
    }
    /**
     * Retorna el valor actual de un campo del formulario
     * @param string $filed
     * @return mixed
     */
    public function __get($field) {
        
        //return $this->getValue( $field , '' );
        return $this->get($field, '');
    }
    /**
     * Obtiene un resultado del Modelo
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get( $var, $default = null ){

        if($this->hasField($var)){
            //valores del formulario
            return $this->getValue($var,$default);
        }
        
        if( isset($this->_settings[$var]) ){
            return $this->_settings[$var];
        }
        /*switch( $var ){
            //propiedades del formulario
            case 'form_title':
                return !is_null( $this->_formHeader ) ? $this->_formHeader : $default;
        }*/
        
        //luego métodos de extracción de datos
        $callback = sprintf('get_%s_data',  strtolower($var));
        
        return (method_exists($this, $callback)) ? $this->$callback( ) : $default;
    }
    /**
     * Publica el diccionario de datos del formulario
     * @return array
     */
    public function get_form_fields_data(){
        return $this->listPublicFields();
    }
    /**
     * Devuelve el nombre o etiqueta del campo solicitado
     * @param string $field
     * @return string
     */
    public final function getLabel($field){
        return $this->getMeta($field, 'label' ,'');
    }
    /**
     * Devuelve un texto de detalle o consejo para mostrar en el formulario
     * @param string $field
     * @return string
     */
    public final function getAdvice( $field ){
        return $this->getMeta($field, 'advice', '' );
    }
    /**
     * Retorna el error de validación a mostrar en el formulario
     * @param string $field
     * @return string | ERROR
     */
    public final function getError( $field ){
        return $this->getMeta($field, 'error');
    }
    /**
     * Obtiene el marcador del campo indicado
     * @param string $field
     * @return string
     */
    public final function getPlaceholder( $field ){
        return $this->getMeta($field, 'placeholder', $this->getLabel($field));
    }
    /**
     * Retorna la clase del campo solicitado, vacía si no se ha definido
     * @param string $field
     * @return string
     */
    public final function getClass( $field ){
        return $this->getMeta($field, 'class','');
    }
    /**
     * Indica si un campo del formulario es requerido a rellenar
     * @param string $field
     * @return boolean
     */
    public final function isRequired($field){
        return $this->getMeta($field, 'required',false);
    }
    /**
     * Determina si el valod de un campo ha sido actualizado
     * @param string $field
     * @return bool
     */
    /*public final function isUpdated( $field ){
        return $this->getMeta($field, 'updated', false);
    }*/
    /**
     * Valor del campo de form seleccionado
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function getValue( $field, $default = null ){
        return $this->getMeta($field, 'value', $default );
    }
    /**
     * Retorna una lista de opciones
     * @param string $field
     * @return array
     */
    public function getSource( $field ){
        return $this->getMeta($field, 'source', array() );
    }
    /**
     * Indica si existe un origen de datos definido para el campo
     * @param string $field
     * @return bool
     */
    public final function hasSource( $field ){
        return $this->hasMeta($field, 'source');
    }
    /**
     * Gestiona la comprobación de un campo para verificar si es exportable
     * fuera del formulario.
     * 
     * @param type $field
     * @return boolean
     */
    private final function isExportable( $field ){
        
        if( $this->getFieldType($field) === self::FIELD_TYPE_ANTISPAM ){
            //este campo sólo es de control de validación del form
            return false;
        }

        if( $this->getFieldType($field) === self::FIELD_TYPE_PRICE_TOTAL ){
            //este campo es un sumatorio de precios, no es un campo exportable
            return false;
        }
        
        if( $this->getMeta($field, 'readonly',false)){
            //no interesa exportar valores de solo lectura
            return false;
        }
        
        if( $this->getMeta($field, 'local',false)){
            //los valores locales solo deben utilizarse explícitamente con este formulario
            return false;
        }
        
        return true;
    }
    /**
     * Establece un valor en el form.
     * Dependiendo del nombre de la propiedad, puede establecerse como atributo 
     * propio del formulario.
     * 
     * @param string $field
     * @param mixed $value
     * @return \CODERS\Framework\Models\FormModel Chaining
     */
    public function setValue($field, $value) {
        
        if( $this->hasField($field)){
            switch( $this->getFieldType($field)){
                case self::FIELD_TYPE_ANTISPAM:
                    $this->setMeta($field, 'value', $value);
                    break;
                case parent::FIELD_TYPE_CHECKBOX:
                    //establecer un booleano directamente
                    $this->setMeta($field, 'value', intval($value) > 0 );
                    //$this->setMeta($field, 'updated', $update);
                    break;
                case parent::FIELD_TYPE_NUMBER:
                    $this->setMeta($field, 'value', intval($value) );
                    //$this->setMeta($field, 'updated', $update);
                    break;
                case parent::FIELD_TYPE_TELEPHONE:
                    //comprobar si esto es correcto (+34 00 00 000 000)
                    //elimina todos los carácteres a excepción de los dígitos y el símbolo +
                    $this->setMeta($field, 'value', preg_replace('/[^\d+]/', '', $value));
                    //$this->setMeta($field, 'updated', $update);
                    break;
                case parent::FIELD_TYPE_FLOAT:
                case parent::FIELD_TYPE_PRICE:
                    $this->setMeta($field, 'value', floatval($value) );
                    //$this->setMeta($field, 'updated', $update);
                    break;
                default:
                    $this->setMeta($field, 'value', $value);
                    //$this->setMeta($field, 'updated', $update);
                    break;
            }
        }
        elseif( is_string($value) ||  is_numeric($value) || is_bool($value) ){
            //solo admitir valores primitivos para establecer propiedades
            switch( $field ){
                case 'form_title':
                    $this->_settings[$field] = $value;
                    //$this->_formHeader = $value;
                    break;
            }
        }
        
        return $this;
    }
    /**
     * Define un error en el formulario
     * @param string $field
     * @param string $message
     * @param mixed $args Parámetro o parámetros a intercalar en el texto (array o cadena)
     * @return \CODERS\Framework\Models\FormModel
     */
    public function setError( $field, $message, $args = null ){
        $this->setMeta(
                $field, 'error',
                TripManStringProvider::__( $message , $args ) );
        return $this;
    }
    /**
     * Lista los campos que se  mostrarám en el layout de la vista de formulario
     * @return array
     */
    public function getLayout(){
        return $this->listFields();
    }
    /**
     * Importa los valores existentes de un diccionario de datos externo que coincidan con la definición local
     * @param TripManDictionary $source
     * @return int Recuento de valores importados
     */
    protected final function mergeValues( TripManDictionary $source, $updated = true ){
        $counter = 0;
        foreach( $this->listFields() as $field ){
            if( $source->hasField($field) && $source->hasMeta($field, 'value')){
                $this->setValue($field, $source->getMeta($field, 'value' ), $updated );
                $counter++;
            }
        }
        return $counter;
    }
    /**
     * Crea un form de datos
     * @param string $app
     * @param string $model
     * @param array $data
     * @return \CODERS\Framework\Models\FormModel|boolean
     */
    public static final function create( \CodersApp $app , $model , array $data = array( ) ){
        
        //$instance = \CodersApp::instance($app);
        
        if( $app !== FALSE ){

            $path = sprintf('%s/models/%s.form.php', $app->appPath(), $model);

            $class = sprintf('\CODERS\Framework\Models\%sForm', \CodersApp::classify( $model ) );

            if( file_exists( $path ) ){

                require_once $path;

                if( class_exists($class) && is_subclass_of($class, self::class ,TRUE)){

                    return new $class( $data );
                }
            }
        }

        return FALSE;
    }
    /**
     * Importa directamente los datos sobre el modelo del formulario
     * @param array $formData
     * @param boolean $update Marca los datos importados como valores actualizados
     * @return \CODERS\Framework\Models\FormModel
     */
    public function importData( array $formData, $update = false ){
        foreach( $this->listFields() as $field ){
            if( isset($formData[$field]) ){
                $this->setValue($field, $formData[$field],$update);
            }
        }
        return $this;
    }
    /**
     * Valida los datos del formulario retornando TRUE si el form ha superado la validación, FALSE si hay errores que revisar
     * @return boolean Estado de la validación
     * 
     * Si se causa una excepción el resto de probables errores no se registrará en el formulario y el método
     * será automáticamente interrumpido
     */
    public function validateFormData( ){
        
        $success = true;
        
        foreach( $this->listFields() as $field ){
            switch( $this->getFieldType($field)){
                case self::FIELD_TYPE_ANTISPAM:
                    $value = $this->getValue($field,'');
                    if( strlen($value) > 0 ){
                        //si hay contenido en este campo, no admitir el registro
                        $success = false;
                        //capturar a los malos
                        TripManLogProvider::system(
                                TripManStringProvider::__(
                                        'Se ha capturado un env&iacute;o malintencionado',
                                        TripManRequestProvider::requestClientIP()),
                                $this->getName());
                    }
                    break;
                case self::FIELD_TYPE_NUMBER:
                case self::FIELD_TYPE_FLOAT:
                case self::FIELD_TYPE_PRICE:
                    $value = $this->getValue($field,0);
                    $minimum = $this->getMeta($field, 'minimum',false );
                    $maximum = $this->getMeta($field, 'maximum', false );
                    if( $minimum !== false && $value < $minimum ){
                        $this->setError($field, 'El valor no alcanza el m&iacute;nimo requerido');
                        $success = false;
                    }
                    if( $maximum !== false && $value > $maximum ){
                        $this->setError($field, 'El valor supera el m&aacute;ximo admitido');
                        $success = false;
                    }
                    break;
                case self::FIELD_TYPE_DROPDOWN:
                    $value = $this->getValue($field,'');
                    if( $this->isRequired($field) && strlen($value) === 0 ) {
                        $this->setError($field, 'Debe selecionarse un valor' );
                        $success = false;
                    }
                    break;
                case self::FIELD_TYPE_EMAIL:
                    $value = $this->getValue($field,'');
                    if( strlen($value)){
                        //validar email
                        $at = strrpos($value, '@');
                        $dot = strrpos($value, '.');
                        //hay una @ en alguna posición superior a 0 y luego hay un punto para definir el dominio
                        if( $at < 1 || $at > $dot ){
                            $this->setError($field, 'La direcci&oacute;n de correo no es v&aacute;lida' );
                            $success = false;
                        }
                    }
                    elseif($this->isRequired($field)){
                        $this->setError($field, 'Debe indicarse un email' );
                        $success = false;
                    }
                    break;
                default:
                    $value = $this->getValue($field,'');
                    if( $this->isRequired($field) && strlen($value) === 0 ){
                        $this->setError($field, 'No puede estar vac&iacute;o' );
                        $success = false;
                    }
                    break;
            }
        }
        
        return $success;
    }
    /**
     * @return array Lista los errores del formulario, si no hay, retorna una lista vacía
     */
    public final function listErrors(){
        $error_list = array();
        foreach($this->getDictionary() as $field => $definition ){
            if( isset( $definition['error'] ) ){
                $error_list[$field] = $definition['error'];
            }
        }
        return $error_list;
    }
    /**
     * Lista los campos publicados en el formulario.
     * Los que no deban ser publicados deben definirse en el constructor con
     * el atributo 'public' = false
     * @return array
     */
    public function listPublicFields(){
        $public = array();
        foreach( $this->listFields() as $field ){
            if( $this->getMeta($field, 'public',true) ){
                $public[] = $field;
            }
        }
        return $public;
    }
    /**
     * Lista los campos marcados para exportar al modelo de destino.
     * Los campos que no deban ser exportados en el diccionario, deberán ser marcados con el 
     * atributo 'local' = true a fin de ser ignorados por el modelo de destino
     * @return array
     */
    public function listOtuputFields(){
        $output = array();
        foreach( $this->listFields() as $field ){
            if( !$this->getMeta( $field, 'local', false ) ){
                $output[] = $field;
            }
        }
        return $output;
    }
    /**
     * Restablece la marca de actualizado en los campos que han sido recientemente importados
     * @return \CODERS\Framework\Models\FormModel
     */
    /*public final function commitUpdated(){
        foreach($this->listFields() as $field ){
            if( $this->getMeta($field, 'updated',false)){
                $this->setMeta($field, 'updated', false);
            }
        }
        return $this;
    }*/
    /**
     * Vuelca todos los valores del formulario existentes en el diccionario
     * proveido si no están marcados como 'local'
     * 
     * @param TripManDictionary $model
     */
    public function fillValues( TripManDictionary $model ){
        
        foreach( $this->listFields() as $field ){
            if( $model->hasField($field) && !$this->getMeta($field, 'local',false) ){
                $model->setMeta($field, 'value', $this->getValue($field));
            }
        }
    }
    /**
     * Array asociativo de todos los valores del formulario.
     * Si se provee la lista de campos a mostrar, se exportan los que coincidan
     * con la definición del filtro.
     * @param array $filter Filtros
     * @return array
     */
    public function listValues( array $filter = null ){
        $values = array();
        if( !is_null($filter)){
            foreach( $filter as $field ){
                if( $this->hasField($field) && $this->isExportable($field)){
                    $values[$field] = $this->getValue($field);
                }
            }
        }
        else{
            foreach( $this->listFields() as $field ){
                if( $this->isExportable($field)){
                    $values[$field] = $this->getValue($field);
                }
            }
        }
        return $values;
    }
    /**
     * @return string Nombre del Formulario
     */
    public function getName(){
        $class = get_class($this);
        $prefix_length = strlen(TripManager::PLUGIN_NAME);
        $suffix_length = strlen($class) - strrpos($class, 'FormModel');
        //TripMan[NOMBRE]FormModel
        return substr( strtolower( $class ), $prefix_length, $suffix_length );
    }
}