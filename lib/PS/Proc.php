<?php

class PS_Proc
{
    static public $DEFAULT_WAIT_TIMEOUT = 0;    // second, never
    static public $DEFAULT_WAIT_INTERVAL = 0.2; // second, 0.2 seconds

    protected $_assoc = null;

    public function __construct(array $assoc)
    {
        $this->_assoc = $assoc;
    }

    public function assoc()
    {
        return $this->_assoc;
    }

    public function isActive()
    {
        return $this->kill(0)===0;
    }

    public function kill($signal=null)
    {
        $cmd = PS::$KILL_BIN;
        if (isset($signal)) {
            $cmd .= " -".$signal;
        }
        $cmd .= sprintf(" %d", $this->pid);

        $p = new POpen4($cmd);
        $p->close();
        return $p->exitstatus();
    }

    public function wait($timeout=null, $interval=null)
    {
        if (is_null($timeout)) {
            $timeout = self::$DEFAULT_WAIT_TIMEOUT;
        }
        if (is_null($interval)) {
            $interval = self::$DEFAULT_WAIT_INTERVAL;
        }
        if (ctype_digit($interval)) {
            $sleep = "sleep";
        } else {
            $interval *= 1000000;
            $sleep = "usleep";
        }
        $endtime = $timeout<=0 ? INF : time()+$timeout;

        // wait loop
        while (true) {
            if (!$this->isActive()) {
                // exited
                return true;
            }
            if ($endtime<=time()) {
                // timeout
                break;
            }
            // sleep
            $sleep($interval);
        }

        // timeout
        $mess = sprintf("PS_Proc::wait() timeout: pid=%d timeout=%ds interval=%dus", $this->pid, $timeout, $interval);
        throw new PS_TimeoutException($mess);
    }

    // property

    public function __get($name)
    {
        if (isset($this->_assoc[$name])) {
            return $this->_assoc[$name];
        }
        return null;
    }

    public function __isset($name)
    {
        return isset($this->_assoc[$name]);
    }
}
