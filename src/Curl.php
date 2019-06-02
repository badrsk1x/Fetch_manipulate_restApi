<?php
namespace App;

class Curl
{
    const VERSION = '0.0.1';
    const DEFAULT_TIMEOUT = 30;
    public $curl;
    public $headers;
    public $url = null;
    public $jsonDecoder = null;
    public $responseCookies = array();
    public $rawResponse = null;
    public $curlError = false;
    public $curlErrorCode = 0;
    public $curlErrorMessage = null;
    public $attempts = 0;
    public $retryDecider ;
    public $retries = 0;
    public $remainingRetries = 0;
    public $jsonDecoderArgs;
    public $httpStatusCode;
    public $httpError;
    public $headerCallbackData;
    public $error;
    public $errorCode;
    public $response;
    public $httpErrorMessage;
    public $successCallback = null;
    public $errorCallback = null;
    public $completeCallback = null;
    public $responseHeaders;
    public $options;
    public $id;

    private $jsonPattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';
	
	 /**
     * Construct
     *
     * @access public
     * @param  $base_url
     * @throws \ErrorException
     */
    public function __construct($base_url = null)
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        $this->curl = curl_init();
        if ($this->curl === FALSE)
		{
			throw new \Exception(_('cURL error: Initialization failed'));
		}
		
        $this->initialize($base_url);
    }

    /**
     * Set Header
     *
     * Add extra header to include in the request.
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

     /**
     * Post
     *
     * @access public
     * @param  $url
     * @param  $data
     */
    public function post($url, $data = '')
    { 
        $this->setUrl($url);
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

     /**
    * Get
    *
    * @access public
    * @param  $url
    * @param  $data
    *
    * @return mixed Returns the value provided by exec.
    */
   public function get($url, $data = array())
   { 
       $this->setUrl($url, $data);
       $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
       $this->setOpt(CURLOPT_HTTPGET, true);
       return $this->exec();
   }

    /**
     * Build Post Data
     *
     * @access public
     * @param  $data
     *
     * @return array|string
     * @throws \ErrorException
     */
    public function buildPostData($data)
    {
        // Return JSON-encoded string when the request's content-type is JSON and the data is serializable.
        if (isset($this->headers['Content-Type']) &&
            preg_match($this->jsonPattern, $this->headers['Content-Type']) &&
            (
                is_array($data) ||
                (
                    is_object($data) &&
                    interface_exists('JsonSerializable', false) &&
                    $data instanceof \JsonSerializable
                )
            )) {
            $data = \App\Core\Encoder::encodeJson($data);
        } elseif (is_array($data)) {
            /*
             in the test task, mentioned that only json will be used, that`s why i did not code this part
            but it have to be done in real project 
            TO-DO 
            */
        }

        return $data;
    }

    /**
     * Set Default JSON Decoder
     *
     * @access public
     * @param  $assoc
     * @param  $depth
     * @param  $options
     */
    public function setDefaultJsonDecoder()
    { 
        $this->jsonDecoder = '\App\Core\Decoder::decodeJson';
        $this->jsonDecoderArgs = func_get_args();
    }

     /**
     * Exec
     *
     * @access public
     * @param  $ch
     *
     * @return mixed Returns the value provided by parseResponse.
     */
    public function exec($ch = null)
    {
        $this->attempts += 1;
        $this->setDefaultJsonDecoder();

  
        if ($ch === null) {
            $this->responseCookies = array();
            $this->rawResponse = curl_exec($this->curl);
            $this->curlErrorCode = curl_errno($this->curl);
            $this->curlErrorMessage = curl_error($this->curl);
        } else {
            $this->rawResponse = curl_multi_getcontent($ch);
            $this->curlErrorMessage = curl_error($ch);
        }

        $this->curlError = $this->curlErrorCode !== 0;
        // Include additional error code information in error message when possible.
        if ($this->curlError && function_exists('curl_strerror')) {
            $this->curlErrorMessage =
                curl_strerror($this->curlErrorCode) . (
                    empty($this->curlErrorMessage) ? '' : ': ' . $this->curlErrorMessage
                );
        }

        $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
        $this->httpError = in_array(floor($this->httpStatusCode / 100), array(4, 5));
        $this->error = $this->curlError || $this->httpError;
        $this->errorCode = $this->error ? ($this->curlError ? $this->curlErrorCode : $this->httpStatusCode) : 0;
        $this->response = $this->parseResponse($this->rawResponse);

        $this->httpErrorMessage = '';
        if ($this->error) {
            if (isset($this->responseHeaders['Status-Line'])) {
                $this->httpErrorMessage = $this->responseHeaders['Status-Line'];
            }
        }

        if ($this->attemptRetry()) {
            return $this->exec($ch);
        }

        $this->execDone();

        return $this->response;
    }

    public function execDone()
    {
        if ($this->error) {
            $this->call($this->errorCallback);
        } else {
            $this->call($this->successCallback);
        }

        $this->call($this->completeCallback);
    }

    /**
     * Attempt Retry if the connection was lost
     *
     * @access public
     */
    public function attemptRetry()
    {
        $attempt_retry = false;
        if ($this->error) {
            if ($this->retryDecider === null) {
                $attempt_retry = $this->remainingRetries >= 1;
            } else {
                $attempt_retry = call_user_func($this->retryDecider, $this);
            }
            if ($attempt_retry) {
                $this->retries += 1;
                if ($this->remainingRetries) {
                    $this->remainingRetries -= 1;
                }
            }
        }
        return $attempt_retry;
    }


    /**
     * Parse Response
     *
     * @access private
     * @param  $response_headers
     * @param  $raw_response
     *
     * @return mixed
     *   If the response content-type is json:
     *     Returns the json decoder's return value: A stdClass object when the default json decoder is used.
     *   Other response content-type was not mentioned in the task but i ts good to have:
     *     To do - Returns the xml decoder's return value: A SimpleXMLElement object when the default xml decoder is used.
     *           - Returns the original raw response unless a default decoder has been set.
     *           -  Returns the original raw response.
     */
    private function parseResponse($raw_response)
    {
        $response = $raw_response;
                    $args = $this->jsonDecoderArgs;
                    array_unshift($args, $response);
                    $response = call_user_func_array($this->jsonDecoder, $args);                
        return $response;
    }

    /**
     * Get Info
     *
     * @access public
     * @param  $opt
     *
     * @return mixed
     */
    public function getInfo($opt = null)
    {
        $args = array();
        $args[] = $this->curl;

        if (func_num_args()) {
            $args[] = $opt;
        }

        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * Get Opt
     *
     * @access public
     * @param  $option
     *
     * @return mixed
     */
    public function getOpt($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }


     /**
     * Call
     *
     * @access public
     */
    public function call()
    {
        $args = func_get_args();
        $function = array_shift($args);
        if (is_callable($function)) {
            array_unshift($args, $this);
            call_user_func_array($function, $args);
        }
    }

      /**
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     *
     * @return boolean
     */
    public function setOpt($option, $value)
    {
        
        $success = curl_setopt($this->curl, $option, $value);
        if ($success) {
            $this->options[$option] = $value;
        }
        return $success;
    }

     /**
     * Set User Agent
     *
     * @access public
     * @param  $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     * Set Default User Agent
     * In some cases, servers will disallow requests that contain unidentified user agents
     *
     * @access public
     */
    public function setDefaultUserAgent()
    {
        $user_agent = 'Curl API Supermetrics' . self::VERSION;
        $user_agent .= ' PHP/' . PHP_VERSION;
        $curl_version = curl_version();
        $user_agent .= ' curl/' . $curl_version['version'];
        $this->setUserAgent($user_agent);
    }

      /**
     * Set Default Timeout
     *
     * @access public
     */
    public function setDefaultTimeout()
    {
        $this->setTimeout(self::DEFAULT_TIMEOUT);
    }


    /**
     * Set Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setTimeout($seconds)
    {
        $this->setOpt(CURLOPT_TIMEOUT, $seconds);
    }
     /**
     * Set Url
     *
     * @access public
     * @param  $url
     * @param  $mixed_data
     */
    public function setUrl($url, $mixed_data = '')
    {
        $built_url = $this->buildUrl($url, $mixed_data);

        if ($this->url === null) {
            $this->url = (string)new \App\Core\Url\Url($built_url);
        } else {
            $this->url = (string)new \App\Core\Url\Url($this->url, $built_url);
        }

        $this->setOpt(CURLOPT_URL, $this->url);
    }

    /**
     * Build Url
     *
     * @access private
     * @param  $url
     * @param  $mixed_data
     *
     * @return string
     */
    private function buildUrl($url, $mixed_data = '')
    {
        $query_string = '';
        if (!empty($mixed_data)) {
            $query_mark = strpos($url, '?') > 0 ? '&' : '?';
            if (is_string($mixed_data)) {
                $query_string .= $query_mark . $mixed_data;
            } elseif (is_array($mixed_data)) {
                $query_string .= $query_mark . http_build_query($mixed_data, '', '&');
            }
        }
        return $url . $query_string;
    }
   

    /**
     * Initialize
     *
     * @access private
     * @param  $base_url
     */
    private function initialize($base_url = null)
    {
        $this->id = uniqid('', true);
        $this->setDefaultUserAgent();
        $this->setDefaultTimeout();
        $this->setOpt(CURLINFO_HEADER_OUT, true);

        // Create a placeholder to temporarily store the header callback data.
        $header_callback_data = new \stdClass();
        $header_callback_data->rawResponseHeaders = '';
        $header_callback_data->responseCookies = array();
        $this->headerCallbackData = $header_callback_data;
        $this->setOpt(CURLOPT_HEADERFUNCTION, createHeaderCallback($header_callback_data));

        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setUrl($base_url);
    }
}

/**
 * Create Header Callback
 *
 * Gather headers and parse cookies as response headers are received. Keep this function separate from the class so that
 * unset($curl) automatically calls __destruct() as expected. Otherwise, manually calling $curl->close() will be
 * necessary to prevent a memory leak.
 *
 * @param  $header_callback_data
 *
 * @return callable
 */
function createHeaderCallback($header_callback_data) {
    return function ($ch, $header) use ($header_callback_data) {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
            $header_callback_data->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        }
        $header_callback_data->rawResponseHeaders .= $header;
        return strlen($header);
    };
}

