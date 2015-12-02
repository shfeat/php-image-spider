<?php
require __DIR__.'/init.php';

$site = 'atfield';
$spider = new App\Libs\Spider($site);
$spider->run();
//$spider->run(1, 10);
echo 'spider done';

//php simple.php atfield
//nohup php simple.php atfield > images.log &
