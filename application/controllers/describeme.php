<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Describeme extends CI_Controller {
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->helper('form');
		$this->form_validation->set_error_delimiters('<div class="error"><p>', '</p></div>'); 	
		
	}

    public function index()	
    {
		$data = array();
		$data['results'] = array();
		
       	// validate user input, if any
		$this->form_validation->set_rules('username', 'username', 'trim|required|xss_clean');
		
		if($this->form_validation->run() == TRUE)
		{
			$this->load->library('lastfmapi/lastfmapi');
			$data['username'] = $this->input->post('username');
					
			// Pass the array to the auth class to return a valid auth
			$auth = new lastfmApiAuth('setsession');		
			$apiClass = new lastfmApi();
			
			$userClass = $apiClass->getPackage($auth, 'user');
			
			// Setup the results array
			$results = array();
			// Create a list of tag's that we don't want
			// Mainly ones that don't describe peoples musical taste
			$badTags = array(
				'good',
				'seen live',
				'favourite',
				'favorites',
				'favorite artists',
				'favourite bands',
				'favourites',
				'want to see live',
				'uk',
				'whales',
				'my music',
				'amazing',
				'awesome',
				'english',
				'fun',
				'multiple artists under same name',
				'a few of the bands ive seen',
				'albums i own',
				'music',
				'rock gods'
			);
			
			// Setup the variables get get the users top artists
			$methodVars = array(
				'user' => $this->input->post('username')
			);
			
			// Get the users top artist (with error check)
			if ( $artists = $userClass->getTopArtists($methodVars) ) 
			{								
				// Loop through each of the users top artists
				foreach ( $artists as $artist ) {
					// Create an artists class to use
					$artistClass = $apiClass->getPackage($auth, 'artist');
					
					// Setup the variables for the artist call
					$methodVars = array(
						'artist' => $artist['name']
					);
					// Get the top tags that are givent to that artist
					$tags = $artistClass->getTopTags($methodVars);
					
					// Check that there is some tags and it is an array
					if ( count($tags) > 0 && is_array($tags) ) 
					{
						// Loop through the tags
						foreach ( $tags as $tag ) {
							// Check it's not a bad tag
							// If it is then it won't be used and the second most popular will be
							// If it isn't just the most used tag is used
							if ( !in_array(strtolower($tag['name']), $badTags) && !empty($tag['name']) ) 
							{
								// Get the previous score for the tag if it exists
								if ( !empty($results[$tag['name']]) )
									$prev = $results[$tag['name']]['value'];
								else
									$prev = 0;
								
								// Calculate the new score
								$new = $prev + $artist['playcount'];
								
								// Write this score back to the results array
								$results[$tag['name']] = array(
									'name' => $tag['name'],
									'url' => $tag['url'],
									'value' => $new
								);
								
								// Break out of the loop to only get the top tag for this artists
								break;
							}
						}
					}
				} 
				}
				
				// Create a compare function which puts the results in descending order based on their score
				function compare($x, $y) {
					if ( $x['value'] == $y['value'] ) {
						return 0;
					}
					else if ( $x['value'] < $y['value'] ) {
						return 1;
					}
					else {
						return -1;
					}
				}
				// Do the sort
				usort($results, 'compare');
				
				// Loops through the results to get a total score to use in working out a percentage
				$total = 0;
				foreach ( $results as $result ) {
					$total = $total + $result['value'];
				}
				
				$data['results'] = $results;
				$data['total'] = $total;
		}
		
		$this->load->view('describeme', $data);
    }

}

/* End of file describeme.php */
/* Location: ./application/controllers/describeme.php */