<?php

/**
 * File: $Id:
 * 
 * Function fnmatch
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2005 by the Xaraya Development Team/2004 The PHP group
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jason Judge/Jean-Charles Lefebvre
 * @todo To avoid recursion, do glob matching my converting the glob expression into an RE.
 */

/**
 * Mimics the fnmatch() function introduced in PHP 4.3.0
 * 
 * @link http://www.php.net/manual/en/function.fnmatch.php
 */

function _fnmatch($pattern, $file)
{
    $lenpattern = strlen($pattern);
    $lenfile    = strlen($file);

    for ($i=0; $i<$lenpattern; $i++)
    {
        if ($pattern[$i] == '*')
        {
            for ($c=$i; $c < max($lenpattern, $lenfile); $c++)
            {
                if (_fnmatch(substr($pattern, $i+1), substr($file, $c))) {return true;}
            }
            return false;
        }

        if ($pattern[$i] == '[')
        {
            $letter_set = array();
            for ($c=$i+1; $c < $lenpattern; $c++)
            {
                if ($pattern[$c] != ']') {
                    array_push($letter_set, $pattern[$c]);
                } else {
                    break;
                }
            }
            foreach ($letter_set as $letter)
            {
                if (_fnmatch($letter.substr($pattern, $c+1), substr($file, $i))) {
                    return true;
                }
            }
            return false;
        }

        if ($pattern[$i] == '?') {continue;}
        if ($pattern[$i] != $file[$i]) {return false;}
    }

    if (($lenpattern != $lenfile) && ($pattern[$i - 1] == '?')) {return false;}
    return true;
}

?>