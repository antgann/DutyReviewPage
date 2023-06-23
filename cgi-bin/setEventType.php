<?php
/*
  Set the type of an event - html only version for sidekick

  SYNTAX:  setEventType.php?EVID=<evid>&HOST=<dbase-hostname>&TYPE=<tp>

  Example: setEventType.php?EVID=1234567&HOST=makalu&TYPE=ts

*/

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/config.php";

// PASSED ARGS
$evid   = $_GET["EVID"];
$dbhost = $_GET["HOST"];
$dbname = getDefDbname();
$type = $_GET["TYPE"];

// Environmental & server values
$remoteUser = $_SERVER["REMOTE_USER"];  
$httpHost   = $_SERVER["SERVER_NAME"];
$remoteAddr = $_SERVER["REMOTE_ADDR"];

// connect to dbase using info from db_conn.php
// NOTE: use 'power' user to allow writes
$dbdescr = getDbServerString($dbhost, $dbname);
$connection = db_connect($db_power_user, $db_power_password, $dbdescr);

//echo "<p>$connection";

// The dbase action
$res = db_setEventType($connection, $evid, $type);

// disconnect
db_logoff();

// make a log entry
//$logFile = "../logs/review.log"; // use global $logFile from config.php -aww

date_default_timezone_set('UTC');
$datestr = date('Y-m-d G:i:s T');
$logstr = "Set type = $type: evid= ".$evid." db= ".$db_host.
          "  By: ".$remoteUser."  From: ".$remoteAddr." @ ".$datestr;
echo "$logstr<p>";

// append to log
$logStr = "echo \"$logstr\" >> $logFile";
echo "<p>$logStr<p>";

`$logStr`;


// -------------------------------------------
function db_setEventType($_con, $_evid, $_type) {

// Sets 'rflag' = "H"
 $str = "call epref.setEventType ($_evid, '$_type')";

echo "<p>$str<p>";

 $res = db_query($_con, $str);

   return $res;
}
?>
