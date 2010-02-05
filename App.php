<?php

  /**
 * An open source application development framework 
 *
 * @author        Jan Kristanto (jan_kristanto@yahoo.co.id)
 * @copyright    Copyright (c) 2009.
 * @link        http://jan.web.id
 * @Version     0.01
 */
 
 /**
  * App Class to import from other module
  * 
  *  
  * 
  */
  
  Class App {
      
      var $_path = array(
        'Model' => 'models' ,
        'Controller' => 'controllers'
      );
      
      function &getInstance() {
        static $instance = array();
        if (!$instance) {
            $instance[0] =& new App();
            
        }
        return $instance[0];
      }
      
      function import($type = null, $name = null,$file = null, $dir = null){
        $_this =& App::getInstance();
        
        $class = strtolower($name);
        
        $path = APPPATH . $_this->_path[$type] . $dir;  
      
        $file = $path . '/' . $class . EXT;

        if (file_exists($file))
        {
            require_once($file);
            return TRUE;
        }    
      }
  }
?>
