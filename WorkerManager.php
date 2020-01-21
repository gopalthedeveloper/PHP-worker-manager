<?php

/*
*Worker manger to process multiple worker instances
*/
class WorkerManager
{
    const STATE_IDLE = 0;
    const STATE_RUNNING = 1;
    //redis obj variable
    public $redis;

    //redis
    protected $redisExtraParams = [];

    /*
    *static instance variable of self cass
    */
    public static $_instance = null;

    protected $directory = __DIR__.'/../';

    protected $freePersent = 80;

    /*
    *initate default functions
    */
    function __construct()
    {
        $this->redis = new \Redis;
        $this->redis->pconnect('127.0.0.1');
    }

    /*
    *static function to return self instance 
    */
    public static function instance()
    {
        if(self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function getStatuOfProcess($pid) 
    {
        $cmd = 'ps -o pid,%cpu,%mem,state,start -p '.$pid;
        exec($cmd,$op);
        return reset($op);
    }

    /*
    *Execute the seperate thread and stores the process in redis
    */
    protected function startProcess($fileName)
    {
        // $cmd = 'php '.$this->directory.$fileName.'.php > /tmp/err & echo $!';
        $cmd = 'nohup php '.$this->directory.$fileName.'.php >> /dev/null & echo $!';
        
        // $cmd = 'php '.$this->directory.$fileName.'.php > /tmp/cron/error  & echo $!';
        
        $this->log($cmd);
        exec($cmd,$op);
        $op = reset($op);
        $option = [
            'pid'   =>  $op,
            'state' =>  0,
            'iteration' =>  0,
            'exestart'  =>  time(),
            'jobStart'  =>  0,            
        ];

        $extraParam = $this->getExtraParamForRedis();
        
        if(count($extraParam) > 0) {
            $option = array_merge($option,$extraParam);
        }
        
        $this->redis->hMSet($fileName.':'.$op,$option);

        return true;
    }

    /*
    *Set directory of the worker
    * @param string $path
    */
    public function setDirectory($path) 
    {
        $this->directory = $path;
        return $this;
    }

    /*
    *Set free persent of the worker
    * @param int $freePersent
    */
    public function setFreePersent($freePersent) 
    {
        $this->freePersent = (double)$freePersent;
        return $this;
    }

    
    /*
    * Set extraoption to save in redis 
    * @param array $options
    */
    protected function setExtraParamForRedis($options) 
    {
        if (is_array($options)) {
            $this->redisExtraParams = $options;
        }
    }

    /*
    * Returns extraoption to save in redis 
    */
    protected function getExtraParamForRedis() 
    {
        $tmp = $this->redisExtraParams;
        $this->redisExtraParams = [];
        return $tmp;        
    }
    
    /*
    * Stop existing worker
    * @param int $pid
    */
    protected function stopProcess($pid)
    {
        $cmd = 'kill -9 '.$pid;
        exec($cmd,$out);
        $this->log($cmd );
        return 1;
    }

    /*
    * Log the content
    * @param string $msg
    */
    protected function log($msg)
    {
        if(!isset($_SERVER['HTTP_USER_AGENT']))
            echo $msg.PHP_EOL;
        return;
    }

    /*
    * Get all infomation of a worker from redis  
    * @param string $workerName
    */
    public function getWorkerProcessesRedis($workerName) 
    {
        // $this->log('ps h |grep '.$this->directory.'/'.$worker['name'].'.php');
        $workersDetail = $this->redis->keys($workerName. '*');
        $details = [];
        foreach($workersDetail as $workerJobName) {
            $details[$workerJobName] = $this->redis->hGetAll($workerJobName);
        }
        
        return $details;
    }

    /*
    * Get all instance details of a worker from OS  
    * @param string $workerName
    */
    protected function getProcessWorker($workerName) 
    {
        $cmd = 'ps h -e |grep '.$this->directory.$workerName.'.php';
        exec($cmd, $out);
        // $this->log($cmd);
        // print_r($out);die;
        array_pop($out);
        array_pop($out);
    
        $this->log(print_r($out, true).'sd');
        return $out;
    }

    protected function removeProcess($worker)
    {
        $this->redis->del($worker['name'].":".$worker['pid']);
        $this->stopProcess($worker['pid']);
        return 1;
    }

    /*
    * Deside to create a new instance or kill existing 
    * @param array $workerDetail
    */
    protected function checkWorker($worker) 
    {
        $runingProcesses = $this->getWorkerProcessesRedis($worker['name']);
        $this->log(print_r($runingProcesses, true));
        

        $processesDeail = $this->getProcessWorker($worker['name']);

        $processingProcess = [];
        
        $freeProcess = [];

        foreach ($processesDeail as $processDetail) {
            $processDetail = array_filter(explode(' ',$processDetail)); 
            $processId = reset($processDetail); 

            $processKey = $worker['name'].":".$processId;
            if(!isset($runingProcesses[$processKey])) {
                $this->stopProcess($processId);
                continue;
            }

            // $workerDetails = $this->redis->hGetAll($processKey); 
            $worker = array_merge($worker,$runingProcesses[$processKey]);
            
            $processingProcess[$processKey] = $worker;
    
            if($worker['state'] == 0) {
                if($worker['iteration'] < $worker['maxIteration']) {
                    $freeProcess[] =   $processKey;
                } else {
                    $this->removeProcess($worker);
                }
            }
        }
        $extraProcesses = array_diff_key($runingProcesses,$processingProcess);

        // $this->log(print_r($extraProcesses,true));
    
        foreach($extraProcesses as $processKey => $process ) {
            // $this->log($process);
            $this->redis->Del($processKey);
        }
        $countProcessingProcess = count($processingProcess);
    
        $noProcessTobeTriggered = 0;
        $this->log($countProcessingProcess.' runningProcess');

        if($countProcessingProcess > 0) {
            
            $currentFreePersent =  count($freeProcess)*100/$worker['min'];

            if($countProcessingProcess < $worker['min']) {
                $noProcessTobeTriggered = $worker['min']-$countProcessingProcess;
            }
            if($currentFreePersent < $this->freePersent) {
                $noOfPercentToAdd = ($this->freePersent - $currentFreePersent)*$countProcessingProcess
                                        /
                                    100;

                $noOfPercentToAdd = ceil($noOfPercentToAdd);

                if($noOfPercentToAdd > $worker['max']){
                    $noOfPercentToAdd = $worker['max'];
                }

                if($noProcessTobeTriggered < $noOfPercentToAdd) {
                    $noProcessTobeTriggered = $noOfPercentToAdd;
                }
            
            }

            if($currentFreePersent > 100 ) {
                $noOfEndProcess = round(($currentFreePersent - 100)*$worker['min']/100);
                $removableProcess = $this->getRemovebleProcess($processingProcess,'state');
                $this->log(print_r($removableProcess,true)."removable");

                for ($i=0; $i < $noOfEndProcess; $i++) {
                    if (count($removableProcess) > 0) {
                        $processToEnd = array_key_first($removableProcess);
                        $this->removeProcess($processingProcess[$processToEnd]);
                        unset($removableProcess[$processToEnd]);
                    }
                }
            }
            $this->log($currentFreePersent.' '.count($freeProcess));
            
        } else {
            $noProcessTobeTriggered = $worker['min'];
        }
        $this->log($noProcessTobeTriggered.' triggeredProcess');
    
        for($i=0; $i < $noProcessTobeTriggered; $i++) {
            $this->setExtraParamForRedis(['maxIteration' => $worker['maxIteration']]);
            $this->startProcess($worker['name']);
        }
    }

    public function getRemovebleProcess($array,$column)
    {
        $return = [];
        foreach ($array as $key => $value) {
            if($value['state'] == self::STATE_IDLE) {
                $return[$key] = $value['iteration'];
            }
            
        }
        arsort($return);
        // $this->log(print_r(rsort($return),true)."removable");
        return $return;
    }

    /*
    * multiple worker handling 
    * @param string $workerDetails
    */
    public function processWorker($workers)
    {
        foreach($workers as $worker) {
            if(!$this->purifyWorker($worker)) {
                continue;
            }
            $this->checkWorker($worker);
        }
    }

    /*
    * Validate worker details 
    * @param string $workerDetails
    */
    protected function purifyWorker(&$worker)
    {
        
        if(!isset($worker['name'])) {
           return false; 
        }
        $purifingWorker = [
            'name'  => '',
            'min'   => 3,
            'max'   => 5,
            'maxExecution'  =>  '100',
            'maxIteration'  => 3,
        ];

        $worker = array_intersect_key($worker,$purifingWorker);
        
        $diffs = array_diff_key($purifingWorker,$worker);
        $worker = array_merge($worker,$diffs);
        return true;
    }
    
}


   