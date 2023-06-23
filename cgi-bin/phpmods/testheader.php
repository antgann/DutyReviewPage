<?php
include_once "config.php";
$netC = strtolower($networkCode}
$url ="${recEqRootURL}/eventpage/${netC}15182241#summary";
print "$url\n";
print "First no format arg:\n";
print_r( get_headers($url) );
print "Next with 1 format:\n";
$headers = get_headers($url, 1);
if ('HTTP/1.1 200 OK' == $headers[0]) {
  echo "A MATCH\n";
}
else { echo "NOT A MATCH\n"; }

?>
~
