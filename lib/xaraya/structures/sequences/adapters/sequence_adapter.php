<?php
/**
 * Sequence adapter
 * 
 * A class which helps others classes to implement
 * specialized sequence behaviour by letting them
 * implement their interface in terms of the sequence
 * interface. (exposed protected in this class)
**/ 
class SequenceAdapter extends Object implements iAdapter, iSequenceAdapter 
{
    // Who does the actual work?
    private $implementor;
    // iAdapter implementation
    // Our children have nothing to say, we do the construction
    
    /**
     * Constructor for sequence adapter
     *
     * @return void
     * @throws Exception
     * @author Marcel van der Boom
    **/
    final public function __construct($type = 'array', array $args = array())
    {
        switch($type) {
        case 'array':
            // Sequence stored as plain array, volatile
            $adapter   = 'array_sequence';
            $class='ArraySequence';
            break;
        case 'dd':
            // Sequence stored in dd object, persistent
            $adapter   = 'dd_sequence';
            $class= 'DynamicDataSequence';
            break;
        default:
            throw new Exception("Sequence type $type is not supported");
        }
        sys::import('xaraya.structures.sequences.adapters.'.$adapter);
        $this->implementor = new $class($args);
    }

    // iSequenceAdapter implementation

    // I want to have this protected but php wont let me
    /**
     * Getters
     *
     * @return mixed
     * @throws Exception
    **/
    public function __get($property) 
    {
        switch($property) {
        case 'size':
            return $this->implementor->size;
        case 'empty': // TODO: this is traditionally a method, should we?
            return $this->implementor->empty;
        case 'head':
            return $this->implementor->head;
        case 'tail':
            return $this->implementor->tail;
        default:
            throw new Exception("Property $property does not exist");
        }
    }
    // The actual implementor handles the implementation details,
    protected function &get($position) 
    {    
        $item = $this->implementor->get($position); 
        return $item;
    }
    protected function insert(&$item, $position) 
    { 
        return $this->implementor->insert($item, $position);
    }
    protected function delete($position)
    { 
        return $this->implementor->delete($position);
    }
    protected function clear() 
    {  
        return $this->implementor->clear(); 
    } 
}
?>
