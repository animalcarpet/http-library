<?php
/**
 * LightPHP Framework
 * LitePHP is a framework that has been designed to be lite waight, extensible and fast.
 * 
 * @author Robert Pitt <robertpitt1988@gmail.com>
 * @category core
 * @copyright 2013 Robert Pitt
 * @license GPL v3 - GNU Public License v3
 * @version 1.0.0
 */
!defined('SECURE') && die('Access Forbidden!');

/**
 * HTTP Request class
 */
class HTTP_Library
{
	/**
	 * Useragent String
	 * @var string
	 */
	protected $_useragent = "LitePHP/HTTP Request Library";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		/**
		 * Validate that curl is installed
		 */
		if(function_exists('curl_init') === false)
		{
			throw new Exception("Curl is required for the HTTP library");
		}

		/**
		 * Make sure pecl_http is installed
		 */
		if(function_exists('http_build_str') === false)
		{
			throw new Exception("pecl_http is required for the HTTP library");
		}
	}

	/**
	 * Perform a GET Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function get($url, $query = array(), $headers = array())
	{
		return $this->_request($url, 'GET', $query, array(), $headers);
	}

	/**
	 * Perform a POST Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $data    Key/Value list of POST Values,
	 *                         you can also supply a file by using @/path/to/file
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function post($url, $query = array(), $data = array(), $headers = array())
	{
		return $this->_request($url, 'POST', $query, $data, $headers);
	}

	/**
	 * Perform a PUT Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $data    Key/Value list of POST Values,
	 *                         you can also supply a file by using @/path/to/file
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function put($url, $query = array(), $data = array(), $headers = array())
	{
		return $this->_request($url, 'PUT', $query, $data, $headers);
	}

	/**
	 * Perform a DELETE Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function delete($url, $query = array(), $headers = array())
	{
		return $this->_request($url, 'PUT', $query, array(), $headers);
	}

	/**
	 * Perform a OPTIONS Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function options($url, $query = array(), $headers = array())
	{
		return $this->_request($url, 'OPTIONS', $query, array(), $headers);
	}


	/**
	 * Perform a TRACE Request
	 * @param  String $url     HTTP endpoint to connect to
	 * @param  array  $query   Key/Value list of query parameters
	 * @param  array  $headers Key/Value list of headers
	 * @return Array           Response Object
	 */
	public function trace($url, $query = array(), $headers = array())
	{
		return $this->_request($url, 'TRACE', $query, array(), $headers);
	}

	/**
	 * Perform a CURL based request to an endpoint.
	 * @param  string $url    	Target location
	 * @param  string $method 	HTTP Method used for the connection
	 * @param  array  $query  	Key/Value pair of parameters for the url
	 * @param  array  $data   	Key/Value pair of parameters to be sent as the payload
	 * @param  array  $headers 	Key/Value pair of headers
	 * @return array         	Response array that contains headers and the body.
	 */
	public function _request($url, $method = '', $query = array(), $data = array(), $headers = array())
	{
		/**
		 * Parse the url into its components
		 */
		$components = parse_url($url);

		/**
		 * Merge the query entities if required.
		 */
		if(isset($components['query']))
		{
			/**
			 * Parse the query string into a key/value pair for merging
			 */
			parse_str($components['query'], $origQueryString);

			/**
			 * Merge and regenerate teh query string for the url
			 */
			$query = http_build_str(array_merge($origQueryString, $query));
		}

		/**
		 * Set the query to the components
		 */
		$components['query'] = http_build_query($query);

		/**
		 * Rebuild the new url
		 */			
		$url = http_build_url($components);

		/**
		 * Create a new curl object for this url
		 */
		$handle = curl_init();

		/**
		 * Create the defualt curl options.
		 */
		$options = array(
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_URL 			=> $url,
			CURLOPT_USERAGENT		=> $this->_useragent,
			CURLOPT_HTTPHEADER		=> (array)$headers,
		);

		/**
		 * Switch what method we are attempting to perform this operation as.
		 */
		switch (strtoupper($method))
		{
			case 'GET':
			break;
			case 'POST':
				$options[CURLOPT_POST] 			= true;
				/**
				 * @todo we need to find an actual fix for this since 
				 *       we have to use a pseudo fix to pass in nested arrays through post
				 */
				if(isset($headers['Content-Type']) && $headers['Content-Type'] == "multipart/form-data"){
					

					if(class_exists("CURLFile")){
						foreach ($data as $key => $value) {
							if($value[0] == '@'){
								$data[$key] = new CurlFile(substr($value, 1));
							}
						}
					}
				
					$options[CURLOPT_POSTFIELDS] = $data;
				}else{
					$options[CURLOPT_POSTFIELDS] 	= str_replace(array('%5B','%5D'), array('[',']'), http_build_query($data));
				}

			break;
			case 'PUT':
			break;
			case 'DELETE':
			break;
			case 'TRACE':
			break;
		}

		/**
		 * Set the options to the handle
		 */
		curl_setopt_array($handle, $options);

		/**
		 * Execute the response and return the error array if it has failed.
		 */
		if(!($response = curl_exec($handle)))
		{
			return array(
				"error" => curl_errno($handle),
				"no"	=> curl_errno($handle)
			);
		}

		/**
		 * Get the information of the request
		 */
		$information = curl_getinfo($handle);

		/**
		 * Close the curl handle
		 */
		curl_close($handle);

		/**
		 * Return an array of the infomration and the response.
		 */
		return array(
			"information" 	=> $information,
			"response" 		=> $response
		);
	}
}