#!/usr/bin/php
<?php

define('XAR_TOKEN_VAR_START', '$');
function testit($blExpression)
{
    // 'resolve' the dot and colon notation
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
    return XAR_TOKEN_VAR_START . $expression;
}

function trphp($phpExpression)
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
    //  6.  (?:         => start property access non-captured subpattern
    //  7.   :|\\.      => matches the colon or the dot notation
    //  8.   [$]{0,1}   => the array key or object member may be a variable
    //  9.   [0-9a-z_]+ => matches number,letter or underscore, one or more occurrences (TODO: if variable, make sure it starts with a letter)
    // 10.  )           => matches right brace
    // 11.  *           => match zero or more occurences of the property access / array key notation (colon notation)
    // 12. )            => ends the current pattern
    // TODO: of course, if all this was between #...# it would be a lot easier ;-)
    // TODO: $a[$b]:c doesn't work properly should be: $a[$b]->c Is: $a[$b]:c
    if (preg_match_all("/\\\$([a-z_][0-9a-z_]*(?:[:|\\.][$]{0,1}[0-9a-z_]+)*)/i", $phpExpression, $matches)) {
        // Resolve BL expresions inside the php Expressions
        $numMatches = count($matches[0]);
        for ($i = 0; $i < $numMatches; $i++) {
            $resolvedName =testit($matches[1][$i]);
            if (!isset($resolvedName)) return; // throw back
            
            $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
        }
    }
    
    $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
    $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
    
    $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);
    
    return $phpExpression;
}

$tests = array (
                '$a.b',
                '$a:b',
                '$a.$b',
                '$a:$b',
                '$a.4',
                '$a.4b',
                '$a.b.c.d',
                '$a.b.c:d',
                '$a.b:c.d',
                '$a.b:c:d',
                '$a:b.c.d',
                '$a:b.c:d',
                '$a.', 
                'a..', 
                'a.b.4',
                'a.$b.4',
                '$a[$b]:c',
                '$a.$b:c',
                '$a[$b]',
                '$a[$b.c]',
                '$a[$b:c]'
);

foreach($tests as $test) {
   echo str_pad($test, 15, ' ') . " ~ " . trphp($test) ."\n";
}
?>