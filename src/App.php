<?php
/*
 * Supermetrics Task
 *
 * @author Badr <badrsk1x#gmail.com> (c) BadrSk1x
 *
 *  This is the primary class with which to instantiate and configure
 */
namespace App;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use Exception;
use App\Core\Container as Container;

class App
{
    public $settings = null;
    public $container = null;
    /**
     * Create new application
     *
     * @var ContainerInterface
     *
     * @param ContainerInterface|array $container Either a ContainerInterface or an associative array of app settings
     * @throws InvalidArgumentException when no container is provided that implements ContainerInterface
     */

    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }
        if (!$container instanceof ContainerInterface) {
            throw new InvalidArgumentException('Expected a ContainerInterface');
        }
        $this->container = $container;
        $this->settings = $this->container->get('settings');

        //Let`s find our token
        $token = new \App\Token($this->container);
        $this->container->token =  $token->getToken();
    }

    /**
    * Enable access to the DI container by consumers of $app
    *
    * @return ContainerInterface
    */
    public function getContainer()
    {
        return $this->container;
    }

    /**
    * Receive the posts
    * @param int $page
    * return array of posts retrieved
    *
    * @throws \Exception
    */
    public function fetchPosts() : array
    {
        $container = $this->getContainer();
        $post = new \App\Post($this->getContainer());
        $postPages = $this->settings->get('PostPages');
        $posts = [];
        for ($i = 1; $i <= $postPages; $i++) {
            $posts = array_merge($posts, $post->getPosts($i));
        }
        return $posts;
    }
}
