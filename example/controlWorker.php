<?php 
    
	include __DIR__.'/includes/conn.php';
	include __DIR__.'/classes/WorkerManager.php';
	include __DIR__.'/workerList.php';


$wokerManager  = WorkerManager::instance();

$wokerManager->processWorker($workers);
sleep(1);

if(isset($_SERVER['HTTP_USER_AGENT']))
	header('Location:workerStatus.php');