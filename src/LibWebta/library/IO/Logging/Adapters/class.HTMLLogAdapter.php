<?
	/**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * Load LogAdapter
	 */
	Core::Load("IO/Logging/Adapters/interface.LogAdapter.php");
	
	/**
     * @name HTMLLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class HTMLLogAdapter extends Core implements LogAdapter
	{
	    /**
	     * Log name
	     *
	     * @var string
	     */
		public $LogName;
		
		/**
		 * Adapter options
		 *
		 * @var array
		 * @access protected
		 */
	    protected $Options = array('format' => "%logname%, %level%, %message%<br \>");
	
	   /**
	    * Constructor
	    * @ignore 
	    *
	    */
		public function __construct()
		{
			parent::__construct();
		}
	
		/**
		 * Destructor
		 * @ignore 
		 *
		 */
		public function __destruct()
		{
			$this->Close();
		}
	
		/**
		 * Set adapter option
		 *
		 * @param string $optionKey
		 * @param string $optionValue
		 * @return bool
		 */
		public function SetOption($optionKey, $optionValue)
		{
		    if (!array_key_exists($optionKey, $this->Options)) {
		        if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Unknown option \"$optionKey\"."), E_WARNING));
		        else 
                    return false;
		    }
		    
		    $this->Options[$optionKey] = $optionValue;
		    return true;
		}
	
		
		/**
		 * Open Log
		 *
		 * @return bool true
		 */
		public function Open()
		{
			return true;
		}
	
		/**
		 * Close Log
		 *
		 * @return bool true
		 */
		public function Close()
		{
			return true;
		}
	
	
		/**
		 * Write Log to output
		 *
		 * @param array $fields
		 * @return bool
		 */
		public function Write($fields)
		{
		    $fields['logname'] = $this->LogName;
		    echo ($this->ParseLogLine($fields));
		    
			return true;
		}
	
	   /**
	    * Parse log line
	    *
	    * @param array $fields
	    * @return string
	    */
		protected function ParseLogLine($fields)
		{
	        $output = $this->Options['format'];
	        
		    foreach ($fields as $fieldName => $fieldValue) {
		        $output = str_replace("%$fieldName%", $fieldValue, $output);
		    }
		    
		    return $output;
		}
	
	}

?>