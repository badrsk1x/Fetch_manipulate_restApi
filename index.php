<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

use \App\App;
use \App\Post;
use \App\PostsStatistics;
use LucidFrame\Console\ConsoleTable as ConsoleTable ;

#config file
$config   = include 'config/config.php';

// step 1 : We initialize our app with the config parameters 
$app = new App(["settings" => $config]);
// step 2 : Let s fetch our posts
$posts = $app->fetchPosts();
// step 3 : Time to do some statistics
$stats = new PostsStatistics($posts);

// step 3-1 : Monthly statistics
$avgStats = $stats->getMonthAverage();
// step 3-2 : Weekly Total posts
$weeklyPosts = $stats->getWeeklyPosts();
// step 3-3 : Average user posts by month
$avgUserStats = $stats->getUserPostAverage();


    /**************************************************************************
     * show the monthly statistics in a table in console 
     **************************************************************************/

$table1 = new ConsoleTable;
$table1->addHeader('Months')
        ->addHeader('Average characters')
        ->addHeader('longest characters');
foreach($avgStats->data as $key=>$data) {
    $table1->addRow()
           ->addColumn(date('F', mktime(0, 0, 0, $key, 10)))
           ->addColumn(round($data->Avg,3))
           ->addColumn($data->Max);
}
$table1->display();


// show the weekly posts in a table in console 
$table2 = new ConsoleTable;
$table2->addHeader('Week')
      ->addHeader('Total posts');
      foreach($weeklyPosts->data as $w=>$posts)
      {
          $table2->addRow()
          ->addColumn($w)
          ->addColumn($posts);
      }
$table2->display();

// show the average user posts by month in a table in console 
$table3 = new ConsoleTable;
$table3->addHeader('Month')
      ->addHeader('Average number of posts per user');
      foreach($avgUserStats->data as $key=>$data)
      {
          $table3->addRow()
          ->addColumn(date('F', mktime(0, 0, 0, $key, 10)))
          ->addColumn(round($data->Avg,3));
      }
$table3->display();
