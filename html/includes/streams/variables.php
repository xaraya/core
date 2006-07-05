<?php
/**
 * Prototype implementation of a custom stream
 *
 * PHP allows you to define custom protocols which allow you to treat
 * certain things like a stream which are normally not a stream. If you dont
 * know what a stream is, dont bother using this.
 * The reason for this prototype is that it sounds like a nice idea to expose
 * things inside xaraya (dd data comes to mind) as a stream of some sort.
 * I haven't got a usecase or even if it is useful, but it sounds nice :-)
 * 
 * The code below implements a 'var:' protocol, which means you can read from 
 * a variable like it was a stream. Example code at the bottom. This example
 * is taken almost verbatime from the link specified below. The trick is that 
 * with one specifier (the registered protocol) you can fopen/fread/fwrite into
 * basically everything you define.
 *
 * @package core
 * @subpackage streams
 * @copyright The Digital Develoment Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://php.net/manual/en/function.stream-wrapper-register.php
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/

class VariableStream {
    public $position;
    public $varname;
    
    function stream_open($path, $mode, $options, &$opened_path) 
    {
        $url = parse_url($path);
        $this->varname = $url["host"];
        $this->position = 0;
        
        return true;
    }
    
    function stream_read($count) 
    {
        $ret = substr($GLOBALS[$this->varname], $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    function stream_write($data) 
    {
        $left = substr($GLOBALS[$this->varname], 0, $this->position);
        $right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
        $GLOBALS[$this->varname] = $left . $data . $right;
        $this->position += strlen($data);
        return strlen($data);
    }
    
    function stream_tell() 
    {
        return $this->position;
    }
    
    function stream_eof() 
    {
        return $this->position >= strlen($GLOBALS[$this->varname]);
    }
    
    function stream_seek($offset, $whence) 
    {
        switch ($whence) {
        case SEEK_SET:
            if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
                $this->position = $offset;
                return true;
            } else {
                return false;
            }
            break;
            
        case SEEK_CUR:
            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            } else {
                return false;
            }
            break;
            
        case SEEK_END:
            if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
                $this->position = strlen($GLOBALS[$this->varname]) + $offset;
                return true;
            } else {
                return false;
            }
            break;
            
        default:
            return false;
        }
    }
    
    function stream_stat()
    {
        // This looks weird, and it is. See php documentation on the gory details.
        $return = array('dev'     => 771,
                        'ino'     => 488704,
                        'mode'    => 0100666,
                        'nlink'   => 1,
                        'uid'     => 0,
                        'gid'     => 0,
                        'rdev'    => 0,
                        'size'    => strlen($GLOBALS[$this->varname]),
                        'atime'   => 1061067181,
                        'mtime'   => 1056136526,
                        'ctime'   => 1056136526,
                        'blksize' => 4096,
                        'blocks'  => 8,
                        );
        return $return;
    }
  }
if(!stream_wrapper_register("var", "VariableStream")) {
    throw new Exception("Failed to register 'var:' protocol");
}

// TEST CODE: 
if(true){
    $myvar = "";

    // This is the crux
    $fp = fopen("var://myvar", "r+");

    fwrite($fp, "line1\n");
    fwrite($fp, "line2\n");
    fwrite($fp, "line3\n");

    rewind($fp);
    while (!feof($fp)) {
        echo fgets($fp);
    }
    fclose($fp);
    var_dump($myvar);
}
?>