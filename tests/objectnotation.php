#!/usr/bin/php
<?php

define('XAR_TOKEN_VAR_START', '$');
function testit($blExpression)
{
    // 'resolve' the dot and colon notation
    $subparts = preg_split('/[\[|\]]/', $blExpression);
    if(count($subparts) > 1) {
        foreach($subparts as $subpart) {
            // Resolve the subpart
            $blExpression = str_replace($subpart, testit($subpart), $blExpression);
        }
        return $blExpression;
    }

    // No arrays in the expression, plain vanilla resolve of dot and colon
    $identifiers = preg_split('/[.|:]/',$blExpression);
    $operators = preg_split('/[^.|^:]/',$blExpression,-1,PREG_SPLIT_NO_EMPTY);
    $numIdentifiers = count($identifiers);
    
    $expression = $identifiers[0];
    for ($i = 1; $i < $numIdentifiers; $i++) {
        if($operators[$i - 1] == '.') {
            if((substr($identifiers[$i],0,1) == XAR_TOKEN_VAR_START) || is_numeric($identifiers[$i])) {
                $expression .= "[".$identifiers[$i]."]";
            } else {
                $expression .= "['".$identifiers[$i]."']";
            }
        } elseif($operators[$i - 1] == ':') {
            $expression .= '->'.$identifiers[$i];
        }
    }
    
    return  $expression;
}

function trphp($phpExpression, $runs = 1)
{
    // This regular expression  must match the variables in the BLExpression grammar above
    // pass it to the resolver, check for exceptions, and replace it with the resolved
    // var name.
    // Let's dissect the expression so it's a bit more clear:
    //  1. /..../i      => we're matching in a case - insensitive  way what's between the /-es (FIXME: KEEP AN EYE ON THIS) 
    //  2. \\\$         => matches \$ which is an escaped $ in the string to match
    //  3. (            => this starts a captured subpattern - results in $matches[1]
    //  4.  [a-z_]      => matches a letter or underscore
    //  5.  [0-9a-z_]*  => matches a number, letter of underscore, zero or more occurrences
    //  6.  (?:         => start array key/objectmember non-captured subpattern
    //  7.   :|\\.      => matches the colon or the dot notation
    //  8.   [$]{0,1}   => the array key or object member may be a variable
    //  9.   [0-9a-z_]+ => matches number,letter or underscore, one or more occurrences 
    // 10.  )           => matches right brace
    // 11.  *           => match zero or more occurences of the property access / array key notation (colon notation)
    // 12. )            => ends the current pattern
    // TODO: of course, if all this was between #...# it would be a lot easier ;-)
    // TODO: $a[$b]:c doesn't work properly should be: $a[$b]->c Is: $a[$b]:c
    // TODO: if array key / object member is variable, make sure it starts with a letter like identifiers should (it will generate an error anyway, but alas)
    $regex = "/((\\\$[a-z_][a-z0-9_\[\]\$]*)([:|\.][$]{0,1}[0-9a-z_\]\[\$]+)*)/i";
    //$regex = "/\\\$([a-z_][0-9a-z_]*(?:[:|\\.][$]{0,1}[0-9a-z_]+)*)/i";
    if (preg_match_all($regex, $phpExpression, $matches)) {
        // Resolve BL expresions inside the php Expressions
        usort($matches[0], 'rlensort');
        //echo print_r($matches,true);
        $numMatches = count($matches[0]);
        for ($i = 0; $i < $numMatches; $i++) {
            $resolvedName =& testit($matches[0][$i]);
            if (!isset($resolvedName)) return; // throw back
            
            // THIS IS NOT SAFE IF SOME PARTS OVERLAP
            $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
        }
    }
    
    $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
    $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
    
    $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);
    $runs--;
    if($runs) $phpExpression = trphp($phpExpression,$runs);
    return $phpExpression;
}

function rlensort($a, $b) 
{
    if(strlen($a) == strlen($b)) {
        return 0;
    }
    return (strlen($a) < strlen($b)) ? 1 : -1;
}


$tests = array (
                '$a'       , 'a',
                '.$a'      , '$a.'   , 'a..' , '..a',
                '$a.b'     , '$a:b'  , 'a.$b', 'a:$b', 
                '$a.$b'    , '$a:$b' ,
                '$a.4'     , '$a.4b' ,
                '$a.b:c', 
                'a.b.4'    , 'a.b.$4',
                'a.$b.4'   , 'a.$b.c',
                '$a[$b]:c' , '$a[$b].c',
                '$a.$b:c'  ,
                '$a[\'b\']', '$a[$b]'  ,
                '$a[$b.c]' , '$a[$b:c]',          
                '$a.b.c.d' , '$a.b.c:d','$a.b:c.d','$a.b:c:d','$a:b.c.d','$a:b.c:d',
                '$o:m()'   , '$o:m($a.b)', '$o:m($a.b)',
                '$o:m($a.b.c)', '$o:m($a:b:c)',
                '$o:m($a.b, $a:b, $a.b:c, $a:b.c)',
                '$a[$b].c',
                '$a[$c.d].e', '$a[$c.d].$e', '$a[$c.$c].e',
				'parents[$i].parentname'
);
//$tests = array ( 'empty($position) and is_array($loop:top:item)');

foreach($tests as $test) {
   echo str_pad($test, 15, ' ') . " ~ " . trphp($test,1) ."\n";
}
?>
