<?php
/*
 * Supermetrics Task
 *
 * (c) BadrSk1x
 *
 *  Api
 *  class to calculate statistics
 */
namespace App;

use App\Statistics\Operations;

class PostsStatistics extends Operations
{
    private $posts;
    private $months;
    private $weeks;
    private $users;


    /**
     * @param object $post
     *
     */
    public function __construct(array $posts)
    {
        $this->posts = $posts;
        $this->months = $this->loadMonthsStat();
        $this->weeks = $this->loadWeeksStat();
        $this->users = $this->loadUsersStat();
    }

    /**
    * Calculate the month Average character length / post
    * Calculate the longest post by character length / month
    *
    * @return object
    *
    */

    public function getMonthAverage(): object
    {
        $r = new \stdClass();
        $r->data = [];
        // we have 12 months in a year
        for ($m=1; $m<=12; $m++) {
            $avg = 0;
            $max=0;
            if (array_key_exists($m, $this->months)) {
                $posts = $this->months[$m];
                $avg = self::mean($posts);
                $max = self::max($posts);
            }
            $r->data[$m] = new \stdClass();
            $r->data[$m]->Month = date('F', mktime(0, 0, 0, $m, 10));
            $r->data[$m]->Avg   = $avg;
            $r->data[$m]->Max = $max;
        }
        return $r;
    }

    /**
    * Calculate the Total posts split by week
    *
    * @return object
    *
    */
    public function getWeeklyPosts(): object
    {
        $r = new \stdClass();
        $r->data = [];
        // we have 52 weekd in a year
        for ($w=1; $w<=52; $w++) {
            $r->data[$w] = new \stdClass();
            array_key_exists($w, $this->weeks) ? $r->data[$w] = $this->weeks[$w] : $r->data[$w] = 0;
        }
        return $r;
    }

    /**
    * Calculate the Average number of posts per user / month
    *
    * @return object
    *
    */
    public function getUserPostAverage(): object
    {
        $r = new \stdClass();
        $r->data = [];
        for ($m=1; $m<=12; $m++) {
            $avg = 0;
            $r->data[$m] = new \stdClass();
            if (array_key_exists($m, $this->users)) {
                $userdata = $this->users[$m];
                $avg = self::mean($userdata);
            }
            $r->data[$m]->Month = date('F', mktime(0, 0, 0, $m, 10));
            $r->data[$m]->Avg   = $avg;
        }
        return $r;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadMonthsStat() :array
    {
        $m = [];
        foreach ($this->posts as $post) {
            $n = $this->getDateByType('n', $post) ;
            $len = strlen($post->message);
            $m[$n][] = $len;
        }
        return $m;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadWeeksStat() :array
    {
        $ws = [];
        foreach ($this->posts as $post) {
            $w = $this->getDateByType('W', $post) ;
            array_key_exists($w, $ws) ? $ws[$w]++ : $ws[$w]=0 ;
        }
        return $ws;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadUsersStat(): array
    {
        $us = [];
        foreach ($this->posts as $post) {
            $n = $this->getDateByType('n', $post) ;
            // Check user
            $uid = $post->from_id;
            array_key_exists($n, $us) && array_key_exists($uid, $us[$n])
            ? $us[$n][$uid]++
            : $us[$n][$uid] = 0;
        }
        return $us;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getDateByType(string $type, object $post): int
    {
        return intval(date($type, strtotime($post->created_time)));
    }
}
