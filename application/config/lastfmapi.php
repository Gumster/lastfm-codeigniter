<?php
	
	$config = array();
	// account
	$config['apiKey'] = '';
	$config['secret'] = '';
	$config['username'] = '';
	$config['sessionKey'] = '';
	$config['subscriber'] = '';
	/**
	  Caching
	  
	  Caching is either by a sqlite table or some other db.
	  the config key 'cache_type' indicates which.
	  
	  Sqlite requires some special handling when testing
	  for existing tables and creating new ones, so 'cache_type' 
	  is used to distinguish those needs.
	  
	  'cache_group' indicates the group name used to define
	  the database connection in database.php This is required
	  as it is not easy to retrieve $active_group from Codigniter's 
	  super object.
	  
	  'cache_table' is the name of the storage table in whatever
	  db is used. 
	  
	  NB if the table does not exist, the 'cache_table' value will
	  be used to create one of that name. If a dbprefix exists, that 
	  will be prepended to the 'cache_table' value.
	  
	**/
	$config['cache_enabled'] = TRUE; //  May be over-ridden at method call level
	$config['cache_type'] = 'sqlite';  // sqlite or db 	
	$config['cache_length'] = 1800; // In seconds. may be over-ridden at method call level
	$config['cache_table'] = 'lastfmapicache';	
	$config['cache_group'] = 'sqlite';
	// service connection
	$config['host'] = 'ws.audioscrobbler.com';
	$config['port'] = 80;
	$config['connected'] = 0;

