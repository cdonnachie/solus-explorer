<?php 

class TimeTracker {
    public $collection;

    public function __construct(){
        $this->collection = array();
    }

    public function getCollection(){
        return $this->collection;
    }

    public function createTimer($name, $level, $start_now = true){

        $timer = new Timer($name, $level);
        if ($start_now){
            $timer->start();
        }
        $this->collection[$name] = $timer;
    }
    public function startTimer($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)){
            $timer->start();
        }
    }
    public function continueTimer($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)){
            $timer->start();
        }
    }
    public function stopTimer($name, $info = null){
        $timer = $this->getTimer($name);
        if (!is_null($timer)){
                $timer->stop();
            if (!is_null($info)){
                $timer->setInfo($info);
            }
            $this->collection[$name] = $timer; 
        }
    }
    public function restartTimer($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)){
            $timer->restart();
        }
    }
    public function setInfo($name, $info){
        $timer = $this->getTimer($name);
        if (!is_null($timer)){
            $timer->setInfo($info);
        }
    }
    public function getDurationSeconds($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)) {return $timer->getDurationSeconds();} else {return null;}
    }
    public function getDurationMilliSeconds($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)) {return $timer->getDurationMilliSeconds();} else {return null;}
    }
    public function getDurationMicroSeconds($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)) {return $timer->getDurationMicroSeconds();} else {return null;}
    }
    public function getInfo($name){
        $timer = $this->getTimer($name);
        if (!is_null($timer)) {return $timer->getInfo();} else {return null;}
    }

    public function getResults(){
        $res = array();
        foreach ($this->getCollection() as $timer){
            $tr = new stdClass();
            $tr->name = $timer->name;
            $tr->level = $timer->level;
            $tr->duration_usec = $timer->getDurationMicroSeconds();
            $tr->info = $timer->getInfo();
            array_push($res, $tr);
        }
        return $res;
    }

    private function getTimer($name){
        if (array_key_exists($name, $this->collection)){
            return $this->collection[$name];
        } else {
            return null;
        }
    }
}

class Timer {
    public $name;
    public $level;
    private $starttime;
    //private $endtime;
    public $info;
    public $duration;
    
    public function __construct($name, $level, $start_now = true){
        $this->name         = $name;
        $this->level        = $level;
        $this->starttime    = null;
        //$this->endtime      = null;
        $this->info         = null;
        $this->duration     = 0;
        if ($start_now){
            $this->start();
        }
    }
    public function start(){
        $this->starttime = microseconds();
    }
    public function stop($info = null){
        if (!is_null($this->starttime)){
            $this->duration += microseconds() - $this->starttime;
            $this-> starttime = null;
        } 
        if (!is_null($info)){
            $this->setInfo($info);
        }
    }
    public function restart(){
        $this->starttime = microseconds();
        $this->duration = 0;
        $this->info = null;
    }
    public function setInfo($info){
        $this->info = $info;
    }

    public function getDurationSeconds(){
        //if (!is_null($this->starttime) && !is_null($this->endtime)){
            return $this->duration/1000000;
        //}
    }
    public function getDurationMilliSeconds(){
        //if (!is_null($this->starttime) && !is_null($this->endtime)){
            return $this->duration/1000;
        //}
    }
    public function getDurationMicroSeconds(){
        //if (!is_null($this->starttime) && !is_null($this->endtime)){
            return $this->duration;
        //}
    }
    public function getInfo(){
        return $this->info;
    }
}

function microseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000000 + ((int)round($mt[0] * 1000000));
}
?>