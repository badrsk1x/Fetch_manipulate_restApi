<?php
namespace App;

class Post extends Request
{
	protected $postUrl;
	
	public function __construct(\App\Core\container $container)
	{ 
		parent::__construct($container);
		$settings = $container->settings;
		$this->postUrl = $settings->get('PostsUrl');
	}
	
	/**
	 * get the posts
	 * @param int $page
	 * return array of posts retrieved
	 * 
	 */
	public function getPosts(int $page) : array
	{
		$requiredToken = true;
		$res =  $this->sendRequest($this->postUrl, 'GET', array('page' => $page), true);
		$posts = (array) $res->posts;
		return $posts;
	}

}
