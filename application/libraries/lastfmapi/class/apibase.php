<?php
/**
 * File that contains all base methods used by all api calls
 * @package base
 */
/**
 * Stores the methods used by all api calls
 * @package base
 */
class lastfmApi {	

	/*
	 * Stores error details
	 * @access public
	 * @var array has two elements: <i>code</i> and <i>desc</i> which stores the error code and description respectivly
	 */
	public $error;
	/*
	 * Stores the connection status
	 * @access public
	 * @var boolean
	 */
	public $connected;
	
	/*
	 * Stores the host name
	 * @access private
	 * @var string
	 */
	private $host;
	/*
	 * Stores the port number
	 * @access private
	 * @var string
	 */
	private $port;
	/*
	 * Stores the raw api call response
	 * @access private
	 * @var string
	 */
	private $response;
	/*
	 * Stores the socket class
	 * @access private
	 * @var class
	 */
	private $socket;
	/*
	 * Stores the cache class
	 * @access private
	 * @var class
	 */
	private $cache;
	/*
	 * Stores the config
	 * @access private
	 * @var class
	 */
	public $config;
	
	
	function __construct() 
	{
		$this->_obj =& get_instance();
		$this->_obj->load->config('lastfmapi');
		// static config values
		$this->host = $this->_obj->config->item('host');
		$this->port = $this->_obj->config->item('port');
		$this->connected = $this->_obj->config->item('connected');
	}

	function setCacheVars()
	{
		// setup cache vars 
		$this->config = array();
		$this->config['cache_length'] = $this->_obj->config->item('cache_length'); 
		$this->config['cache_type'] = $this->_obj->config->item('cache_type');	
		$this->config['cache_table'] = $this->_obj->config->item('cache_table');
		$this->config['cache_enabled'] = $this->_obj->config->item('cache_enabled');
		$this->config['cache_group'] = $this->_obj->config->item('cache_group');
	}
	/*
	 * Setup the socket to get the raw api call return
	 * @access private
	 * @return boolean
	 */
	function setup() 
	{			
		$this->setCacheVars();
		$this->socket = new lastfmApiSocket($this->host, $this->port);
		if ( !$this->socket->error_number && !$this->socket->error_string ) {
				$this->connected = 1;
				return true;
			}
		else {
			$this->handleError(99, $this->socket->error_string);
			return false;
		}
		
	}
	
	/*
	 * Turns the raw response into an xml object
	 * @access private
	 * @return object
	 */
	private function process_response() {
		$xmlstr = '';
		$record = 0;
		foreach ( $this->response as $line ) {
			if ( $record == 1 ) {
				$xmlstr .= $line;
			}
			elseif( substr($line, 0, 1) == '<' ) {
				$record = 1;
			}
			elseif ( preg_match('/^HTTP\/1.[0-9]{1} ([4-9]{1}[0-9]{2}.*)/', $line, $matches) ) {
        		$this->handleError(99, $this->host.': Service not available (' . trim($matches[1]) . ')');
        		return;
      		}
		}
		
		try {
			libxml_use_internal_errors(TRUE);
			$xml = new SimpleXMLElement($xmlstr);
		} 
		catch (Exception $e) {
			// Crap! We got errors!!!
			$errors = libxml_get_errors();
			$error = (sizeof($errors) > 0) ? $errors[0] : new libxmlerror;
			$msg = 'undefined libxmlerror';
			if(!empty($error->message))
				$msg = $error->message;
			$this->handleError(95, 'SimpleXMLElement error: '.$e->getMessage().': '.$msg);
		}
		
		if ( !isset($e) ) {
			// All is well :)
			return $xml;
		}
	}
	
	/*
	 * Used in api calls that do not require write access. Returns an xml object
	 * @access protected
	 * @return object
	 */
	protected function apiGetCall($vars) {
		$this->setup();
		// method vars may over-ride 2 cache settings: cache_length & cache_enabled
		if(isset($vars['cache_length']))
			if(is_numeric($vars['cache_length']))
				$this->config['cache_length'] = $vars['cache_length'];
		if(isset($vars['cache_enabled']))	
			$this->config['cache_enabled'] = $vars['cache_enabled'];	
				
		if ( $this->connected == 1 ) 
		{
			$this->cache = new lastfmApiCache($this->config);
			if ( !empty($this->cache->error) ) {
				$this->handleError(96, $this->cache->error);
				return false;
			}
			else {
				if ( $cache = $this->cache->get($vars) ) {
					// Cache exists
					$this->response = $cache; 
				}
				else {
					// Cache doesn't exist
					$url = '/2.0/?';
					foreach ( $vars as $name => $value ) {
						$url .= trim(urlencode($name)).'='.trim(urlencode($value)).'&';
					}
					$url = substr($url, 0, -1);
					$url = str_replace(' ', '%20', $url);
					
					$out = "GET ".$url." HTTP/1.0\r\n";
					$out .= "Host: ".$this->host."\r\n";
					$out .= "\r\n";
					$this->response = $this->socket->send($out, 'array');
					$this->cache->set($vars, $this->response);
				}
				// return XML
				return $this->process_response();
			}
		}
		else {
			return false;
		}
	}
	
	/*
	 * Used in api calls that require write access. Returns an xml object
	 * @access protected
	 * @return object
	 */
	protected function apiPostCall($vars, $return = 'bool') {
		$this->setup();
		if ( $this->connected == 1 ) {
			$url = '/2.0/';
			
			$data = '';
			foreach ( $vars as $name => $value ) {
				$data .= trim($name).'='.trim($value).'&';
			}
			$data = substr($data, 0, -1);
			$data = str_replace(' ', '%20', $data);
			
			$out = "POST ".$url." HTTP/1.1\r\n";
			$out .= "Host: ".$this->host."\r\n";
			$out .= "Content-Length: ".strlen($data)."\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "\r\n";
			$out .= $data."\r\n";
			$this->response = $this->socket->send($out, 'array');
			// return XML
			return $this->process_response();
		}
		else {
			return false;
		}
	}
	
	/*
	 * Processes and error and writes to the public variable $error
	 * @access protected
	 * @return void
	 */
	protected function handleError($error = '', $customDesc = '') {
		if ( !empty($error) && is_object($error) ) {
			// Fail with error code
			$this->error['code'] = $error['code'];
			$this->error['desc'] = $error;
		}
		elseif( !empty($error) && is_numeric($error) ) {
			// Fail with custom error code
			$this->error['code'] = $error;
			$this->error['desc'] = $customDesc;
		}
		else {
			//Hard failure
			$this->error['code'] = 0;
			$this->error['desc'] = 'Unknown error';
		}
	}
	
	/*
	 * Generates the api signature for use in api calls that require write access
	 * @access protected
	 * @return string
	 */
	protected function apiSig($secret, $vars) {
		ksort($vars);
		
		$sig = '';
		foreach ( $vars as $name => $value ) {
			$sig .= $name.$value;
		}
		$sig .= $secret;
		$sig = md5($sig);
		
		return $sig;
	}
	
	/*
	 * Used to return the correct package to allow access to the api calls for that package
	 * @access public
	 * @return class
	 */
	public function getPackage($auth, $package, $config = NULL) 
	{

		if ( is_object($auth) ) {
			if ( !empty($auth->apiKey) && !empty($auth->secret) && !empty($auth->username) && !empty($auth->sessionKey) && ($auth->subscriber == 0 || $auth->subscriber == 1) ) {
				$fullAuth = 1;
			}
			elseif ( !empty($auth->apiKey) ) {
				$fullAuth = 0;
			}
			else {
				$this->handleError(91, 'Invalid auth class was passed to lastfmApi. You need to have at least an apiKey set');
				return FALSE;
			}
		}
		else {
			$this->handleError(91, 'You need to pass a lastfmApiAuth class as the first variable to this class');
			return FALSE;
		}
		
		if ( $package == 'album' || $package == 'artist' || $package == 'event' || $package == 'geo' || $package == 'group' || $package == 'library' || $package == 'playlist' || $package == 'radio' || $package == 'tag' || $package == 'tasteometer' || $package == 'track' || $package == 'user' || $package == 'venue' ) {
			$className = 'lastfmApi'.ucfirst($package);
			return new $className($auth, $fullAuth, $config);
		}
		else {
			$this->handleError(91, 'The package name you passed was invalid');
			return FALSE;
		}
	}
}