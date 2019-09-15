<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Gestor de conexión a la base de datos WP
 * 
 * v1.1.02 - 2017-05-26
 * -> Agregado el método insertOrUpdate para facilitar acutalizaciones de datos cuando la clave primaria ya existe
 */
class DB{
    /**
     * @var string
     */
    private $_appKey;
    /**
     * WordPress DB Instance
     * @var \wppdb
     */
    private $_wpdb = NULL;
    /**
     * @global wpdb $wpdb
     * @param string $appKey
     */
    public function __construct( \CodersApp $app ) {
        
        global $wpdb;
        
        $this->_wpdb = $wpdb;

        $this->_appKey = $app->endPointKey();
        
    }
    /**
     * @return \wpdb
     */
    public function wpdb(){
        return $this->_wpdb;
    }
    
    /**
     * Comprueba si ha habido errores que anotar
     * @return boolean
     */
    public final function checkErrors(){
        
        return strlen( $this->_wpdb->last_error );
    }
    /**
     * @global string $table_prefix
     * @param string $table
     * @param boolean $quote
     * @return string
     */
    public function table( $table , $quote = FALSE ){
        
        global $table_prefix;
        
        $output = sprintf('%s%s_%s',
                $table_prefix,
                $this->_appKey,
                strtolower( $table ) );
        
        return $quote ? sprintf('`%s`',$output) : $output;
    }
    /**
     * Select records
     * @param string $table
     * @param array $fields
     * @param array $filters
     * @return array
     */
    public function select( $table , array $fields , array $filters = array( ) ){
        
        return array();
    }
    /**
     * Insert values
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert( $table , array $data ){
        
        $columns = array_keys($data);

        $values = array();
        
        foreach( $data as $val ){
            if(is_array($val)){
                //listas
                $values[] = sprintf("'%s'",  implode(',', $val));
            }
            elseif(is_numeric($val)){
                //numerico
                $values[] = $val;
            }
            else{
                //texto
                $values[] = sprintf("'%s'",$val);
            }
        }
        
        $sql_insert = sprintf('INSERT INTO `%s` (%s) VALUES (%s)',
                $this->getTable($table),
                implode(',', $columns),
                implode(',', $values));
        
        $result = $this->_wpdb->query($sql_insert);
        
        if( $result === false ){

            $this->checkErrors();
            
            return 0;
        }
        
        return $result;
    }
    /**
     * Update/Insert values
     * @param string $table
     * @param array $values
     * @param array $filters
     * @return int
     */
    public function upsert( $table , array $values , array $filters){
        
        return 0;
    }
    /**
     * Update a table
     * @param string $table
     * @param array $values
     * @param array $filters
     * @return int
     */
    public function update( $table, array $values , array $filters ){
        
        return 0;
    }
    /**
     * Remove from a table
     * @param string $table
     * @param array $filters
     * @return int
     */
    public function delete( $table , array $filters ){
        
        return 0;
    }
}