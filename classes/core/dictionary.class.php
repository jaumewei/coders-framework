<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Descriptor de tipos de datos
 * 
 * @author Jaume Llopis
 */
abstract class Dictionary {
    
    const DEFAULT_LENGTH = 25;
    
    const TYPE_ID = 'id';
    const TYPE_EMAIL = 'email';
    const TYPE_TELEPHONE = 'tel';
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_LIST = 'list';
    const TYPE_DROPDOWN = 'dropdown';
    const TYPE_OPTION = 'option';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'date-time';
    const TYPE_NUMBER = 'number';
    const TYPE_PRICE = 'price';
    const TYPE_FLOAT = 'float';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_HIDDEN = 'hidden';
    const TYPE_USER = 'user';
    const TYPE_PASSWORD = 'password';
    //campo relacionado, se debe definir desde el método especial defineRelatedField
    const TYPE_RELATED = 'related';
    const TYPE_FILE = 'file';
    
    private $_content = array();
    /**
     * Nombre de la clase por defecto
     * @return STring
     */
    public function __toString() {
        return get_class($this);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getMeta($name, 'value', '' );
    }
    /**
     * Importa directamente los datos sobre el modelo del formulario
     * @param array $data
     * @param string $meta Valor meta a importar
     * @return \TripManDictionary
     */
    protected function importMeta( array $data, $meta ){
        if(strlen($meta)){
            foreach( $this->listFields() as $field ){
                if( isset($data[$field]) ){
                    switch( $this->getFieldType($field) ){
                        case self::TYPE_FLOAT:
                        case self::TYPE_PRICE:
                            $this->setMeta($field, $meta, floatval( $data[$field]) );
                            break;
                        case self::TYPE_ID:
                        case self::TYPE_NUMBER:
                            $this->setMeta($field, $meta, intval( $data[$field]) );
                            break;
                        default:
                            $this->setMeta($field, $meta, $data[$field]);
                            break;
                    }
                }
            }
        }
        return $this;
    }
    /**
     * Agrega un campo a la definición del diccionario
     * 
     * @param String $name
     * @param String $type
     * @param array|NULL $properties
     * @return \TripManDictionary Instancia para chaining
     */
    protected function addField( $name, $type = self::TYPE_TEXT, array $properties = null ){
        if( !isset( $this->_content[$name]) ){
            
            $this->_content[$name] =  self::defineField($name, $type, $properties);
            
        }
        return $this;
    }
    /**
     * Retorna la definición de un campo meta
     * @param string $name
     * @return array|NULL
     */
    protected function getField( $name, $default = null ){
        return isset( $this->_content[$name] ) ? $this->_content[$name] : $default;
    }
    /**
     * Tipo de campo definido
     * @param string $name
     * @return string
     */
    public function getFieldType( $name ){
        return isset($this->_content[$name]) ?
            $this->_content[$name]['type'] :  null;
    }
    /**
     * Retorna la definicióncompleta de un campo del diccionario
     * @param string $name
     * @return array
     */
    public function getFieldMeta( $name ){
        return isset($this->_content[ $name ] ) ?
            $this->_content[ $name ] : array();
    }
    /**
     * Establece el valor de un atributo dentro del set de datos
     * @param string $field
     * @param string $meta
     * @param mixed $value
     * @return \TripManDictionary
     */
    protected final function setMeta( $field, $meta, $value ){
        switch( $meta ){
            case 'name':
            case 'type':
                //no permitir manipular los metas estáticos y identificadores
                break;
            default:
                $this->_content[$field][$meta] = $value;
                break;
        }

        return $this;
    }
    /**
     * Obtiene un valor meta del campo requerido, si no existe, devuelve un valor por defecto
     * @param string $field
     * @param string $meta
     * @param mixed $default
     * @return mixed
     */
    protected function getMeta( $field, $meta, $default = null ){
        return ( isset($this->_content[$field] ) && isset($this->_content[$field][$meta]) ) ?
            $this->_content[$field][$meta] :
            $default;
    }
    /**
     * Lista todos los valores de un campo meta dentro de las definiciones del diccionario
     * @param string $meta
     * @param mixed $default
     * @return array
     */
    protected final function listMetas( $meta , $default = null ){
        $output = array();
        foreach($this->listFields() as $field){
            $output[$field] = $this->getMeta($field, $meta, $default);
        }
        return $output;
    }
    /**
     * Indica si existe un meta en un campo
     * @param string $field
     * @param string $meta
     * @return bool
     */
    protected final function hasMeta( $field, $meta ){
        return $this->hasField($field) && isset($this->_content[$field][$meta]);
    }
    /**
     * Lista los valores predefinidos para cada campo
     * @return array
     */
    protected final function listSources(){
        $output = array();
        foreach( $this->listFields() as $field ){
            $source = $this->getSource($field,false);
            if( $source !== false ){
                $output[] = $source;
            }
        }
        return $output;
    }
    /**
     * Comprueba si  existe una lista de valores predefinidos para el campo indicado
     * @param string $field
     * @return boolean
     */
    protected final function hasSource( $field ){
        return method_exists($this, sprintf('list%sSource',$field));
    }
    /**
     * Carga un listado de valores para un campo, en su defecto, devuelve una lista vacía
     * @param string $field
     * @param boolean $default Valor por defecto
     * @return array|boolean
     */
    protected function getSource( $field , $default = array( ) ){
        $callback = sprintf('list%sSource',$field);
        return method_exists($this, $callback) ?
                $this->$callback() :
                $default;
    }
    /**
     * @return array Lista de campos definidos en el Diccionario
     */
    public final function listFields(){
        return array_keys( $this->_content );
    }
    /**
     * Indica si existe un campo en la definición
     * @param string $field
     * @return bool
     */
    public final function hasField( $field ){
        return isset($this->_content[$field]);
    }
    /**
     * Lista los valores del diccionario
     * @param boolean $mapKeys
     */
    public function listValues( $mapKeys = true ){
        $output = array();
        foreach( $this->listFields() as $field ){
            if( $mapKeys ){
                $output[ $field ] = $this->getValue($field);
            }
            else{
                $output[] = $this->getValue($field);
            }
        }
        return $output;
    }
    /**
     * @param string $field
     * @return array
     */
    public function listOptions( $field ){
        
        $options = sprintf('get%sOptions',$field);
        
        return method_exists($this, $options) ? $this->$options() : array();
    }
    /**
     * Valor de un campo
     * @param string $field
     * @return mixed
     */
    public function getValue( $field ){
        return $this->getMeta($field, 'value', false);
    }
    /**
     * Establece un valor
     * @param string $field
     * @param mixed $value
     * @return \TripManDictionary
     */
    public function setValue( $field , $value ){
        if( $this->hasField($field)){
            switch($this->getFieldType($field)){
                //case self::TYPE_ID:
                //      por definir si procede cambiar ids
                //    break;
                case self::TYPE_FLOAT:
                case self::TYPE_PRICE:
                    $this->setMeta($field, 'value', floatval($value));
                    break;
                case self::TYPE_NUMBER:
                    $this->setMeta($field, 'value', intval($value));
                    break;
                default:
                    $this->setMeta($field, 'value', $value );
                    break;
            }
        }
        
        return $this;
    }
    /**
     * Crea y parametriza un campo de formulario
     * @param string $name Nombre del campo
     * @param string $type Tipo de dato
     * @param array $properties Propiedades extra
     * @return array Definición del dato
     */
    protected static final function defineField( $name, $type = self::TYPE_TEXT , array $properties = array() ){

        $properties['name'] = $name;
        $properties['type'] = $type;
        
        switch( $properties['type'] ){
            case self::TYPE_PASSWORD:
            case self::TYPE_USER:
                //ambos campos siempre serán requeridos en los formularios
                //tanto de registro como de inicio de sesión
                //$properties['required'] = 1;
                break;
            case self::TYPE_CHECKBOX:
                $properties['value'] = isset($properties['value']) ? intval($properties['value']) : 0;
                break;
            case self::TYPE_NUMBER:
                if( !isset($properties['step']) ){
                    $properties['step'] = 1;
                }
                $properties['value'] = isset($properties['value']) ? intval($properties['value']) : 0;
                break;
            case self::TYPE_PRICE:
                if( !isset($properties['step']) ){
                    $properties['step'] = 0.05;
                }
                $properties['value'] = isset($properties['value']) ? floatval($properties['value']) : 0;
                break;
            case self::TYPE_FLOAT:
                if( !isset($properties['step']) ){
                    $properties['step'] = 0.1;
                }
                $properties['value'] = isset($properties['value']) ? floatval($properties['value']) : 0;
                break;
//            case self::TYPE_TEXTAREA:
//                break;
//            case self::TYPE_EMAIL:
//                break;
//            case self::TYPE_DATE:
//                break;
//            case self::TYPE_CHECKBOX:
//                break;
//            case self::TYPE_HIDDEN:
//                break;
//            case self::TYPE_LIST:
//                break;
//            case self::TYPE_TEXT:
//                break;
            default:
                //nada por agregar
                break;
        }
        
        return $properties;
    }
}


