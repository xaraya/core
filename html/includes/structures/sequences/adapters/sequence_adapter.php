<?php
/**
 * Sequence adapter
 * 
 * A class which helps others classes to implement
 * specialized sequence behaviour by letting them
 * implement their interface in terms of the sequence
 * interface. (exposed protected in this class)
 */ 
class SequenceAdapter implements iAdapter, iSequenceAdapter 
{
    // Who does the actual work?
    private $implementor;
    // iAdapter implementation
    // Our children have nothing to say, we do the construction
    final public function __construct($type = 'array', $args = array())
    {
        switch($type) {
        case 'array':
            // Only array as storage implemented atm
            $classfile = 'array_sequence.php';
            $class='ArraySequence';
            break;
        case 'dd':
            $classfile = 'dd_sequence.php';
            $class= 'DynamicDataSequence';
            break;
        default:
            die('Wrong type');
        }
        include_once dirname(__FILE__).'/'.$classfile;
        $this->implementor = new $class($args);
    }
    // iSequenceAdapter implementation
    public function head()
    {
        return $this->implementor->head();
    }
    public function tail()
    {
        return $this->implementor->tail();
    }
    // I want to have this protected but php wont let me
    public function __get($property) {
        switch($property) {
        case 'size':
            return $this->implementor->size;
        case 'empty':
            return ($this->implementor->size == 0);
        }
    }
    // The actual implementor handles the implementation details,
    protected function &get($position) 
    {    
        // TODO: check size and empty
        $item = $this->implementor->get($position); 
        return $item;
    }
    protected function insert(&$item, $position) 
    { 
        // TODO: check size and empty
        return $this->implementor->insert($item, $position);
    }
    protected function delete($position)
    { 
        // TODO: check size and empty
        return $this->implementor->delete($position);
    }
    protected function clear() 
    {  
        return $this->implementor->clear(); 
    } 
}
?>