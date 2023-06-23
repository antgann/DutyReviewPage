<?php

include_once "phpmods/format_events.php";
include_once "phpmods/config.php"; // for $solServer and $solServerPort

$evid   = $_GET["EVID"];
// $database   = $_GET["DATABASE"];
$database   = getDefDbname();

echo <<< EOFx
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
  <head>
    <title>location of $evid</title>
  </head>

  <body>
<PRE>
location provided by ${solServer}:$solServerPort

EOFx;

// $binDir from config.php
print system("$binDir/hypWrapper.sh -u $solServer -p $solServerPort -P -d $database $evid");

echo <<< EOFx
</PRE>
  </body>
</html>
EOFx;
?>
