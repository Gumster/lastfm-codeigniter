<?php
/**
 * Stores the caching methods
 * @package base
 */
/**
 * Allows access to the caching methods to cache data when api calls are made
 * @package base
 */
class lastfmApiCache {
	/**
	 * Stores the batabase class
	 * @var class
	 */
	private $dbconn;
	/**
	 * Stores the batabase type
	 * @var string
	 */
	private $type;
	/**
	 * Stores the error details
	 * @var array
	 */
	public $error;
	/**
	 * Stores the path to the sqlite database
	 * @var string
	 */
	private $path;
	/**
	 * Stores the amount of time to cahce for
	 * @var integer
	 */
	private $cache_length;
	/**
	 * Stores the config array
	 * @var array
	 */
	private $config;
	/**
	 * States if caching is enabled or not
	 * @var boolean
	 */
	private $cache_enabled;
	
	/**
	 * Store CI super object
	*/
	private $CI;
	

	/**
	 * Run when the class is created
	 * @param array $config The config array
	 * @uses lastfmApiDatabase
	 */
	function __construct($config) {
		
		$this->CI =& get_instance();		
		
		$this->config = $config;
	
		if(isset($config['cache_enabled']))
			$this->cache_enabled = $config['cache_enabled'];
		else
			$this->cache_enabled = TRUE;
			
		if (isset($config['cache_type']) ) {
			$this->type = $config['cache_type'];			
		}
		else {
			$this->type = 'sqlite';
		}
		
		// 
		if ($this->cache_enabled) {
			
			$this->dbconn = $this->CI->load->database($this->config['cache_group'], TRUE);			

			$this->check_table_exists();

		}
	}
	
	
	/**
	 * Internal method to check if the table exists
	 * @access private
	 * @return void
	 */
	private function check_table_exists() {
		// check tables nicely
		$result = array();

		if($this->type == 'sqlite') {
			// add dbprefix
			$tablename = $this->dbconn->dbprefix . $this->config['cache_table'];
			$query = "SELECT name FROM sqlite_master WHERE name = '" . $tablename ."';";
			$result = $this->dbconn->query($query);
			if($result->num_rows() == 0)
				$this->create_table();
		}
		else {
			$result = $this->dbconn->table_exists($this->config['cache_table']);
			if(!$result)
				$this->create_table();
		}
		
	}
	
	/**
	 * Internal method to create the table if it doesn't exist
	 * @access private
	 * @return void
	 */
	private function create_table() {
		if ( $this->type == 'sqlite' ) {
			$auto_increase = '';
		}
		else {
			$auto_increase = ' AUTO_INCREMENT';
		}
		// add dbprefix
		$tablename = $this->dbconn->dbprefix . $this->config['cache_table'];
		$query = "CREATE TABLE ". $tablename ." (cache_id INTEGER PRIMARY KEY".$auto_increase.", unique_vars TEXT, expires INT, body TEXT)";
		if ( $this->dbconn->query($query) ) {
			log_message('info', 'Last.fm cache table '. $tablename .' created');
		}
		else {
			log_message('error', 'Last.fm cache table '.$tablename. ' could not be created');
		}
	}
	
	/**
	 * Searches the database for the cache date. Returns an array if it exists or false if it doesn't 
	 * @access public
	 * @param array $unique_vars Array of variables that are unique to this piece of cached data
	 * @return string
	 */
	public function get($unique_vars) 
	{
		
		if($this->cache_enabled == TRUE ) 
		{
			$this->dbconn->select('expires, body');
			$this->dbconn->where('unique_vars', ''. htmlentities(serialize($unique_vars)));
			$result = $this->dbconn->get($this->config['cache_table'], NULL, 1);
			if ( $result->num_rows() > 0 ) {
					$row = $result->row();
					if ( $row->expires < time() ) 
					{
						$this->dbconn->delete($this->config['cache_table'], array('unique_vars' => htmlentities(serialize($unique_vars)) ) );
						return false;
					}
					else 
					{
						return unserialize(html_entity_decode($row->body));
					}
			}
			else 
			{
				// TODO: Handle error
				log_message('info', 'Last.fm cache did not find a match '. $unique_vars);
				return false;
			}
		}
		else 
		{
			log_message('info', 'Last.fm caching not enabled');
			return false;
		}
	}
	
	/**
	 * Adds new cache data to the database
	 * @access public
	 * @param array $unique_vars Array of variables that are unique to this piece of cached data
	 * @param string $body The contents of the cache to put into the database
	 * @return boolean
	 */
	public function set($unique_vars,  $body) {
		if ( $this->cache_enabled == TRUE ) {
			$expire = time() + $this->config['cache_length'];
			$this->dbconn->set('unique_vars', htmlentities(serialize($unique_vars)));
			$this->dbconn->set('expires', $expire);
			$this->dbconn->set('body', htmlentities(serialize($body)) );
			$this->dbconn->insert($this->config['cache_table']);
			
			if ( $this->dbconn->affected_rows() == 1 ) {
				return true;
			}
			else {
				// TODO: Handle error
				return false;
			}
		}
		else {
			return false;
		}
	}
	
}
