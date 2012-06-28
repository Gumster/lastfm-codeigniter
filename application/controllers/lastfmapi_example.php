<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lastfmapi_example extends CI_Controller {
	
	private $apiClass;
	private $auth;

	function __construct() 
	{
		parent::__construct();
		
		$this->load->library('lastfmapi/lastfmapi');
		
		// Pass the array to the auth class to return a valid auth
		$this->auth = new lastfmApiAuth('setsession');
		
		$this->apiClass = new lastfmApi();
					
	}
	
	
	public function index()
	{			
		$this->_albuminfo();
		echo '<hr>';
		$this->_lovedtracks();
	}	
	
	function _albuminfo()
	{			
		// Call for the album package class with auth data
		$albumClass = $this->apiClass->getPackage($this->auth, 'album');
		
		// Setup the variables
		$methodVars = array(
			'artist' => 'Black Sabbath',
			'album' => 'Masters of Reality'
		);
		
		if ( $album = $albumClass->getInfo($methodVars) ) {
			// Success
			echo '<h2>Album info</h2>';
			echo '<pre>';
			print_r($album);
			echo '</pre>';
		}
		else {
			// Error
			die('<b>Error '.$albumClass->error['code'].' - </b><i>'.$albumClass->error['desc'].'</i>');
		}	
	}
	
	function _lovedtracks()
	{
		$userClass = $this->apiClass->getPackage($this->auth, 'user');
		// Setup the variables
		$methodVars = array(
			'user' => 'Gumstar',
			'limit' => 5
		);

		if ( $tracks = $userClass->getLovedTracks($methodVars) ) {
			echo '<h2>Loved tracks</h2>';
			echo '<pre>';
			print_r($tracks);
			echo '</pre>';
		}
		else {
			die('<b>Error '.$userClass->error['code'].' - </b><i>'.$userClass->error['desc'].'</i>');
		}	
	}

}
/* End of file lastfmapi_example.php */
/* Location: ./application/controllers/lastfmapi_example.php */