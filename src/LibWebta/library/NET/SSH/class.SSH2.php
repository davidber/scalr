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
     * @package    NET
     * @subpackage SSH
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */	

	/**
	 * @name SSH2
	 * @package NET
	 * @subpackage SSH
	 * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */	
	class SSH2 extends Core 
	{
		
		/**
		 * Stream timeout
		 */
		const STREAM_TIMEOUT = 10;
		
		/**
		 * Default units for terminal dimensions
		 */
		const TERM_UNITS = SSH2_TERM_UNIT_CHARS;
		
		/**
		 * Default terminal height
		 */
		const TERM_HEIGHT = 132; #SSH2_DEFAULT_TERM_HEIGHT;
		
		/**
		 * Default terminal width
		 */
		const TERM_WIDTH = 200; #SSH2_DEFAULT_TERM_WIDTH;
		
		/**
		 * @var integer Units for terminal dimensions
		 * @access public
		 */
		public $TermUnits;
		
		/**
		 * @var integer Terminal height
		 * @access public
		 */
		public $TermHeight;
		
		/**
		 * @var integer Terminal width
		 * @access public
		 */
		public $TermWidth;
		
	
		/**
		* SSH connection resource
		* @var resource SSH connection resource
		* @access private
		*/
		private $Connection;
		
		/**
		* Passwords array
		* @var array
		* @access private
		* @see AddPassword
		*/
		private $Passwords;
		
		/**
		* Public keys array
		* @var array
		* @access private
		* @see AddPubkey
		*/
		private $Pubkeys;
		
		/**
		 * Stream timeout
		 *
		 * @var integer
		 * @access private
		 */
		private $Timeout;
		
		/**
		 * SFTP Stream
		 *
		 * @var stream
		 * @access private
		 */
		private $SFTP = false;
		
		
		public $StdErr;
		
		/**
		 * SSH2 constructor
		 *
		 * @param integer $term_height
		 * @param integer $term_width
		 * @param integer $term_units
		 */
		function __construct($term_height = null, $term_width = null, $term_units = null)
		{
		    $this->TermHeight = (is_int($term_height) && $term_height > 0) ? $term_height : self::TERM_HEIGHT;
			$this->TermUnits = $term_units ? $term_units : self::TERM_UNITS;
			$this->TermWidth = (is_int($term_width) && $term_width > 0) ? $term_width : self::TERM_WIDTH;
		    $this->Timeout = self::STREAM_TIMEOUT;
		    
		    $this->Logger = Logger::getLogger('SSH2');
		}
		
		/**
		 * Set stream timeout
		 *
		 * @param integer $timeout
		 * @access public
		 */
		public function SetTimeout($timeout)
		{
		    $this->Timeout = $timeout;
		}
		
		/**
		 * Add password credentials for auth
		 *
		 * @param string $login SSH login
		 * @param string $password SSH password
		 * @access public
		 */
		public function AddPassword($login, $password)
		{
			$this->Passwords[] = array($login, $password);
		}
		
		/**
		 * Add Pubkey auth data
		 *
		 * @param string $login
		 * @param string $pubkeyfile
		 * @param string $privkeyfile
		 * @param string $passphrase
		 */
		public function AddPubkey($login, $pubkeyfile, $privkeyfile, $passphrase=null)
		{
			$this->Pubkeys[] = array($login, $pubkeyfile, $privkeyfile, $passphrase);
		}
		
		/**
		 * Return true if we connected to SSH
		 *
		 * @return bool
		 */
		public function IsConnected()
		{
		    return ($this->Connection && is_resource($this->Connection));
		}
		
		/**
		 * Test connection to remote host
		 *
		 * @param string $host
		 * @param integer $port
		 * @return bool
		 */
		public function TestConnection($host, $port=22)
		{
		    $sock = @fsockopen($host, $port, $errno, $errstr, $this->Timeout);
		    if (!$sock)
		    {
		        $this->Logger->warn("Unable to connect to SSH server on {$host}:{$port}. ({$errno}) {$errstr}");
				return false;
		    }
		    else 
                @fclose($sock);
			
            return true;
		}
		
		/**
		* Connect to SSH server and authenticate with password
		* @access public
		* @param string $host Host to connect
		* @param string $port Port to connect
		* @param string $login Login to authenticate with
		* @param string $password Password to authenticate with
		* @return array
		*/
		public function Connect($host, $port=22, $login = null, $password = null)
		{
				
			// Backwards compat
			if ($login)
				$this->AddPassword($login, $password);
			
			try 
			{
				if (count($this->Pubkeys) > 0)
					$hostkeys = array('hostkey' => 'ssh-rsa');
				else 
					$hostkeys = array();
				
			    $this->Connection = ssh2_connect($host, $port, $hostkeys);
			    				
				if (!$this->Connection)
				{
					$this->Logger->warn("Unable to connect to SSH server on {$host}:{$port}");
					return false;
				}
				
				if ($this->Connection)
				{
					// Try all avaliable pubkeys
					foreach ((array)$this->Pubkeys as $p)
					{
						if (ssh2_auth_pubkey_file($this->Connection, $p[0], $p[1], $p[2], $p[3]))
							return true;
					    else 
					        $this->Logger->warn("Cannot login to SSH using PublicKey");
					}
					
					
					// Try all avaliable passwords
					foreach ((array)$this->Passwords as $p)
					{
						if (ssh2_auth_password($this->Connection, $p[0], $p[1]))
							return true;
					    else 
					        $this->Logger->warn("Cannot login to SSH");
					}
					
				}
				$this->Connection = false;
				return false;
				
			}
			catch (Exception $e) 
			{
				$this->Logger->warn($e->getMessage());
				return false;
			}
				
			return true;
		}

		/**
		 * Return SSH2 shell stream
		 *
		 * @return stream
		 */
		public function GetShell()
		{
			try 
			{
			    if ($this->Connection)
				{
				    $stream = @ssh2_shell($this->Connection, 
					                    null, 
					                    null, 
					                    $this->TermWidth, 
					                    $this->TermHeight, 
					                    $this->TermUnits
					                   );
					                   
					if ($stream)
						return $stream;
					else
						return false;
				}
				else
					return false;
			} 
			catch (Exception $e) 
			{
				return false;
			}
		}
		
		/**
		* Execute a command and returns both stdout and stderr output
		* @access public
		* @param string $command remote shell command to execute
		* @return bool
		*/
		public function Exec($command, $stopstring = false)
		{
		    try 
			{
			    if ($this->Connection)
				{
				    $stream = @ssh2_exec($this->Connection, 
					                    "{$command}", 
					                    null, 
					                    null, 
					                    $this->TermWidth, 
					                    $this->TermHeight, 
					                    $this->TermUnits
					                   );
					                   
					@stream_set_blocking($stream, true);
					@stream_set_timeout($stream, $this->Timeout);
					
					$stderr_stream = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
					$this->StdErr = @fread($stderr_stream, 4096);
					@fclose($stderr_stream);
					
					if ($this->StdErr != '')
						$this->Logger->info("STDERR: {$this->StdErr}");
					
					// Read from stream
					while($l = @fgets($stream, 4096))
					{
						$meta = stream_get_meta_data($stream);
						if ($meta["timed_out"])
							break;
						$retval .= $l;
						
						if ($stopstring && stripos($l, $stopstring) !== false)
							break;
					}
					
					if ($retval == '')
						$retval = true;
					
					// Close stream
					@fclose($stream);
				}
				else
					return false;
			} 
			catch (Exception $e) 
			{
				return false;
			}
		
			return $retval;
		}
		
		
		/**
		* Transfer file over SFTP
		* @access public
		* @param string $remote_path Remote file path
		* @param string $local_path Local file path
		* @param string $write_type Write path
		* @param bool $read_content_from_file If True we read content from '$source' else content = $source
		* @return bool
		*/
		public function SendFile($remote_path, $source, $write_type = "w+", $read_content_from_file = true)
		{
			try 
			{
				if ($this->Connection)
				{
					if (!$this->SFTP || !is_resource($this->SFTP))
						$this->SFTP = @ssh2_sftp($this->Connection);
						
					if ($this->SFTP && is_resource($this->SFTP))
					{
						$stream = @fopen("ssh2.sftp://{$this->SFTP}".$remote_path, $write_type);
						if ($stream)
						{
						    if ($read_content_from_file)
							 $content = @file_get_contents($source);
							else 
							 $content = $source;
							 
							if (fwrite($stream, $content) === FALSE) 
							{
								throw new Exception(sprintf(_("SFTP: Cannot write to file '%s'"), $remote_path));
								return false;
							}
							@fclose($stream);
							return true;
						}
						else
						{
							$this->Logger->warn(sprintf(_("SFTP: Cannot open remote file '%s'"), $remote_path));
							return false;
						}
					}
					else
						return false;
				}
				else
					return false;
			}
			catch (Exception $e) 
			{
				return false;
			}
		}
		
		/**
		* Get file contents over SFTP
		* @access public
		* @param string $remote_path Remote file path
		* @return strung
		*/
		public function GetFile($remote_path)
		{
			$retval = false;
			try 
			{
				if ($this->Connection)
				{
					if (!$this->SFTP || !is_resource($this->SFTP))
						$this->SFTP = @ssh2_sftp($this->Connection);
						
					if ($this->SFTP && @is_resource($this->SFTP))
					{
						$this->Logger->info("Open stream to: ssh2.sftp://CONNECTION$remote_path");
						
						$stream = @fopen("ssh2.sftp://{$this->SFTP}".$remote_path, "r");
						if ($stream)
						{
							$this->Logger->info("Reading: $remote_path");
							$string = true;
							
							@stream_set_timeout($stream, 5);
							@stream_set_blocking($stream, 0);
							
							while($string !== false)
							{
								$string = @fgetc($stream);
								$retval .= $string;
							}
							
							$info = serialize(stream_get_meta_data($stream));
							
							@fclose($stream);
							
							return $retval;
						}
						else
						{
							$this->Logger->warn(sprintf(_("SFTP: Cannot open remote file '%s'"), $remote_path));
							return false;
						}
					}
					else
					{
						$this->Logger->warn(_("SFTP: Connection broken"));
						return false;
					}
				}
				else
				{
					$this->Logger->warn(_("No established SSH connection"));
					return false;
				}
			}
			catch (Exception $e) 
			{
				$this->Logger->warn($e->__toString());
				return false;
			}
		}
		
	}
        
?>