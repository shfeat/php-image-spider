<?php
require __DIR__.'/init.php';

//php simple.php atfield
//nohup php simple.php atfield 1 10 &

$site = isset($argv[1])? $argv[1] : 'atfield';
$start = isset($argv[2])? $argv[2] : 0;
$end = isset($argv[3])? $argv[3] : 0;

$spider = new App\Libs\Spider($site);
$spider->run($start, $end);
echo 'spider done';
