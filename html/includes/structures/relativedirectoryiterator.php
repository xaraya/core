<?php

    class RelativeDirectoryIterator extends DirectoryIterator
    {
        public function __construct($file)
        {
            parent::__construct(realpath($file));
        }

        public function getExtension()
        {
            $filename = $this->GetFilename();
            $extension = strrpos($filename, ".", 1) + 1;
            if ($extension != false)
                return strtolower(substr($filename, $extension, strlen($filename) - $extension));
            else
                return "";
        }
    }

?>