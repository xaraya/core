<?php

function testit($blExpression) 
{
$identifiers = preg_split('/[.|:]/',$blExpression);
$operators = preg_split('/[^.|^:]/',$blExpression,-1,PREG_SPLIT_NO_EMPTY);

$numIdentifiers = count($identifiers);

$expression = $identifiers[0];
for ($i = 1; $i < $numIdentifiers; $i++) {
            if($operators[$i - 1] == '.') {
                $expression .= "['".$identifiers[$i]."']";
            } elseif($operators[$i - 1] == ':') {
         $expression .= '->'.$identifiers[$i];
    }
}
return $expression;
}
 
$tests = array (
'a.b.c.d',
'a.b.c:d',
'a.b:c.d',
'a.b:c:d',
'a:b.c.d',
'a:b.c:d',
'a.', 'a..', 'normal.test.4',
'tpl:pageTitle'
);

foreach($tests as $test) {
   echo $test . " ~~ $" . testit($test) ."\n";
}
?>