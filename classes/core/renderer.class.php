<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Definición de la vista
 * 
 * @todo renderDictionaryData() Pendiente de resolver notificación de errores del renderizado de form de datos
 */
abstract class Renderer extends Component{    
    /**
     * @var TripManIModel Modelo de datos 
     */
    private $_model = null;
    /**
     * @var array Definicion de clases del contenedor del plugin
     */
    private $_classDef = array( );
    /**
     * 
     */
    protected function __construct() {
        
    }
    /**
     * Muestra un valor o componente visual
     * @param type $element
     */
    public function __render( $element ){
        
        print $element;

    }
    /**
     * @param TripManRenderer $R
     * @return String nombre del contexto de la vista
     */
    public function getName(){
        
        $class = strtolower( get_class( $this ) );
        //$suffix_length = strlen($class) - strrpos($class, 'View');
        //TripManManagerView
        return substr( $class , 7, strlen($class) - 4 - 7 );
    }
    /**
     * Establece una propiedad en la vista
     * @param string $var
     * @param mixed $val
     * @return \TripManRenderer
     */
    public function set($var, $val) {
        parent::set($var, $val);
        return $this;
    }
    /**
     * Establece el título de la vista
     * @param string $title
     * @param bool $parsestring Define si se procesa la cadena con el gestor de idiomas
     * @return \TripManRenderer
     */
    public final function setTitle( $title, $parsestring = false ){
        $this->set('page_title', $parsestring ?
            TripManStringProvider::__($title) :
                $title);
        return $this;
    }
    /**
     * @param \TripManIModel $model
     * @param string|null $context Define el contexto de la vista
     * @return \TripManRenderer Instancia para chaining
     */
    public function setModel( \TripManIModel $model, $context = null ){
        $this->_model = $model;
        $this->set(
                TripManRequestProvider::EVENT_DATA_CONTEXT,
                !is_null($context) ? $context : $this->getName());
        return $this;
    }
    /**
     * Establece el contexto de la vista
     * @param string $context
     * @return \TripManRenderer
     */
    public function setContext( $context ){
        $this->set(TripManRequestProvider::EVENT_DATA_CONTEXT, $context);
        return $this;
    }
    /**
     * @return TripManIModel Modelo de datos
     */
    protected function getModel(){ return $this->_model; }
    /**
     * Registra una clase CSS en el ámbito o contexto indicado
     * @param string $context
     * @param string $class
     */
    protected final function registerClass( $context, $class ){
        if( !isset($this->_classDef[$context]) ){
            $this->_classDef[$context] = array($class);
        }
        else{
            foreach( $this->_classDef[$context] as $cls ){
                if( $cls === $class ){
                    return false;
                }
            }
            $this->_classDef[$context][] = $class;
        }
        return true;
    }
    /**
     * Obtiene una propiedad de la vista
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get($var, $default = null) {
        switch( $var ){
            default:
                return parent::get($var, $default);
        }
    }
    /**
     * Retorna las clases del contexto solicitado
     * @param String $context
     * @return String
     */
    public final function getClass( $context ){
        return isset( $this->_classDef[$context] ) ?
            implode( ' ',$this->_classDef[$context]) : '';
    }
    /**
     * Retorna la ruta URI del layout de la vista seleccionada o devuelve nulo si no existe
     * @param string $view
     * @return URI path
     */
    protected final function getLayout( $view ){
        
        $local = sprintf(
                '%smodules/%s/views/layouts/%s.php',
                MNK__TRIPMAN__DIR,
                $this->getName(),
                strtolower( $view ) );
        
        $base = sprintf(
                '%scomponents/views/layouts/%s.php',
                MNK__TRIPMAN__DIR,
                strtolower( $view ));
        
        return file_exists($local) ? $local : $base;
    }
    /**
     * @param String $template Nombre de la plantilla o fragmento html a cargar
     */
    protected function displayTemplate( $template ){

        $local_path = sprintf(
                '%smodules/%s/views/templates/%s.php',
                MNK__TRIPMAN__DIR,
                $this->getName(),
                strtolower($template));
        $path = sprintf(
                '%scomponents/views/templates/%s.php',
                MNK__TRIPMAN__DIR, strtolower( $template ) );

        if(file_exists($local_path)){
            require $local_path;
        }
        elseif(file_exists($path)){
            require $path;
        }
        else{
            $error = TripManLogProvider::error('Vista inv&aacute;lida '.$template);
            
            echo $error->getHTML();
        }
    }   
    /**
     * Obtener conjunto de datos
     * @param string $var
     * @param mixed $default Argumentos opcionales que podrían ser requeridos por el método callback
     * @return mixed
     */
    public function get_data( $var, $default = null ){

        return !is_null( $this->_model) ?
            $this->_model->get( $var, $default ) :
            $default;
    }
    /**
     * Muestra el contenido seleccionado
     * @param string $content
     * @param mixed $data
     * @return HTML
     */
    public function display( $content, $data ){
        
        $display = sprintf('display_%s_content', strtolower( $content ) );
        
        return method_exists($this, $display) ?
                $this->$display( $data ) :
                sprintf('<span class="display-error">%s <b>%s</b></span>',
                    TripManStringProvider::__('No se ha encontrado el contenido'),
                    $content);
    }
    /**
     * Muestra un precio
     * @param float $price
     * @return HTML
     */
    protected function display_price_content( $price, $name = null ){
        
        $currency = TripManager::createModel('Currency');
        
        $id = !is_null($name) ? sprintf('id="id_%s"',$name) : '';
        
        return sprintf('<span %s class="price">%s %s</span>',$id, $price,$currency->get('coin'));
    }
    /**
     * Renderiza la fecha con formato presentable
     * @param string $date
     * @return HTML
     */
    protected function display_date_content( $date ){
        
        $dateFormat = TripManStringProvider::displayDate($date);
        
        return sprintf('<span class="date">%s</span>', $dateFormat);
    }
    /**
     * Devuelve la etiqueta de un campo, ver si se ha resuelto en algún otro lugar del modelo
     * @param string $field
     * @return string
     */
    protected function display_label_content( $field ){
        
        if(!is_null($this->getModel())){
            //return 'no hay etiqueta de momento';
        }
        
        return TripManStringProvider::__( $field );
    }
    /**
     * Muestra el elnacae de un email
     * @param string $email
     * @return HTML
     */
    protected function display_email_content( $email ){
        return self::renderLink(sprintf('mailto:%s',$email),$email, 'icon-email');
    }
    /**
     * Muestra el enlace a un teléfono
     * @param string $telephone
     * @return HTML
     */
    protected function display_telephone_content( $telephone ){
        return self::renderLink(sprintf('tel:%s',$telephone),$telephone, 'icon-telephone');
    }
    /**
     * Genera el input de un formulario utilizando la descripción meta de
     * $field en el modelo asociado
     * 
     * @param string $field
     * @return HTML
     */
    protected function display_form_input_content( $field ){
        
        $html = '';
        
        $form = $this->getModel();
        
        if( !is_null($form)){
            $class = $form->getClass($field);
            $type = $form->getFieldType($field);
            $error = $form->getError($field);
            $placeholder = $form->getPlaceholder($field);
            
            if( $form->isReadOnly( $field ) ){
                $html .= self::renderHidden( $field, $form->getValue($field,''));
                $html .= self::renderLabel( $field, $form->getLabel($field));
                switch( $type ){
                    case TripManDictionary::FIELD_TYPE_PRICE:
                        print $this->display_price_content($form->getValue($field,''));
                        break;
                    default:
                        printf('<span id="id_%s" class="field-value">%s</span>',
                                $field, $form->getValue($field, ''));
                        break;
                }
            }
            elseif( $type === TripManDictionary::FIELD_TYPE_ID ){
                if(  $form->getValue($field,false) !== false ){
                    $html .= self::renderHidden($field, $form->getValue($field));
                }
            }
            elseif( $form->hasSource($field ) ){
                $html .= self::renderLabel($field,$form->getLabel($field));
                //en el caso de que posea un origen de datos, mostrar como lista desplegable
                $html .= self::renderDropDown($field,
                        $form->getSource($field),
                        $form->getValue($field),'',
                        $placeholder);
            }
            else{
                switch( $type ){
                    case TripManDictionary::FIELD_TYPE_ID:
                        break;
                    case TripManFormModel::FIELD_TYPE_ANTISPAM:
                        $html .= self::renderText( $field,
                                $form->getValue($field, ''),
                                'hidden','');
                        break;
                    case TripManDictionary::FIELD_TYPE_CHECKBOX:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderCheckBox( $field,
                                $form->getValue($field, false),
                                $form->getClass($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_DROPDOWN:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderDropDown( $field,
                                $form->getSource($field),
                                $form->getValue($field, null),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_LIST:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderDropDown( $field,
                                $form->getSource($field),
                                $form->getValue($field, null),
                                $form->getClass($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_PRICE:
                    case TripManDictionary::FIELD_TYPE_FLOAT:
                    case TripManDictionary::FIELD_TYPE_NUMBER:
                        $html .= self::renderLabel($field,$form->getLabel($field));

                        $meta = $form->getFieldMeta($field);
                        $min = isset($meta['minimum']) ? $meta['minimum'] : 0;
                        $max = isset($meta['maximum']) ? $meta['maximum'] : 0;
                        $class = isset($meta['class']) ? 'field-value '.$meta['class'] : 'field-value';

                        $html .= self::renderNumber( $field,
                                $form->getValue($field, 0),
                                $min,$max, $class );
                        break;
                    case TripManDictionary::FIELD_TYPE_DATE:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderDate( $field,
                                $form->getValue($field, ''),
                                'field-value '.$form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_DATETIME:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderDate( $field,
                                $form->getValue($field, ''),
                                'field-value '.$form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_EMAIL:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderEmail( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_TEXTAREA:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderTextArea( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManFormModel::FIELD_TYPE_PRICE_TOTAL:
                        //precio total del formulario público
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= $this->display_price_content($form->getValue($field,0),$field);
                        break;
                    case TripManDictionary::FIELD_TYPE_FILE:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderFileUpload( $field );
                        break;
                    default:
                        $html .= self::renderLabel($field,$form->getLabel($field));
                        $html .= self::renderText( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                }
                if( !is_null($error)){
                    //muestra un error de validación en el form
                    $html .= sprintf('<span class="form-error %s">%s</span>',$type, $error);
                }
            }
        }
        
        return $html;
    }
    /**
     * @param string $field
     */
    protected function renderFormField( $field ){
        
        $form = $this->getModel();
        
        if( !is_null($form)){
            $class = $form->getClass($field);
            $type = $form->getFieldType($field);
            $error = $form->getError($field);
            $advice = $form->getAdvice( $field );
            
            if( $form->isReadOnly($field)){
                print self::renderHidden($field, $form->getValue($field,''), $field );
                print self::renderLabel($field,$form->getLabel($field));
                switch( $type ){
                    case TripManDictionary::FIELD_TYPE_PRICE:
                        print $this->display_price_content( $form->getValue($field,'') );
                        break;
                    default:
                        printf('<span id="id_%s" class="field-value">%s</span>',
                                $field, $form->getValue($field, ''));
                        break;
                }
            }
            elseif( $type === TripManDictionary::FIELD_TYPE_ID ){
                if(  $form->getValue($field,false) !== false ){
                    print self::renderHidden($field, $form->getValue($field));
                }
            }
            elseif( $form->hasSource($field ) ){
                print self::renderLabel($field,$form->getLabel($field));
                //en el caso de que posea un origen de datos, mostrar como lista desplegable
                print self::renderDropDown($field,
                        $form->getSource($field),
                        $form->getValue($field));
            }
            else{
                switch( $type ){
                    case TripManDictionary::FIELD_TYPE_ID:
                        break;
                    case TripManFormModel::FIELD_TYPE_ANTISPAM:
                        print self::renderText( $field,
                                $form->getValue($field, ''),
                                'hidden','');
                        break;
                    case TripManDictionary::FIELD_TYPE_CHECKBOX:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderCheckBox( $field,
                                $form->getValue($field, false),
                                $form->getClass($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_DROPDOWN:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderDropDown( $field,
                                $form->getSource($field),
                                $form->getValue($field, null),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_LIST:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderDropDown( $field,
                                $form->getSource($field),
                                $form->getValue($field, null),
                                $form->getClass($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_PRICE:
                    case TripManDictionary::FIELD_TYPE_FLOAT:
                    case TripManDictionary::FIELD_TYPE_NUMBER:
                        print self::renderLabel($field,$form->getLabel($field));

                        $meta = $form->getFieldMeta($field);
                        $min = isset($meta['minimum']) ? $meta['minimum'] : 0;
                        $max = isset($meta['maximum']) ? $meta['maximum'] : 0;
                        $class = isset($meta['class']) ? $meta['class'] : '';

                        print self::renderNumber( $field,
                                $form->getValue($field, 0),
                                $min,$max, $class );
                        break;
                    case TripManDictionary::FIELD_TYPE_DATE:
                        print self::renderLabel($field,$form->getLabel($field));
                        /*print self::renderDate( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));*/
                        print self::renderDatePicker($field,
                                $form->getValue($field),
                                $form->getMeta($field, 'minimum'),
                                $form->getMeta($field, 'maximum'),
                                $form->getMeta($field, 'excluded'));
                                //$form->getMeta($field, 'available'));
                        break;
                    case TripManDictionary::FIELD_TYPE_DATETIME:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderDate( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_EMAIL:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderEmail( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManDictionary::FIELD_TYPE_TEXTAREA:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderTextArea( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                    case TripManFormModel::FIELD_TYPE_PRICE_TOTAL:
                        //precio total del formulario público
                        print self::renderLabel($field,$form->getLabel($field));
                        print $this->display_price_content( $form->getValue($field,0),$field );
                        break;
                    case TripManDictionary::FIELD_TYPE_FILE:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderFileUpload( $field );
                        break;
                    default:
                        print self::renderLabel($field,$form->getLabel($field));
                        print self::renderText( $field,
                                $form->getValue($field, ''),
                                $form->getClass($field),
                                $form->getPlaceholder($field));
                        break;
                }
                if(strlen($advice)){
                    printf('<small class="advice">%s</small>',$advice);
                }
                if( !is_null($error)){
                    //muestra un error de validación en el form
                    printf('<span class="form-error %s">%s</span>',$type, $error);
                }
            }
        }
    }
    /**
     * Renderiza una vista
     */
    public function render( $layout ){
        
        $path = $this->getLayout($layout);

        printf('<div class="tripman-content %s">',$layout );
        
        /*$title = $this->get('page_title','');
        
        if(strlen($title) ){
            printf('<h1 class="page-title">%s</h1>',$title );
        }*/
        
        //mostrar las notificaciones si la vista está preparada
        if($this->get('display_notifier',true)){
            if( is_admin() ){
                $this->displayTemplate('notify');
            }
            else{
                print self::renderMessages();
            }
        }
        
        if (file_exists($path)) {

            $this->registerClass('view', 'tripman-'. $this->getName() );

            $this->registerClass('view', strtolower( $layout) );


            require $path;
        }
        else{
            //si la vista es error, las notificaciones se muestran dentro del propio contexto
            TripManLogProvider::error( 
                sprintf('%s <b>%s</b>',
                        TripManStringProvider::__('Vista inv&aacute;lida'),$path));

            $this->displayTemplate('error');
        }

        print '</div>';
    }
    /**
     * @param string $profile
     * @return \TripManRenderer|null
     */
    public static final function createRender($profile) {
        
        if ( $profile !== TripManager::PROFILE_INVALID ) {

            $class = sprintf('TripMan%sView', $profile);

            $path = sprintf('%smodules/%s/views/%s.view.php',
                    MNK__TRIPMAN__DIR,
                    strtolower($profile),
                    strtolower($profile) );
            
            if (file_exists($path)) {

                require_once( $path );

                if (class_exists($class) && is_subclass_of($class, 'TripManRenderer',true) ) {
                    return new $class();
                }
            }
        }
        return null;
    }
    /**
     * Renderiza un campo de formulario
     * 
     * @param array $fieldDef
     * @param type $class
     */
    public static final function renderFieldInput( array $fieldDef, $class = '' ){
        if( isset($fieldDef['name']) && isset($fieldDef['type'])){
            
            $value = isset($fieldDef['value']) ? $fieldDef['value'] : null;
            
            $cls = is_array($class) ? implode(' ',$class) : $class;
            
            if(isset($fieldDef['class'])){
                $cls .= (is_array($fieldDef['class'])) ?
                        ' '.implode(' ',$fieldDef['class']) :
                        ' '.$fieldDef['class'];
            }
            
            switch($fieldDef['type']){
                case TripManDictionary::FIELD_TYPE_NUMBER:
                    $maximum = isset($fieldDef['maximum']) ? $fieldDef['maximum'] : 0;
                    
                    $minimum = isset($fieldDef['minimum']) ? $fieldDef['minimum'] : 0;
                    
                    return self::renderNumber($fieldDef['name'], $value, $minimum, $maximum,$cls  );
                case TripManDictionary::FIELD_TYPE_FLOAT:
                case TripManDictionary::FIELD_TYPE_PRICE:
                    $maximum = isset($fieldDef['maximum']) ? $fieldDef['maximum'] : 0;
                    
                    $minimum = isset($fieldDef['minimum']) ? $fieldDef['minimum'] : 0;
                    
                    if(isset($fieldDef['step']) ){
                        $step = $fieldDef['step'];
                    }
                    else{
                        $step = $fieldDef['type'] = TripManDictionary::FIELD_TYPE_FLOAT ?
                                0.1 : 0.01;
                    }
                    
                    return self::renderFloat($fieldDef['name'], $value, $minimum, $maximum,$step, $cls  );
                case TripManDictionary::FIELD_TYPE_HIDDEN:

                    return self::renderHidden($fieldDef['name'], $value);
                case TripManDictionary::FIELD_TYPE_OPTION:
                    return self::renderOptionList( $fieldDef['name'],
                            $fieldDef['source'], $value, $cls );
                case TripManDictionary::FIELD_TYPE_LIST:
                    
                    $placeholder = isset($fieldDef['label']) ? $fieldDef['label'] : null;
                    
                    return self::renderList($fieldDef['name'],
                            $fieldDef['source'], $value, $cls,$placeholder );
                case TripManDictionary::FIELD_TYPE_CHECKBOX:
                    $checked = isset($fieldDef['value']) ? $fieldDef['value'] : '';

                    return self::renderCheckBox($fieldDef['name'], $checked, $cls);
                case TripManDictionary::FIELD_TYPE_TEXTAREA:
                    //$value = isset($fieldDef['value']) ? $fieldDef['value'] : '';
                    $placeholder = isset($fieldDef['label']) ? $fieldDef['label'] : null;
                    
                    return self::renderTextArea($fieldDef['name'],$value,$cls,$placeholder );
                case TripManDictionary::FIELD_TYPE_DATE:
                    //$placeholder = isset($fieldDef['label']) ? $fieldDef['label'] : null;
                    //self::renderDate($fieldDef['name'], $value, $cls,$placeholder);
                    //break;
                case TripManDictionary::FIELD_TYPE_PASSWORD:
                    return self::renderPassword($fieldDef['name'],$cls, false, $placeholder );
                case TripManDictionary::FIELD_TYPE_EMAIL:
                case TripManDictionary::FIELD_TYPE_TELEPHONE:
                default:
                    $size = isset($fieldDef['size']) ? $fieldDef['size'] : TripManDictionary::FIELD_DEFAULT_LENGTH;
                    
                    $placeholder = isset($fieldDef['label']) ? $fieldDef['label'] : null;
                    
                    return self::renderText($fieldDef['name'], $value, $cls,
                            $placeholder,
                            $size);
            }
        }
    }
    /**
     * <label></label>
     * @param string $name
     * @param string $label
     * @param string $class
     */
    public static final function renderLabel( $name, $label = null, $class='' ){
        return sprintf('<label for="id_%s" class="field-label %s">%s</label>',
            $name,$class, TripManStringProvider::__( !is_null( $label ) ? $label : $name ) );
    }
    /**
     * <span class="price" />
     * @param string $name
     * @param int $value
     * @param \TripManCurrencyModel $currency | NULL
     * @param string $class
     */
    public static final function renderPrice( $name, $value,\TripManCurrencyModel $currency = null, $class = '' ){

        
        return sprintf('<span id="id_%s" class="price %s">%s</price>',
                $name , $class , !is_null($currency) ? $currency->format( $value ) : $value );
    }
    /**
     * <textarea></textarea>
     * @param string $name
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     */
    public static final function renderTextArea( $name, $value = '', $class = '', $placeholder = null ){

        $placeholderTag = !is_null($placeholder) ?
                TripManStringProvider::__($placeholder) :
                TripManStringProvider::__('vac&iacute;o');
        
        return sprintf(
                '<textarea id="id_%s" class="field-value %s" name="%s" placeholder="%s">%s</textarea>',
                $name,$class ,
                TripManRequestProvider::prefixAttach( $name ),
                $placeholderTag ,$value);
    }
    /**
     * <input type="text" />
     * @param string $name
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     * @param int $size
     */
    public static final function renderText($name, $value = null, $class = '', $placeholder = null, $size = TripManDictionary::FIELD_DEFAULT_LENGTH ){

        $placeholderTag = !is_null($placeholder) ?
                TripManStringProvider::__($placeholder) :
                TripManStringProvider::__('vac&iacute;o');
        
        return sprintf(
                '<input id="id_%s" class="field-value %s" type="text" name="%s" value="%s" size="%s" placeholder="%s" />',
                $name,$class,
                TripManRequestProvider::prefixAttach( $name ),
                $value, $size,$placeholderTag);
    }
    /**
     * <input type="password" />
     * @param String $name
     * @param String $class
     * @param bool $duplicated Mostrar un input de confirmación del password
     * @param String $placeholder
     * @param int $size
     * @return String
     */
    public static final function renderPassword( $name, $class = '', $duplicated = false, $placeholder = null, $size = TripManDictionary::FIELD_DEFAULT_LENGTH ){
        
        $label = !is_null($placeholder) ? $placeholder : TripManStringProvider::__(TripManStringProvider::LBL_USER_PASS);
        
        $input = sprintf(
                '<input id="id_%s" class="field-value %s" type="password" name="%s" size="%s" placeholder="%s" />',
                $name, $class,
                TripManRequestProvider::prefixAttach( $name ),
                $size,$label);
        
        if( $duplicated ){
            $input .= sprintf(
                '<input class="field-value %s validation" type="password" name="%s_validation" size="%s" placeholder="%s" />',
                $class,$name,$size, TripManStringProvider::__('Repetir'). ' '. $label);
        }
        
        return $input;
    }
    /**
     * <input type="search" />
     * @param String $name
     * @param String $class
     * @param String $placeholder
     * @return String
     */
    public static final function renderSearch( $name, $class = '', $placeholder = null ){
        
        $label = !is_null($placeholder) ? $placeholder : TripManStringProvider::__('Buscar');
        
        $input = sprintf(
                '<input id="id_%s" class="field-filter %s" type="search" name="%s" placeholder="%s" />',
                $name, $class, TripManRequestProvider::prefixAttach( $name ),
                TripManStringProvider::__( $label ) );
        
        return $input;
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param int $min
     * @param int $max
     * @param String $class
     */
    public static final function renderNumber($name, $value = 0, $min = 0, $max = 0, $class = 'field-value' ){
        
        $maxTag = ( $max > 0 ) ? sprintf('max="%s"',$max) : '';
        
        return sprintf(
                '<input id="id_%s" class="%s" type="number" name="%s" value="%s" min="%s" %s />',
                $name,$class,
                TripManRequestProvider::prefixAttach( $name ),
                $value,$min,$maxTag);
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param String $class
     * @param String $placeholder
     * @param int $max 0 para no definir máximo
     */
    public static final function renderFloat($name, $value = null, $min = 0, $max = 0, $step = 0.1, $class = '' ){
        
        $maxTag = ( $max > 0 ) ? sprintf('max="%s"',$max) : '';
        $valTag = !is_null($value) ? sprintf('value="%s"',$value) : '';
        
        return sprintf(
                '<input id="id_%s" class="field-value %s" type="number" name="%s" step="%s" min="%s" %s %s />',
                $name,$class,
                TripManRequestProvider::prefixAttach( $name ),
                $step,$min,$maxTag,$valTag);
    }
    /**
     * <input type="date" />
     * Versión con jQuery UI
     * <input type="text" class="hasDatepicker" />
     * @param string $name
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     * @param int $size
     */
    public static final function renderDate($name, $value = '', $class = 'field-value', $placeholder = null ){

        $placeHolderTag = !is_null($placeholder) ?
                    TripManStringProvider::__($placeholder) :
                    TripManager::getOption('tripman_date_format');
        
        $cls = is_array($class) ? implode(' ', $class) : $class;
        
        return sprintf(
                '<input id="id_%s" class="%s" type="date" name="%s" value="%s" placeholder="%s" />',
                $name, $cls,
                TripManRequestProvider::prefixAttach( $name ),
                $value, $placeHolderTag );
    }
    /**
     * Versión con jQuery UI requerida 1.12.1 con soporte para DatePicker
     * <input type="text" class="calendar hasDatepicker" />
     * @param string $name
     * @param string $value
     * @param mixed $min
     * @param mixed $max
     * @param array $exceptions
     */
    public static final function renderDatePicker($name, $value = '', $min = null, $max = null, array $exceptions = null ){

        $datepickerPlugin = TripManager::createPlugin('jQueryUI');
        
        if( !is_null($datepickerPlugin)){
            //una vez cargado el plugin, establece los parámetros introducidos
            if( !is_null($min)){
                $datepickerPlugin->set('minDate', $min);
            }
            if( !is_null($max)){
                $datepickerPlugin->set('maxDate', $max);
            }
            if( !is_null($exceptions)){
                $datepickerPlugin->set('excludeDates', $exceptions);
            }
            if( strlen( $value ) ){
                $datepickerPlugin->set('value', $value);
            }

            //luego ejecuta el plugin, que genera la vista del widget
            return $datepickerPlugin->run(
                TripManjQueryUIPlugin::WIDGET_DATEPICKER,
                    $name);
        }
        
        return self::renderDate($name, $value);
    }
    /**
     * <input type="tel" />
     * @param string $name
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     */
    public static final function renderTelephone($name, $value = null, $class = '', $placeholder = null ){

        $placeholderTag = !is_null($placeholder) ?
                    TripManStringProvider::__($placeholder) :
                    TripManStringProvider::__('vac&iacute;o');
        
        return sprintf(
                '<input id="id_%s" class="field-value %s" type="tel" name="%s" value="%s" size="%s" placeholder="%s" />',
                $name, $class,
                TripManRequestProvider::prefixAttach( $name ),
                $value, TripManDictionary::FIELD_DEFAULT_LENGTH,
                $placeholderTag );
    }
    /**
     * <input type="email" />
     * @param string $name
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     */
    public static final function renderEmail($name, $value = null, $class = '', $placeholder = null ){
        
        $placeholderTag = !is_null($placeholder) ?
                    TripManStringProvider::__($placeholder) :
                    TripManStringProvider::__('vac&iacute;o');
        
        return sprintf(
                '<input id="id_%s" class="field-value %s" type="email" name="%s" value="%s" size="%s" placeholder="%s" />',
                $name, $class,
                TripManRequestProvider::prefixAttach( $name ),
                $value, TripManDictionary::FIELD_DEFAULT_LENGTH,
                $placeholderTag );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param mixed $class
     */
    public static final function renderCheckBox($name, $checked = false , $class = '', $disabled = false ){
        
        $disableTag = $disabled ? 'disabled="disabled"' : '';
        
        $checkTag = $checked ? 'checked=\"checked\"' : '';
        
        return sprintf(
                '<input id="id_%s" class="%s" type="checkbox" name="%s" value="1" %s %s />',
                $name,$class,
                TripManRequestProvider::prefixAttach( $name ),
                $disableTag, $checkTag );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param mixed $class
     */
    public static final function renderSelectable($name, $value, $checked = false , $class = '', $disabled = false ){
        
        $disableTag = $disabled ? 'disabled="disabled"' : '';
        
        $checkTag = $checked ? 'checked=\"checked\"' : '';
        
        return sprintf(
                '<input id="id_%s" class="%s" type="checkbox" value="%s" %s %s />',
                sprintf('%s_%s',$name,$value),$class,
                $value, $disableTag, $checkTag );
    }
    /**
     * Lista de opciones <input type="radio" />
     * @param String $name
     * @param array $options
     * @param string $value
     * @param string $class
     * @param boolean $disabled
     * @return String
     */
    public static final function renderOptionList( $name, array $options, $value = null, $class = '', $disabled = false ){

        $list = sprintf('<ul id="id_%s" class="field-value %s" >',$name,$class );
        
        $ctlDisable = $disabled ? 'disabled="disabled"' : '';

        foreach( $options as $optValue => $optLabel){
            
            $checked = ( !is_null( $value ) && $optValue == $value) ? 'checked="checked"' : '';
            
            $list .= sprintf('<li><label for="id_%s_%s">%s</label>',
                    $name,$optValue, $optLabel);
            
            $list .= sprintf("<input id=\"id_%s_%s\" type=\"radio\" name=\"%s\" value=\"%s\" %s %s />",
                    $name,$optValue,
                    TripManRequestProvider::prefixAttach($name),
                    $optValue, $checked, $ctlDisable ) ;
            
            $list .= '</li>';
        }

        return $list . '</ul>';
    }
    /**
     * <select size="5" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     */
    public static final function renderList($name, array $options, $value = null, $class = '' ){
        
        $list = sprintf('<select size="5" id="id_%s" class="field-value %s" name="%s" >',
                $name,$class,
                TripManRequestProvider::prefixAttach($name) );

        if( !is_null($placeholder)){
            $list .= sprintf('<option value="">- %s -</option>' ,TripManStringProvider::__($placeholder));
        }

        foreach($options as $optValue=>$optLabel){

            $selected = ( !is_null( $value ) && $optValue == $value) ?
                    ' selected="selected"' : '';
            
            $list .= sprintf('<option value="%s" %s >%s</option>',
                    $optValue,$selected,$optLabel);
        }

        return $list . '</select>';
    }
    /**
     * <select size="1" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param mixed $class
     * @param string $placeholder
     */
    public static final function renderDropDown($name, array $options, $value = null, $class = '', $placeholder = null ){
        
        printf('<!-- %s -->', $value);
        
        $list = sprintf('<select size="1" id="id_%s" class="field-value %s" name="%s" >',
                $name,$class,
                TripManRequestProvider::prefixAttach($name) );

        if( !is_null($placeholder)){
            $list .= sprintf('<option value="">- %s -</option>' ,TripManStringProvider::__($placeholder));
        }
        
        foreach($options as $optValue=>$optLabel){

            $selected = ( !is_null( $value ) && $optValue == $value) ?
                    ' selected="selected"' : '';
            
            $list .= sprintf('<option value="%s" %s >%s</option>',
                    $optValue,$selected,$optLabel);
        }

        return $list . '</select>';
    }
    /**
     * <input type="hidden" />
     * @param string $name
     * @param string $value
     * @param string $id
     * @param string $class
     */
    public static final function renderHidden($name, $value, $id = null ){
        
        $input_id = !is_null($id) ? sprintf('id="id_%s"',$id) : '';
        
        return sprintf('<input type="hidden" name="%s" value="%s" %s />',
                TripManRequestProvider::prefixAttach($name),
                $value , $input_id);
    }
    /**
     * <input type="file" />
     * @param string $name
     * @return HTML
     */
    public static final function renderFileUpload( $name ){
        
        $maxFileSize = intval( TripManager::getOption('tripman_max_filesize',1) ) * 1024 * 1024;
        
        return sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%s" />',$maxFileSize) .
            sprintf('<input id="id_%s" type="file" name="%s" />',$name,$name);
    }
    /**
     * <button type="submit" />
     * @param string $name
     * @param string $value
     * @param string $label
     * @param string $class
     * @return string
     */
    public static final function renderSubmit( $name, $value , $label, $class = '' ){
        return sprintf(
                '<button id="id%s_%s" type="submit" name="%s" value="%s" class="button %s" />%s</button>',
                $name,$value, TripManRequestProvider::prefixAttach($name),
                $value,$class, TripManStringProvider::__($label) );
    }
    /**
     * <a href=""></a>
     * @param string $action
     * @param string $label
     * @param bool $public
     * @param bool $new_view
     * @param string $class
     * @return HTML | String
     */
    public static final function renderAction( $action , $label , array $data = null, $new_view = false, $class = 'button' , $module = null ){

        $url = TripManRequestProvider::requestPublicRoute($action, $data , $module );
        
        $target = $new_view ? '_blank' : '_self';
        
        return sprintf('<a href="%s" target="%s" class="%s">%s</a>',
                $url, $target, $class, $label);
    }
    /**
     * <a href=""></a>
     * @param string $action
     * @param string $label
     * @param array $data
     * @param bool $new_view
     * @param string $class
     * @return HTML | String
     */
    public static final function renderAdminAction($action , $label , array $data = null, $new_view = false, $class = 'button'){
        
        $url = TripManRequestProvider::requestAdminRoute($action, $data );
        
        $target = $new_view ? '_blank' : '_self';
        
        /*if( !is_null($data) && count($data) ){
            $extra = array();
            foreach( $data as $var=>$val ){
                $extra[] = sprintf('%s=%s', TripManRequestProvider::prefixAttach($var),$val);
            }
            $url .= '&' . implode('&', $extra);
        }*/
        
        return sprintf('<a href="%s" target="%s" class="%s">%s</a>',
                $url, $target, $class, $label);
    }
    /**
     * Link para crear nuevo tipo de post
     * @param string $type
     * @param string $label
     * @return HTML
     */
    public static final function renderPostNewLink( $type, $label, $class = 'button' ){
        
        $url = sprintf('%s/wp-admin/post-new.php?post_type=%s_%s',
                get_site_url(),TripManager::PLUGIN_NAME,$type);
        
        return sprintf('<a href="%s" target="_self" class="%s">%s</a>',
                $url,$class, TripManStringProvider::__($label));
    }
    /**
     * Link para Editar tipo de post
     * @param int $id
     * @param string $label
     * @return HTML
     */
    public static final function renderPostEditLink( $id, $label, $class = 'button' ){
        
        $url = sprintf('%s/wp-admin/post.php?post=%s&action=edit',
                get_site_url() , $id );
        
        return sprintf('<a href="%s" target="_blank" class="%s">%s</a>',
                $url, $class, TripManStringProvider::__($label) );
    }
    /**
     * <img src="" />
     * @param string | URL $source
     * @param string $alt
     * @param string $title
     * @param mixed $class
     * @return string | HTML
     */
    public static final function renderImage( $source , $alt='' , $title = '', $class = 'default-image' ){
        
        return sprintf('<img src="%s" alt="%s" title="%s" class="%s" />',
                $source, $alt,
                strlen($title) ? $title : $alt,
                is_array($class) ? implode(' ', $class) : $class);
    }
    /**
     * <a href=""></a>
     * @param string $url
     * @param string $label
     * @param string $class
     * @param array|null $meta Información meta adicional del link
     * @return string | HTML
     */
    public static final function renderLink( $url, $label, $class = 'link', array $meta = null ){
        
        $target = '_self';
        
        if( !is_null($meta)){
            if( isset($meta['target']) ){
                $target = $meta['target'];
            }
        }
        
        return sprintf('<a href="%s" target="%s" class="%s">%s</a>',
                $url,$target,$class,$label);
    }
    /**
     * Link y checkbox de términos y condiciones
     * @param string $class
     */
    public static final function renderConditionsLink( $class = '' ){

        $conditions_link = self::renderLink(
                        TripManager::getBookingTermsLink(),
                        TripManStringProvider::__('condiciones de reserva'),
                        'link terms-and-conditions',
                        array('target'=>'_blank'));
        
        $label = sprintf('<label for="id_term_agreement">%s %s</label>',
            TripManStringProvider::__('Acepto las'),
            $conditions_link);
        
        return sprintf('<input id="id_term_agreement" type="checkbox" class="term_agreement %s" />%s',
                $class,$label);
    }
    /**
     * Genera la vista de una barra de progreso para mostrar
     * @param string $name
     * @param int $total
     * @param int $value
     * @param string $class
     * @param int $minimum Porcentaje mínimo a mostrar cuando el progreso no llega al umbral indicado (siempre que sea mayor que 0)
     * @return string | HTML
     */
    public static final function renderProgressBar( $name, $total, $value = 0, $display = 'progress' , $minimum = 10 , $label = '' ){
        //prevenir sobrecarga del valor de longitud de la barra de progreso
        $display_value = ( $value > $total ) ? $total : $value;
        
        $percent =  ($total > 0) ? number_format( ($display_value / (float)$total) * 100, 1 ) : 0;
        
        $display_percent = ( $percent > 0 && $percent < $minimum ) ? $minimum : $percent;
        
        switch( $display ){
            case 'progress':
                //mostrar N / M unidades
                $inset = sprintf(
                        '<span class="units">%s</span><span class="total">%s</span>',
                        $value,$total);
                break;
            case 'units':
                //mostrar solo N unidades
                $inset = $value > $display_value ? 
                    //valor excede el rango de la barra ( 110 / 100 )
                    $value :
                    //valor dentro del rango de la barra
                    $display_value ;
                break;
            case 'percent':
                //mostrar (N/Mx100) %
                $inset = $value > $display_value ?
                    //mostrar sobrecarga del rango (110%)
                    number_format( ($value / (float)$total) * 100, 1 ) :
                    //mostrar porcentaje
                    $percent ;
                break;
            default:
                //no mostrar nada
                $inset = '&nbsp;';
                break;
        }
        //esto muestra la barra de progreso interna
        $style = ( $percent > 0 ) ? sprintf( 'width: %s%%;', intval( $display_percent ) ) : 'display: none;';
        
        $highlight = ( $percent > 40 ) ? 'light' : 'dark';

        return sprintf( '<span class="progress-bar-container %s">', strtolower( $name ) ).
                sprintf( '<span class="progress-bar %s" style="%s"></span>',
                        $value > $display_value ? 'overflow' : '',
                        $style ).
                sprintf( '<span class="label %s %s">%s %s</span></span>',
                        $highlight, $display, $inset ,
                        TripManStringProvider::__( $label ) );
    }
    /**
     * Genera un control de progreso radial donde la leyenda del mismo puede mostrar de tres modos diferentes
     * 
     * - units: muestra tansolo el contador actual $value dentro del circulo
     * - progress: muestra las unidades en formato $value / $total dentro del circulo
     * - percentage: muestra el porcentaje calculado de $value / $total x 100 dentro del circulo
     * 
     * @param String $name
     * @param int $total
     * @param int $value
     * @param string $display units, progress o percent
     * @return HTML
     */
    public static final function renderRadialProgress( $name, $total, $value = 0, $display = 'units' ){
       
        $value = $value > $total ? $total : $value;
        
        $progress = ( $total > 0 ) ? intval( ($value / $total) * 360 ) : 0;

        $half = $progress < 180 ? $progress : 180;

        $showFull = $progress > 180 ? 'display' : '';
        
        switch( $display ){
            case 'progress':
                //mostrar N / M unidades
                $inset = sprintf(
                        '<label class="%s"><span class="units">%s</span><span class="total">%s</span></label>',
                        $display,$value,$total);
                break;
            case 'units':
                //mostrar solo N unidades
                $inset = sprintf('<label class="%s">%s</label>', $display, $value );
                break;
            case 'percent':
            default:
                //mostrar (N/Mx100) %
                $percent = ( $total > 0 ) ? intval( ($value / $total) * 100 ) : 0;
                $inset = sprintf('<label class="%s">%s</span>', $display, $percent );
                break;
        }

        $html = sprintf( '<div class="radial-progress-container %s"><div class="base">',
                    strtolower( $name ) ) .
                sprintf( '<div class="half" style="transform: rotate( %sdeg);"></div>', $half ) .
                '<div class="mask"></div>' .
                sprintf( '<div class="full %s" style="transform: rotate( %sdeg);"></div>',
                    $showFull,$progress ) .
                sprintf( '</div><div class="inset">%s</div></div>', $inset );

        return $html;
    }
    /**
     * @return URL Url de desconexión de la sesión de WP
     */
    public static final function renderWordPressLogOut(){
        return wp_logout_url( site_url() );
    }
    /**
     * Genera un link de acceso al panel de control del inversor
     * @return string| HTML Link de acceso
     */
    public static final function renderAdminShortCutLink(){
        return sprintf('<p>%s</p>',self::renderLink(
            TripManRequestProvider::requestAdminRoute(),
                TripManStringProvider::__('Gesti&oacute;n Trip Manager'),
                'button admin-shortcut',array('target'=>'_blank')));
    }
    /**
     * Genera un botón para acceder al directorio multimedia de WP
     * @param string $name
     * @return HTML
     */
    public static final function renderMediaButton( $name, $media_id = 0 ){
        
        $media_title = ( $media_id > 0 ) ?
                basename( get_attached_file( $media_id ) ) :
                TripManStringProvider::__(TripManStringProvider::LBL_SELECT);
        
        $class = ( $media_id > 0 ) ?
                $name . ' loaded' :
                $name;
        
        return sprintf(
                '<a href="#id_%s" class="clear-media">%s</a><input type="hidden" id="id_%s" name="%s" value="%s" /><button type="button" class="button media-selector %s">%s</button>',
                $name ,TripManStringProvider::__(TripManStringProvider::LBL_CLEAR),$name, $name,
                ($media_id > 0) ? $media_id : '', $class, $media_title );
    }
    /**
     * Recuperar la url de un archivo multimedia
     * @param int $media_id
     * @return URL
     */
    public static final function renderMediaUrl( $media_id ){
        return $media_id > 0 ? wp_get_attachment_url( $media_id ) : '#';
    }
    /**
     * Carga el contenido de una página por id para integrar en la intranet
     * 
     * Mucho ojo con los posts generados por un PageBuilder!!!
     * 
     * @param int $id
     * @return String
     */
    public static final function renderPost( $id , $class = '' ){

        $content = '';
        
        if( $id ){
            
            $post = get_post( $id );

            $content = !is_null( $post ) && $post->post_type === 'page' ?
                sprintf( preg_replace("/\n/", "<br/>", $post->post_content ) ) : '';
        }

        return sprintf('<div class="post-page %s"><p>%s</p></div>',$class, $content );
    }
    /**
     * Muestra la lista de mensajes capturados por el renderer
     * @param TripManRenderer $R
     * @param boolean $placeholder Permite fijar el area de notificación permanentemente
     */
    public static final function renderMessages( $level = TripManLogProvider::LOG_TYPE_NOTIFY, $placeholder = false ){
        
        $messages = TripManLogProvider::listLogs( $level );
        
        if( count($messages) || $placeholder ){
            
            $html = sprintf( '<ul class="tripman-notifier">' );
            
            foreach ($messages as $message) {
                //omitir notificaciones de debuging y errores en tiempo de ejecución del componente
                $html .= sprintf('<li class="%s">%s</li>',
                        $message->getType(true) ,
                        $message->getMessage() );
            }
            
            return $html . '</ul>';
        }
        
        return '';
    }
    /**
     * Muestra el contenido de un evento por pantalla
     * @param \TripManRequestProvider $request
     */
    public static final function renderRequestData( \TripManRequestProvider $request ){
        
        print '<div class="request-output">';
        
        printf('<h2>Informaci&oacute;n del Evento <span class="remark">%s</span></h2>',$request);

        var_dump($request);
        
        print '</div>';
    }
    /**
     * Muestra un log por pantalla
     * @param \TripManLogProvider $log
     */
    public static final function renderLog( \TripManLogProvider $log ){
        print $log;
    }
}