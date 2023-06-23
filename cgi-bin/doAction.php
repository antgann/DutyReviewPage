<?php
/* Wrapper for ACCEPT/DELETE/CANCEL of an event or trigger from the Duty Review Page
  Does the action and writes a log entry.
  SYNTAX:  doAction.php?EVID=<evid>&ACTION=<action>
  Example: doEvent.php?EVID=1234567&ACTION=delete
  This produces no output except what is written to the log file.
  Writes to log at: /home/htdocs/trinet/review/logs/review.log
  This is the log viewable from the webpage.
*/

// required libraries for dbase access
include_once "phpmods/event_actions.php";
include_once "phpmods/config.php";
include_once "phpmods/db_utils.php";

// PASSED ARGS
$evid   = $_GET["EVID"];
$action = strtoupper($_GET["ACTION"]);   // upcase the action string
//$db = $_GET["DATABASE"];
$db = getDefDbname();

print "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML//EN\">
<html>
<head>
<title>$evid $action $db</title>
</head>
<!-- set bacground color to red/orange (well, pink really) -->
<body BGCOLOR=#FFCCCC>";

$res = -1; // result code

// Environmental & server values
//print_r($_SERVER);
$remoteUser = "UNKNOWN";
if ( array_key_exists("REMOTE_USER", $_SERVER) ) {
    $remoteUser = $_SERVER["REMOTE_USER"];  
}
else if ( array_key_exists("PHP_AUTH_USER", $_SERVER) ) {
    $remoteUser = $_SERVER["PHP_AUTH_USER"];  
}
else if ( array_key_exists("REDIRECT_REMOTE_USER", $_SERVER) ) {
    $remoteUser = $_SERVER["REDIRECT_REMOTE_USER"];  
}

$httpHost   = $_SERVER["SERVER_NAME"];
$remoteAddr = $_SERVER["REMOTE_ADDR"];

// compose a log entry
// global $logFile;      // from phpmods/config.php

date_default_timezone_set('UTC');
$datestr = date('Y-m-d G:i:s T');
$logstr = "$action: evid= ".$evid." db= ".$db.
          "  By: ".$remoteUser."  From: ".$remoteAddr." @ ".$datestr;


if ($action == "ACCEPT") {
    $res = hasCancelledAlarms($db, $evid); 
    if ( $res == 'true' ) {
        $logstr = "UNCANCELLED_" . $logstr;
        $res = uncancelEvent($evid); 
    }
    else {
        $res = acceptEvent($evid); 
    }
} else if ($action == "CANCEL") {
    $res = cancelEvent($evid); 
} else if ($action == "DELETE") {
    $res = deleteEvent($evid); 
} else if ($action == "DELETE_TRIGGER") {
    $res = deletetrigger($evid); 
} else if ($action == "ACCEPT_TRIGGER") {
    $res = acceptTrigger($evid); 
} else if ($action == "ALARM") {
    $res = sendAlarms($evid); 
} else if ($action == "FINALIZE") {
    $res = finalizeEvent($evid); 
} else if ($action == "QDDS") {
    $res = sendPublicEvent($evid); 
} else if ($action == "MAKEGIF") {
    $res = remakeGif($evid); 
} else if ($action == "MAKEGIFTRG") {
    $res = remakeGifTrg($evid); 
} else if ($action == "SET_TYPE") {
    $type = $_GET["TYPE"];    // type is a 2-char string like 'eq', 'qb'
    $res = setEventType ($evid, $type); 
    $logstr = $logstr . " type = $type";
} else if ($action == "SET_GTYPE") {
    $gtype = $_GET["GTYPE"];    // type is a 1-char string like 'l','r','t'
    $res = setEventGType ($evid, $gtype); 
    $logstr = $logstr . " gtype = $gtype";
} else {
    $logstr = "INVALID ACTION - ".$logstr;  // no action but report it
}

// --------------------
$logstr .= " res=".$res;  // append result code
$logstr .= "\n"; // line feed for readability

print "$logstr <br />\n";   // echo to web browser for debugging
writeLog($logstr);

function writeLog($string) {
// write $string to $logFile (from config.php)
    global $logFile;

    $fp = fopen($logFile, "a");  // append; filepointer at end of file
    if ($fp === FALSE) {
        print "COULD NOT OPEN LOGFILE FOR WRITING, CHECK PERMISSIONS:<br />\n";
        print "$logFile\n<br />";
        print "Continuing without logging.<br />\n";
    } else {
        if ( flock($fp, LOCK_EX) ) {
            fwrite($fp, $string);
            fclose($fp);
        } else {
            print "Could not get lock for $logFile.<br />\n";
        }
    }
}
?>
</body>
</html>
