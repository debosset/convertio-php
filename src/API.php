<?php
/**
 * This file contains code about \Convertio\API class
 */
namespace Convertio;

use Exception;
use Convertio\Exceptions\APIException;
use Convertio\Exceptions\CURLException;

/**
 * Base Wrapper to manage http connections with Convertio API using curl
 */
class API
{
    /**
     * Url to communicate with Convertio API
     * @var string
     */
    private $api_host = 'api.convertio.co';
    /**
     * Protocol (http or https) to communicate with Convertio API
     * @var string
     */
    private $api_protocol = 'https';
    /**
     * API Key of the current application
     * @var string
     */
    private $api_key = null;

    /**
     * HTTP Requests timeout in seconds
     * @var integer
     */
    private $http_timeout = 3;

    /**
     * Construct a new wrapper instance
     *
     * @param string $api_key API Key of your application.
     * You can get your API Key on https://convertio.co/api/
     *
     * @throws \Convertio\Exceptions\APIException if api key is missing or empty
     */
    public function __construct($api_key)
    {
        if (empty($api_key)) {
            throw new APIException("API Key parameter is empty");
        }
        $this->api_key = $api_key;
    }

    /**
     * This is the main method of this wrapper. It will
     * sign a given query and return its result.
     *
     * @param string $method HTTP method of request (GET,POST,PUT,DELETE)
     * @param string $path relative url of API request
     * @param string $content body of the request
     * @return mixed
     *
     * @throws \Exception
     * @throws \Convertio\Exceptions\APIException if the Convertio API returns an error
     * @throws \Convertio\Exceptions\CURLException if there is a general HTTP / network error
     */
    private function _rawCall($method, $path, $content = null)
    {
        $url = $path;
        if (strpos($path, '//') === 0) {
            $url = $this->api_protocol . ":" . $path;
        } elseif (strpos($url, 'http') !== 0) {
            $url = $this->api_protocol . '://' . $this->api_host . $path;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->http_timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->http_timeout);

        if ($method == 'GET') {

        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        } elseif (gettype($content) == 'resource' && $method == 'PUT') {
            $fstat = fstat($content);
            curl_setopt($curl, CURLOPT_PUT, TRUE);
            curl_setopt($curl, CURLOPT_INFILE, $content);
            curl_setopt($curl, CURLOPT_INFILESIZE, $fstat['size']);
        } elseif (is_array($content) && ($method == 'POST')) {
            $content['apikey'] = $this->api_key;
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($content));
        }

        $response = curl_exec($curl);
        $response_info = curl_getinfo($curl);
        $response_errno = curl_errno($curl);
	    $response_error = curl_error($curl);
	    curl_close($curl);
	    $curl = null;

        if (isset($response_info['content_type']) && strpos($response_info['content_type'], 'application/json') !== FALSE) {
        	try {
	            $data = json_decode($response, true);
	        } catch (\Exception $e) {
	            if (JSON_ERROR_NONE !== json_last_error()) {
 		  	        throw new \Exception('Error parsing JSON response');
	            }
                throw new \Exception($e);
	        }

            if ($data['status'] == 'error')
            {
		        throw new Exceptions\APIException($data['error'], $data['code']);
            }

            return $data;
        } elseif ($response_errno === 0) {
            return $response;
        } else
        {
	        throw new Exceptions\CURLException($response_error, $response_errno);
        }
    }

    /**
     * Wrap call to Convertio APIs for GET requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @return mixed
     *
     * @throws \Exception
     * @throws \Convertio\Exceptions\APIException if the Convertio API returns an error
     * @throws \Convertio\Exceptions\CURLException if there is a general HTTP / network error
     *
     */
    public function get($path, $content = null)
    {
        return $this->_rawCall("GET", $path, $content);
    }

    /**
     * Wrap call to Convertio APIs for POST requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @return mixed
     *
     * @throws \Exception
     * @throws \Convertio\Exceptions\APIException if the Convertio API returns an error
     * @throws \Convertio\Exceptions\CURLException if there is a general HTTP / network error
     *
     */
    public function post($path, $content)
    {
        return $this->_rawCall("POST", $path, $content);
    }

    /**
     * Wrap call to Convertio APIs for PUT requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @return mixed
     *
     * @throws \Exception
     * @throws \Convertio\Exceptions\APIException if the Convertio API returns an error
     * @throws \Convertio\Exceptions\CURLException if there is a general HTTP / network error
     *
     */
    public function put($path, $content)
    {
        return $this->_rawCall("PUT", $path, $content);
    }

    /**
     * Wrap call to Convertio APIs for DELETE requests
     *
     * @param string $path path ask inside api
     * @param string $content content to send inside body of request
     * @return mixed
     *
     * @throws \Exception
     * @throws \Convertio\Exceptions\APIException if the Convertio API returns an error
     * @throws \Convertio\Exceptions\CURLException if there is a general HTTP / network error
     *
     */
    public function delete($path, $content = null)
    {
        return $this->_rawCall("DELETE", $path, $content);
    }

    /**
     * Get the current API Key
     *
     * @return string
     */
    public function getAPIKey()
    {
        return $this->api_key;
    }


}