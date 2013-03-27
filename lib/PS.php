<?php

require_once 'PS/Proc.php';
require_once 'PS/Exception.php';
require_once 'PS/TimeoutException.php';
require_once 'POpen4.php';

class PS implements ArrayAccess, Iterator
{
    static public $PS_BIN = "/bin/ps";
    static public $PS_OPTION = "aux";
    static public $KILL_BIN = "/bin/kill";

    protected $_keys = null;
    protected $_procs = null;
    protected $_iterpos = 0;

    protected function __construct(array $keys, array $procs)
    {
        $this->_keys = $keys;
        $this->_procs = $procs;
    }

    public function procs()
    {
        return $this->_procs;
    }

    public function kill($signal=null)
    {
        if (count($this->_procs)) {
            $cmd = self::$KILL_BIN;
            if ($signal) {
                $cmd .= " -".$signal;
            }
            foreach ($this->_procs as $proc) {
                $cmd .= sprintf(" %d", $proc->pid);
            }

            $p = new POpen4($cmd);
            $p->close();
            return $p->exitstatus();
        }
    }

    // filter

    public function equalFilter($key, $value)
    {
        if (!in_array($key, $this->_keys)) {
            throw new PS_Exception("key not found: ".$key);
        }
        $procs = array();
        foreach ($this->_procs as $proc) {
            if (isset($proc->$key) && $proc->$key==$value) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    public function matchFilter($key, $pattern)
    {
        if (!in_array($key, $this->_keys)) {
            throw new PS_Exception("key not found: ".$key);
        }
        $procs = array();
        foreach ($this->_procs as $proc) {
            if (isset($proc->$key) && preg_match($pattern, $proc->$key)) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    public function progFilter($path)
    {
        $pos = strpos($path, "/");
        if (0<$pos) {
            $path = realpath($path);
            $pos = 0;
        }

        if ($pos===0) {
            $pattern = "/^".preg_quote($path, "/")."( |$)/";
        } else {
            $pattern = "/^([^\"'\/]*\/)*".preg_quote($path, "/")."( |$)/";
        }
        return $this->matchFilter("command", $pattern);
    }

    public function activeFilter()
    {
        $procs = array();
        foreach ($this->_procs as $proc) {
            if ($proc->isActive()) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    public function filter($func)
    {
        if (is_callable($func)) {
            throw new PS_Exception("func is not callable: ".$func);
        }
        $procs = array();
        foreach ($this->_procs as $proc) {
            if (call_user_func($func, $proc)) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    // ArrayAccess

    public function offsetExists($offset)
    {
        return isset($this->_procs[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->_procs[$offset])) {
            return $this->_procs[$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new PS_Exception("offsetSet is not implemented");
    }

    public function offsetUnset($offset)
    {
        throw new PS_Exception("offsetUnset is not implemented");
    }

    // Iterator

    public function rewind()
    {
        $this->_iterpos = 0;
    }

    public function current()
    {
        return $this->_procs[$this->_iterpos];
    }

    public function key()
    {
        return $this->_iterpos;
    }

    public function next()
    {
        $this->_iterpos++;
    }

    public function valid()
    {
        return isset($this->_procs[$this->_iterpos]);
    }

    // static

    static public function open($ps_option=null)
    {
        if (is_null($ps_option)) {
            $ps_option = self::$PS_OPTION;
        }
        $p = new POpen4(self::$PS_BIN." ".$ps_option);
        $txt = "";
        while (!feof($p->stdout())) {
            $txt .= fread($p->stdout(), 8192);
        }
        $p->close();
        if (0!==$p->exitstatus()) {
            throw new PS_Exception("ps exitstatus is not success: ".$p->exitstatus());
        }

        $txt = trim($txt);
        $lines = preg_split("/(\r\n|\r|\n)/", $txt);

        $head = array_shift($lines);
        $keys = preg_split("/\s+/", trim($head));
        foreach ($keys as $i=>$key) {
            $keys[$i] = strtolower(str_replace("%", "", $key));
        }

        $procs = array();
        foreach ($lines as $line) {
            $values = preg_split("/\s+/", trim($line), count($keys));
            if (count($values)==count($keys)) {
                $assoc = array_combine($keys, $values);
                $proc = new PS_Proc($assoc);
                $procs[] = $proc;
            }
        }

        return new self($keys, $procs);
    }
}
