<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Allow only child classes to access the repository core attributes
 */
class Repository extends Component{
    
    /**
     * 
     * @param \CodersApp $app
     */
    protected function __construct( \CodersApp $app , array $data = array( ) ) {
        
        $this->set( 'app', $app->endPointName())
                ->set('key', $app->endPointKey())
                ->set('name', isset($data['name']) ? $data['name'] : 'undefined' )
                ->set('type', isset($data['type']) ? $data['type'] : '' )
                ->set('size', isset($data['size']) ? $data['size'] : 0 );
    }
    /**
     * @param string $source
     * @return string
     */
    public function path( $source = '' ){
        
        $app = \CodersApp::instance($this->get('app'));
        
        if( FALSE !== $app ){
            
            $root = $app->repoPath();
            
            if(strlen($root)){
                
                return strlen($source) ?
                        sprintf('%s/%s/', $$root,$source) :
                        $root;
            }
        }
        
        return '';
    }
    /**
     * @return string
     */
    protected function makeFileId(){
        return md5(uniqid(date('YmdHis'),TRUE));
    }
    /**
     * @return string
     */
    public function getPath(){
        
        $id = $this->getId();
        
        return strlen($id) ? $this->path($id) : '';
    }
    /**
     * @return string
     */
    public function getId(){ return $this->get('id', ''); }
    /**
     * @return string
     */
    public function getName(){ return $this->get('name',''); }
    /**
     * @return string
     */
    public function getType(){ return $this->get('type',''); }
    /**
     * @return string
     */
    public function getExtension(){ return $this->get('extension',''); }
    /**
     * @return string
     */
    public function getDescription(){ return $this->get('description',''); }


    public function create( array $meta , $content = '' ){
        
    }
    
    public function save( ){
        
    }
    /**
     * @return string
     */
    public function load(){
        
        $path = $this->getPath();
        
        if(strlen($path) && file_exists($path)){

            return file_get_contents($path);
        }
        
        return '';
    }
    /**
     * Upload a file
     * @param string $input
     * @return \CODERS\Framework\Repository|boolean
     * @throws \Exception
     */
    public static final function upload( $input ){
        
        try{
            
            $app = \CodersApp::current();

            if( FALSE === $app ){throw new \Exception('Invalid app'); }
            
            $file = array_key_exists($input, $_FILES) ? $_FILES[ $input ] : array();

            if( count($file) === 0 ){throw new \Exception('Invalid file'); }
            
            $file['id'] = $this->makeFileId();

            $destination = $this->path($file['id']);
            
            if(strlen($destination) ){throw new \Exception('Invalid desstination'); }
            
            switch( $file['error'] ){
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception('Read only error');
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception('Extension error');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('Form allowed size overflow');
                case UPLOAD_ERR_INI_SIZE:
                    throw new \Exception('Ini allowed size overflow');
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception('No file');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception('No temp dir');
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception('Partial File');
                case UPLOAD_ERR_OK:
                    //nothing
                    break;
            }

            if(move_uploaded_file($file['tmp_name'], $destination)){
                return new Repository($app, $file );
            }
            else{
                throw new \Exception('Upload error');
            }
        }
        catch (\Exception $ex) {
            print( $ex->getMessage() );
        }
        
        
        return FALSE;
    }
}


