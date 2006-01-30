<?php
/**
 * Interfaces for sequences:
 *
 * Sequence : linear list, insert/delete can happen at any index
 * Stack    : linear list, insert/delete happen at head
 * Queue    : linear list, insert happens at head, delete at tail
 * Deque    : linear list, insert/delete happens at either head or tail
 *
 * If you are in need of these objects, this file should theoretically
 * contain any information you need to be able to use them.
 * 
 * Legend for interface description:
 * Properties:
 * [rw] (type) name : description of property 'name' with (r)ead and/or (w)rite access        
 * Methods:
 * (type) access function &name(param,...,param)
 * 
 * Methods and properties in an interface declaration are by
 * definition public (in PHP that is)
 */

/**
 * Sequence datastructure
 *
 * A sequence is the generic datastructure for a linear list where
 * items can be deleted and inserted at any place in the list.
 * 
 */
interface iSequence
{
    // r (int)  size   : number of elements in the sequence
    // r (bool) empty  : is the sequence empty
    /* (mixed) item */public function &get($position);
    /* (bool)  ok   */public function insert(&$item, $position);
    /* (bool)  ok   */public function delete($position);
    /* (bool)  ok   */public function clear();
}

/** 
 * Adapter helper
 *
 * To use (in this case a sequence) an object and implement its
 * interface in terms of another, we use an adapter interface
 * 
 * An adapter reimplements the interface of the object being adapted
 * but protects those methods, to be used only by its descendents
 * This interface is the base Adapter.
 */
interface iAdapter {
    /* (mixed) object */public function __construct($type = 'array', $args = array());
}

/**
 * Sequence Adapter
 *
 * Adapter interface for sequences.
 *
 * Other objects specialising a sequence inherit from this
 */
interface iSequenceAdapter
{
    // r (int)  size : number of elements
    // r (bool) empty: is the sequence empty
    // r (int)  head : index of the head of the sequence
    // r (int)  tail : index of the tail of the sequence
}

/**
 * Queue datastructure
 *
 * A queue is a special sequence where items flow in at the head of
 * the sequence and flow out at the tail. No other elements of the
 * queue are directly accessible.
 */
interface iQueue
{
    // r (int)   size : number of elements in the queue
    // r (bool)  empty: is the queue empty 
    /*   (bool)  ok   */public function push($item);
    /*   (mixed) item */public function &pop();
    /*   (bool)  ok   */public function clear();
}

/**
 * Stack datastructure
 *
 * A stack is a special sequence where items flow in and out at the
 * head of the sequence. No other elements of the queue are directly
 * accessible. It has the same interface as a queue (which one
 * inherits from which is arbitrary)
 */
interface iStack extends iQueue
{}

/**
 * Deque datastructure
 *
 * A deque is a special sequence where items flow in at the head or
 * tail of the sequence. No other elements of the queue are directly
 * accessible.
 */
interface iDeque
{
    // r (int)   size : number of elements in the deque
    // r (bool)  empty: is the deque empty
    /*   (bool)  ok   */public function push(&$item, $whichEnd);
    /*   (mixed) item */public function &pop($whichEnd);
    /*   (bool)  ok   */public function clear();
}
?>