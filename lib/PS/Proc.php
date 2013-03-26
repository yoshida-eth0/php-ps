<?php

class PS_Proc
{
    static public $DEFAULT_WAIT_TIMEOUT = 0;        // second, forever
    static public $DEFAULT_WAIT_INTERVAL = 200000;  // micro second, 0.2 seconds

    protected $_ps = null;

    public function __construct(array $ps)
    {
        $this->_ps = $ps;
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
        $endtime = $timeout<=0 ? INF : time()+$timeout;
        while (true) {
            if (!$this->isActive()) {
                return true;
            }
            if ($endtime<=time()) {
                break;
            }
            usleep($interval);
        }
        $mess = sprintf("PS_Proc::wait() timed out: pid=%d timeout=%d interval=%d", $this->pid, $timeout, $interval);
        throw new PS_TimeoutException($mess);
    }

    // property

    public function __get($name)
    {
        if (isset($this->_ps[$name])) {
            return $this->_ps[$name];
        }
        return null;
    }

    public function __isset($name)
    {
        return isset($this->_ps[$name]);
    }
}
