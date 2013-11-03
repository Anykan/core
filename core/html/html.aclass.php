<?php
 /*
 * Project:		EQdkp-Plus
 * License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:		2013
 * Date:		$Date: 2013-04-24 10:23:19 +0200 (Mi, 24 Apr 2013) $
 * -----------------------------------------------------------------------
 * @author		$Author: godmod $
 * @copyright	2006-2013 EQdkp-Plus Developer Team
 * @link		http://eqdkp-plus.com
 * @package		eqdkp-plus
 * @version		$Rev: 13337 $
 * 
 * $Id: super_registry.class.php 13337 2013-04-24 08:23:19Z godmod $
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

abstract class html {
	// field type
	protected static $type = '';
	
	protected static $ignore = array('text', 'text2', 'type');
	
	public function __construct($name, $options=array()) {
		$this->name = $name;
		foreach($options as $key => $option) {
			if(in_array($key, self::$ignore)) continue;
			$this->$key = $option;
		}
	}
	
	abstract protected function _toString();
	
	abstract public function inpval();
	
	public function __get($name) {
		if($name == 'type') return self::$type;
		$class = register($name);
		if($class) return $class;
		return null;
	}
	
	public function __toString() {
		if(empty($this->value) && isset($this->default)) $this->value = $this->default;
		return $this->_toString();
	}
	
	protected function cleanid($input) {
			if(strpos($input, '[') === false && strpos($input, ']') === false) return $input;
			$out = str_replace(array('[', ']'), array('_', ''), $input);
			return 'clid_'.$out;
	}
}
?>