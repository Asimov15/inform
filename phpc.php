<?php
// prints e.g. 'Current PHP version: 4.1.1'
echo 'Current PHP version: ' . phpversion();
echo PHP_EOL;
// prints e.g. '2.0' or nothing if the extension isn't enabled
echo phpversion('tidy');
echo PHP_EOL;
$test= "test12\abc";
echo $test;
echo PHP_EOL;
echo str_replace("\\", "", $test);
echo PHP_EOL;
?>
