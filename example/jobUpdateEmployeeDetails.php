<?php
require_once __DIR__.'/includes/common.php';
include __DIR__.'/classes/ImportFunction.php';

// include 'classes/Common.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;



// ImportFunction::getEmployeeExcelUpdate(1,3);
// die;print_r($query);
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('hello', false, false, false, false);
$myPId = getmypid();

$redis = new \Redis;
$redis->pconnect('127.0.0.1');

$file = basename(__FILE__, '.php');
$processKey = $file . ':' . $myPId;
$workerDetails = $redis->hGetAll($processKey);

// if (count($workerDetails) == 0) {
//     exec('kill -9 ' . $myPId);
// }

$callback = function ($msg) use ($redis, $myPId, $processKey, $connection,$channel) {
    $workerDetails = $redis->hGetAll($processKey);
    $workerDetails['iteration']++;

    $redis->hSet($processKey, 'iteration', $workerDetails['iteration']);
    $redis->hSet($processKey, 'jobStart', time());
    $redis->hSet($processKey, 'state', 1);

    ImportFunction::getEmployeeExcelUpdate($msg->body);
    // sleep(10);

    $redis->hSet($processKey, 'jobStart', 0);
    $redis->hSet($processKey, 'state', 0);

    if ($workerDetails['iteration'] >= $workerDetails['maxIteration']) {
        $channel->close();
        $connection->close();
        exec('php controlWorker.php');
        // exec('kill -9 ' . $myPId);
        
        exit;
    }
};
$channel->basic_consume('hello', '', false, true, false, false, $callback);
// lisen:

while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
// goto lisen; 

