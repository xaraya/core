<?php

    class ObjectDescriptor extends Object
    {
        protected $args;

        function __construct(array $args)
        {
            $this->setArgs($args);
        }

        public function getArgs()
        {
            return $this->args;
        }

        public function getPublicProperties(Object $object)
        {
            $o = $object->getClass();
            $objectname = $o->getName();
            $reflection = new ReflectionClass($objectname);
            $properties = array();
            foreach($reflection->getProperties() as $p) {
                $prop = new ReflectionProperty($objectname,$p->name);
                if ($prop->isPublic()) $properties[$p->name] = $prop->getValue($object);
            }
            return $properties;
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

        public function setArgs(array $args)
        {
            $this->args = $args;
        }
    }
?>
