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
  * Registry to save and request object. to get one instance only
  * 
  * Use Singleton Design Pattern 
  * 
  */

class Registry {

    var $__objects = array();

    var $__map = array();

    var $__config = array();
    
    function _assignLibraries(){
        if ($CI =& get_instance())
        {
            $this->lang = $CI->lang;
            $this->load = $CI->load;
            
            $this->config = $CI->config;
        }
    }
    
    function &getInstance() {
        $this->_assignLibraries();
        $this->load->helper('inflector');
        static $instance = array();
        if (!$instance) {
            $instance[0] =& new Registry();
        }
        return $instance[0];
    }

    function &init($class, $type = null) {
        $_this =& Registry::getInstance();
        $id = $false = false;
        $true = true;

        if (!$type) {
            $type = 'Model';
        }

        if (is_array($class)) {
            $objects = $class;
            if (!isset($class[0])) {
                $objects = array($class);
            }
        } else {
            $objects = array(array('class' => $class));
        }
        
        $defaults = isset($_this->__config[$type]) ? $_this->__config[$type] : array();
        $count = count($objects);

        foreach ($objects as $key => $settings) {
            if (is_array($settings)) {
                $plugin = $pluginPath = null;
                $settings = array_merge($defaults, $settings);
                $class = $settings['class'];

                if (strpos($class, '.') !== false) {
                    list($plugin, $class) = explode('.', $class);
                    $pluginPath = $plugin . '.';
                }

                if (empty($settings['alias'])) {
                    $settings['alias'] = $class;
                }
                $alias = $settings['alias'];

                if ($model =& $_this->__duplicate($alias, $class)) {
                    $_this->map($alias, $class);
                    return $model;
                }

                if (class_exists($class) || App::import($type, $class)) {
                    ${$class} =& new $class($settings['class']);
                } 

                if (!isset(${$class})) {
                    trigger_error(sprintf(__('(Registry::init() could not create instance of %1$s class %2$s ', true), $class, $type), E_USER_WARNING);
                    return $false;
                }

                if ($type !== 'Model') {
                    $_this->addObject($alias, ${$class});
                } else {
                    $_this->map($alias, $class);
                }
            } elseif (is_numeric($settings)) {
                trigger_error(__('(Registry::init() Attempted to create instance of a class with a numeric name', true), E_USER_WARNING);
                return $false;
            }
        }

        if ($count > 1) {
            return $true;
        }
        return ${$class};
    }

    function addObject($key, &$object) {
        $_this =& Registry::getInstance();
        $key = underscore($key);
        if (!isset($_this->__objects[$key])) {
            $_this->__objects[$key] =& $object;
            return true;
        }
        return false;
    }
    
    function removeObject($key) {
        $_this =& Registry::getInstance();
        $key = underscore($key);
        if (isset($_this->__objects[$key])) {
            unset($_this->__objects[$key]);
        }
    }

    function isKeySet($key) {
        $_this =& Registry::getInstance();
        $key = underscore($key);
        if (isset($_this->__objects[$key])) {
            return true;
        } elseif (isset($_this->__map[$key])) {
            return true;
        }
        return false;
    }

    function keys() {
        $_this =& Registry::getInstance();
        return array_keys($_this->__objects);
    }
    
    function &getObject($key) {
        $_this =& Registry::getInstance();
        $key = underscore($key);
        $return = false;
        if (isset($_this->__objects[$key])) {
            $return =& $_this->__objects[$key];
        } else {
            $key = $_this->__getMap($key);
            if (isset($_this->__objects[$key])) {
                $return =& $_this->__objects[$key];
            }
        }
        return $return;
    }

    function config($type, $param = array()) {
        $_this =& Registry::getInstance();

        if (empty($param) && is_array($type)) {
            $param = $type;
            $type = 'Model';
        } elseif (is_null($param)) {
            unset($_this->__config[$type]);
        } elseif (empty($param) && is_string($type)) {
            return isset($_this->__config[$type]) ? $_this->__config[$type] : null;
        }
        $_this->__config[$type] = $param;
    }

    function &__duplicate($alias,  $class) {
        $duplicate = false;
        if ($this->isKeySet($alias)) {
            $model =& $this->getObject($alias);
            if (is_object($model) && (is_a($model, $class) || $model->alias === $class)) {
                $duplicate =& $model;
            }
            unset($model);
        }
        return $duplicate;
    }

    function map($key, $name) {
        $_this =& Registry::getInstance();
        $key = underscore($key);
        $name = underscore($name);
        if (!isset($_this->__map[$key])) {
            $_this->__map[$key] = $name;
        }
    }

    function mapKeys() {
        $_this =& Registry::getInstance();
        return array_keys($_this->__map);
    }

    function __getMap($key) {
        if (isset($this->__map[$key])) {
            return $this->__map[$key];
        }
    }

    function flush() {
        $_this =& Registry::getInstance();
        $_this->__objects = array();
        $_this->__map = array();
    }
}
?>