<?php

    class ObjectDescriptor extends DataContainer
    {
        protected $args;

        function __construct(array $args=array())
        {
            $this->setArgs($args);
        }

        public function getArgs()
        {
            return $this->args;
        }

        public function refresh(Object $object)
        {
            $publicproperties = $this->getPublicProperties($object);
            foreach ($this->args as $key => $value) if (in_array($key,$publicproperties)) $object->$key = $value;
            else echo $key ."<br />";  // temporary for debugging
        }

        public function store(Object $object)
        {
            $publicproperties = $this->getPublicProperties($object);
            foreach ($publicproperties as $key => $value) $this->args[$key] = $value;
        }

        public function setArgs(array $args=array())
        {
            if (empty($this->args)) $this->args = $args;
            else foreach($args as $key => $value) if (isset($value)) $this->args[$key] = $value;
        }
        /* deprecated
        public function load(array $args=array())
        {
            $this->__construct($args);
        }
        */
    }
?>
