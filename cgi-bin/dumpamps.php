<?php

include "phpmods/format_events.php";
include_once "phpmods/config.php";

$evid   = $_GET["EVID"];
// $database   = $_GET["DATABASE"];

echo <<< EOFx
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
  <head>
    <title>Amps for $evid</title>
  </head>

  <body>
<PRE>
EOFx;

// $binDir is from config.php
print system("$binDir/ampWrapper.sh $evid");

echo <<< EOFx
</PRE>
  </body>
</html>
EOFx;
?>
