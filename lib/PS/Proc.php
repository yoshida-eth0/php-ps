<?php

class PS_Proc
{
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
