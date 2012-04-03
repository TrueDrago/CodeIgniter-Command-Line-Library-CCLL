<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Command line Class
 *
 * The class for managing cli arguments: making an array of possible arguments for scripts,
 * validating them, printing help for usage of script
 * 
 *
 * @author		Igor Demishev
 * @link		https://github.com/TrueDrago/CodeIgniter-Command-Line-Library-CCLL
 */
class Command_line {
	
	protected $_possible_arguments = array();
	protected $_arguments = array();
	protected $_errors = array();
	protected $CI;

	/**
	 * Command Line Class Constructor
	 * 
	 * @param	array	$arguments	array of possible arguments for your cli script [[]=>[arg => string, alias => string, help => string, type => int]]
	 * 
	 */
	public function __construct($arguments = array()) {
		// Set the super object to a local variable for use later
		$this->CI =& get_instance();
		
		$this->_set_possible_arguments($arguments);
		
		$raw_args = array_slice($_SERVER["argv"], 3); // Deleting route info
		
		foreach ($raw_args AS $key => $val) {
			// Is it even a CLI argument?
			if ($this->is_cli_argument($val)) {	
				$p_arg = $this->get_possible_argument($val);
				// If this arg isnt mentioned as possible, we proceed further 
				if(!$p_arg) { 
					log_message('warning', "Cli class found not mentioned argument");
					continue;
				}
				// If argument is type (bool), we set it true
				if($p_arg['type']==2) {
					$this->_arguments[$p_arg['arg']] = TRUE;
					if (!empty($p_arg['alias']))
						$this->_arguments[$p_arg['alias']] = &$this->_arguments[$p_arg['arg']];
				}
				else {
					$arg_value = (isset($raw_args[$key+1]) && !$this->is_cli_argument($raw_args[$key+1])) ? $raw_args[$key+1] : FALSE;
					// If argument value is required we check it
					if($p_arg['type']==1 && !$arg_value) {
						$this->_set_error($val);
					}
					else {
						$this->_arguments[$p_arg['arg']] = $this->_prepare_value($arg_value);
						if (!empty($p_arg['alias']))
							$this->_arguments[$p_arg['alias']] = &$this->_arguments[$p_arg['arg']];
					}
				}
			}
		}
		log_message('debug', "Cli Class Initialized");
	}
	
	/**
	 *	Strips quotes from parameter string if needed
	 * 
	 * @param string $value
	 * @return string 
	 */
	protected function _prepare_value($value) {
		if (preg_match("/^[\"'](.*)[\"']$/", $value, $prepared_value))
			return $prepared_value[1];
		return $value;
	}
	
	/**
	 *	Adds the specified error to the errors array
	 * 
	 * @param string $argument argument key
	 * @param string $error (optional)
	 */
	protected function _set_error($argument, $error = FALSE) {
		$error = $error OR "Please specify an argument value for ".$argument;
		$this->_errors[] = $error;
	}
	
	/**
	 *	Determines if the specified string is argument key (have - or -- in the beginning)
	 * 
	 * @param string $argument
	 * @return boolean 
	 */
	public function is_cli_argument($argument) {
		return preg_match("/^(-){1,2}([A-Z])+/i", $argument);
	}
	
	/**
	 *	Recursively sets the possible arguments array
	 * 
	 * @param array|string $argument
	 * @param string $alias
	 * @param string $help
	 * @param boolean $type
	 * @return boolean 
	 */
	protected function _set_possible_arguments($argument, $alias = '', $help = '', $type = 0) {
		// If an array was passed via the first parameter instead of indidual string
		// values we cycle through it and recursively call this function.
		if (is_array($argument)) {
			foreach ($argument as $row) {
				// Houston, we have a problem...
				if (!isset($row['arg']) && !isset($row['alias']))
					continue;

				// If the argument name wasn't passed we use the alias name
				$arg = (!isset($row['arg'])) ? $row['alias'] : $row['arg'];
				$alias = (isset($row['alias'])) ? $row['alias'] : '';
				$help = (isset($row['help'])) ? $row['help'] : '';
				$type = (isset($row['type'])) ? $row['type'] : 0;
				
				// Here we go!
				$this->_set_possible_arguments($arg, $alias, $help, $type);
			}
			return TRUE;
		}

		// No arg or alias? Nothing to do...
		if (!is_string($argument) || ! is_string($alias))
			return FALSE;

		// Build our master array
		$this->_possible_arguments[$argument] = array(
			'arg'				=> $argument,
			'alias'				=> $alias,
			'help'				=> $help,
			'type'				=> $type
		);
		
		if($type==0 || $type==2) {
			$this->_arguments[$argument] = FALSE;
			$this->_arguments[$alias] = &$this->_arguments[$argument];
		}
		
		// Make an alias if needed
		if (!empty($alias) && $alias!=$argument) {
			$this->_possible_arguments[$alias] = &$this->_possible_arguments[$argument];
		}
		return TRUE;
	}
	
	/**
	 *	Returns the key for possible argument or false if there is no such argument
	 * 
	 * @param string $argument
	 * @return string|boolean 
	 */
	public function get_possible_argument($argument) {
		return isset($this->_possible_arguments[$argument]) ? $this->_possible_arguments[$argument] : FALSE;
	}
	
	/**
	 *	Returns the specified argument's value
	 * 
	 * @param string $argument
	 * @return string 
	 */
	public function get_argument($argument) {
		return $this->_arguments[$argument];
	}
	
	/**
	 *	Checks if all required arguments are specified and sets errors on fail
	 * 
	 * @return boolean
	 */
	protected function _check_required() {
		$parsed_args = array();
		$trigger = TRUE;
		foreach ($this->_possible_arguments AS $p_arg) {
			if (in_array($p_arg['arg'], $parsed_args))
				continue;
			
			$parsed_args[] = $p_arg['alias'];
			$parsed_args[] = $p_arg['arg'];
			
			if ($p_arg['type']==1 && !isset($this->_arguments[$p_arg['arg']]) && !isset($this->_arguments[$p_arg['alias']])) {
				$error = 'Please specify required argument: '.$p_arg['arg'];
				if (!empty($p_arg['alias']))
					$error .= ' ('.$p_arg['alias'].')';
				$this->_set_error($p_arg['arg'], $error);
				$trigger = FALSE;
			}
		}
		return $trigger;
	}
	
	/**
	 *	Checks if the cli input was valid
	 * 
	 * @return boolean 
	 */
	public function valid_input() {	
		return (!empty($this->_arguments) && empty($this->_errors) && $this->_check_required());
	}
	
	/**
	 *	Returnes the wel formated string with help info & errors if needed
	 * 
	 * @return string 
	 */
	public function get_help() {
		$help = '';
		if (!empty($this->_errors)) {
			$help .= 'Warning! Some errors have occured:'.PHP_EOL.PHP_EOL;
			foreach ($this->_errors AS $err) {
				$help .= $err.PHP_EOL;
			}
			$help .= PHP_EOL;
		}
		
		$class = $this->CI->router->class;
		$method = $this->CI->router->method;
		
		$help .= 'Usage: php index.php '.$class.' '.$method.' [OPTIONS]'.PHP_EOL.PHP_EOL;
		if (empty($this->_possible_arguments)) 
			return $help;
		
		$help .= 'Options are:'.PHP_EOL;
		$parsed_args = array();
		foreach ($this->_possible_arguments AS $argument) {
			// If we already printed this arg, proceed to next
			if (in_array($argument['arg'], $parsed_args))
				continue;

			if (isset($argument['alias']) && !empty($argument['alias'])) {
				$help .=$argument['alias']. ', ';
				$parsed_args[] = $argument['alias'];
			}
			$help .= $argument['arg'];
			$parsed_args[] = $argument['arg'];
			$help .="\t\t"; 
			if (isset($argument['help']))
				$help .= $argument['help'];
			$help .= PHP_EOL; 
			
		}
		return $help;
	}
}