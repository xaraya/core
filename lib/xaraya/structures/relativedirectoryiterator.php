<?php

    class RelativeDirectoryIterator extends DirectoryIterator
    {
        public function __construct($file)
        {
            $realpath = realpath($file);
            if (!$realpath) return false;
            parent::__construct($realpath);
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