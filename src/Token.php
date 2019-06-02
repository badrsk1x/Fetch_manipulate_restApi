<?php
namespace App;

use App\Resquest;

class Token extends Request
{
	private $id;
	private $email;
	private $name;
	protected $registerUrl;
	public $token;
	
	public function __construct(\App\Core\container $container)
	{ 
		parent::__construct($container);
		$settings = $container->settings;
		
		$this->id = $settings->get('ClientId');	
		$this->email = $settings->get('ClientEmail');
		$this->name = $settings->get('ClientName');
		$this->registerUrl = $settings->get('RegisterUrl');	
		$this->token = FALSE;
    }

    /**
	 * Build the request
	 * 
	 * @retval string token
	 * @throws \Exception
	 */
	public function getToken() : string
	{
		
		if ($this -> token !== FALSE)
		{
			return $this -> token;
        }
        
		$dataRegister = [
            "client_id" => $this->id,
            "email" => $this->email,
            "name" => $this->name,
        ];

		$res = $this->sendRequest($this->registerUrl, 'POST', $dataRegister);    
        $this->token = $res->sl_token;
		return $this->token;
	}
}