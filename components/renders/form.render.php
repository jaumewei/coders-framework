<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class FormRender extends Renderer{

    protected function __construct(\CodersApp $app) {
        
        parent::__construct($app);

    }

    
}
