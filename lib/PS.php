<?php

require_once 'PS/Proc.php';
require_once 'PS/Exception.php';
require_once 'POpen4.php';

class PS
{
    static public $PS_CMD = "/bin/ps aux";
    static public $KILL_BIN = "/bin/kill";

    protected $_keys = null;
    protected $_procs = null;

    public function __construct(array $keys=null, array $procs=null)
    {
        if (isset($keys, $procs)) {
            $this->_keys = $keys;
            $this->_procs = $procs;
        } else {
            $this->_fetchAll();
        }
    }

    public function procs()
    {
        return $this->_procs;
    }

    // filter

    public function equalFilter($key, $value)
    {
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
        $procs = array();
        foreach ($this->_procs as $proc) {
            if (isset($proc->$key) && preg_match($pattern, $proc->$key)) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    public function progFilter($path) {
        $pos = strpos($path, "/");
        if (0<$pos) {
            $path = realpath($path);
            $pos = 0;
        }

        if ($pos===0) {
            $pattern = "/^".preg_quote($path, "/")."( |$)/";
        } else {
            $pattern = "/^([^\"']*\/)*".preg_quote($path, "/")."( |$)/";
        }
        return $this->matchFilter("command", $pattern);
    }

    public function filter($func)
    {
        if (is_callable($func)) {
            throw new PS_Exception("func is not callable: ".$func);
        }
        $procs = array();
        foreach ($this->_procs as $proc) {
            if ($func($proc)) {
                $procs[] = $proc;
            }
        }
        return new self($this->_keys, $procs);
    }

    // fetch

    protected function _fetchAll()
    {
        $p = new POpen4(self::$PS_CMD);
        $txt = "";
        while (!feof($p->stdout())) {
            $txt .= fread($p->stdout(), 8192);
        }
        $p->close();
        if (0<$p->exitstatus()) {
            throw new PS_Exception("ps exitstatus is not success :".$p->exitstatus());
        }

        $txt = trim($txt);
        $lines = preg_split("/(\r\n|\r|\n)/", $txt);

        $head = array_shift($lines);
        $keys = preg_split("/\s+/", $head);
        foreach ($keys as $i=>$key) {
            $keys[$i] = strtolower(str_replace("%", "", $key));
        }

        $procs = array();
        foreach ($lines as $line) {
            $values = preg_split("/\s+/", $line, count($keys));
            $assoc = array_combine($keys, $values);
            $proc = new PS_Proc($assoc);
            $procs[] = $proc;
        }

        $this->_keys = $keys;
        $this->_procs = $procs;
    }
}
