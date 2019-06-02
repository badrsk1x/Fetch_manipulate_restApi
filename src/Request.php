<?php
/*
 * Supermetrics Task
 *
 * (c) BadrSk1x
 *
 *  Api 
 *  Request class
 */
namespace App;

use App\Core\Container as Container;

class Request
{
    protected $api;
    protected $token;

	// Construct the class
	protected function __construct($container)
	{
		// Check for extension
		if (!extension_loaded('curl'))
		{
			throw new \Exception(_('cURL extension is required! You need to install cURL'));
        }
        // Set the ApiUrl base URL
        $settings = $container->settings;
        $this->api = $settings->get('ApiUrl');    
        $this->token = $container->token ?? null;
	}
    
   
    /**
	 * Create a request to the API with cURL
	 * 
	 * @param string $endPoint The API url to use
	 * 	endpoint to send the request to
	 * @param string $requestMethod Either POST or GET
	 * 	HTTP method to use
	 * @param array $data 
     *  parameters to pass
     * @param bool $tokenRequired 
     * if the endpoint needs token to access
	 * 
	 * @retval object
	 * 	object received in response
	 * 
	 * @throws \Exception
	 * 	On errors, with error message
	 */
    
    protected function sendRequest(string $endPoint, string $requestMethod , array $data = array(), $tokenRequired = false) : object
	{ 
        if($tokenRequired) { 
           $data['sl_token'] = $this->token;
        } 
        $curl = new Curl();
        $endPoint = $this->api.$endPoint;

        	// Set method specific settings
		switch ($requestMethod)
		{
        case 'GET':
        $response = $this->sendGet($curl, $endPoint, $data);
			break;		
		case 'POST':
			$response = $this->sendPost($curl, $endPoint, $data);
			break;	
		default:
			break;
        }
        return $response;
    }

    /**
	 * Create a HTTP POST request 
     * @param object $curl 
     * @param string $endpoint
     * @param array $data
	 * @retval object
	 * 	object received in response 
     */
    private function sendPost(object $curl, string $endPoint, array $data) : object {              
        $curl->setHeader('Content-Type', 'application/json');
        $curl->post($endPoint, $data);
        if(property_exists($curl->response, 'error')) $this->getError($curl->response->error);
        // we need only data from our response
        $response = $curl->response->data;
        return $response;
    }

    /**
	 * Create a HTTP GET request 
	 * @param object $curl 
     * @param string $endpoint
     * @param array $data
     * @retval object
	 * 	object received in response 
     */
    private function sendGet(object $curl, string $endpoint, array $data) : object {  
        $curl->get($endpoint, $data);
        if(property_exists($curl->response, 'error')) $this->getError($curl->response->error);
         // we need only data from our response
        $response = $curl->response->data;
        return $response;
    }

    /**
     *@param object error
     * show error message
     */
    public function getError($error){
        throw new \Exception($error->message);
    }

	
}