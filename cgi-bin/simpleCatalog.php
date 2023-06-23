<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html><head>

<?php
/*

  SPECIAL VERION FOR SIDEKICK

 The workhorse creation script of the Duty Review Page
 - Reads catalog from dbase
 - holds internal event list
 - holds value of current selected event

 Invoked with:  "cgi-bin/simpleCatalog.php"

NOTE: dbhost is not passed - it comes from "phpmods/db_conn.php"

Optional row limit syntax:

simpleCatalog.php?LIMIT=400

*/
// ///////////////////////
//  LEAP SECOND COMPLIANT
// ///////////////////////

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/config.php";

$httpHost = $_SERVER["SERVER_NAME"];

// Limit the number of rows in the list so it doesn't get too long during intense sequences
$DefRowLimit = 50;
$rowLimit    = $DefRowLimit;
//$hrsBack = 72;  // show events this old, instead  DEFAULT TO config.php $hrsBack value -aww 2012/05/25
$highlightMag = 2.94;                     // show events > this in red
date_default_timezone_set('UTC');
$now = date("D M j G:i:s T Y");           // Format: Wed Jun 29 11:55:52 PDT 2005
$nines = 999999;

// PASSED ARGS
$rowLimit   = $_GET["LIMIT"];
$browserInfo = strtolower($_SERVER['HTTP_USER_AGENT']);

// 'false' if not Hiptop/Sidekick browser, else =55
$isSidekick = strpos($browserInfo, "hiptop");   

// If no arg or neg. use default value
if ($rowLimit <= 0) {$rowLimit = $DefRowLimit;};

if ($rowLimit == $nines) {
   $title = "Last $hrsBack hours Events";
} else {
   $title = "Last $rowLimit Events";
}

echo "<title>$title ($httpHost) </title>";
echo "</head>";
echo "<A HREF=\"simpleCatalog.php\">REFRESH</A> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $now [$db_host]";
//echo "<br> isSidekick = $isSidekick";
//echo "<br> $browserInfo ";
//
// Read the dbase and format the results as html
//
$knt = format_catalog_html($db_host, $rowLimit, $hrsBack, $isSidekick, $highlightMag);

// enforce truncation rule
if ($knt > $rowLimit) {
    echo "<hr><p>** List truncated at $rowLimit events to limit list size (full time not shown).<p>";
    echo "<A HREF=\"simpleCatalog.php?LIMIT=$nines\">Click here to see ALL events in last ${hrsBack} hours.<A>";
} else {
    echo "<hr> End of list: $knt events in last $hrsBack hrs";
}


// //////////////////////////////////////////////////////////////////////

function format_catalog_html($host, $maxRows, $hrsBack, $squish, $redMag) {
/*
  ID#       MAG    SRC    DATE   TIME ET F
     lat        lon        z   #   rms gap 
  14158676  1.1 Ml 2005/06/27 21:32:48 le A RT1
 33.5917 -116.7530  16.5  22  0.28 122
    8.6 km WNW of Anza, CA
  14158672  1.1 Ml 2005/06/27 21:24:50 le A RT1
 35.4562 -117.8457   7.0  10  0.59 111
   24.5 km SW  of Ridgecrest, CA
*/

// $squish will be either 'false' or a number (true)
$break = "";
if ($squish) {$break = "<br>";};

//  header
echo "<head> </head>";
echo "<PRE>";

//echo "  ID#       MAG    SRC    DATE   TIME ET F$break";
//echo "<i>      lat        lon        z   #   rms gap </i>";
echo "  ID#      MAG     DATE      TIME   ET$break";
echo "<i> SRC F   lat      lon      z    #  rms</i>"; 
//14205356 1.5 Ml 2005/12/21 20:00:38 le RT1 A 34.2252 -116.5640 12.7  16 0.51

$knt = 0;

// connect to dbase using info from db_conn.php
//db_connect($db_user, $db_password, $db);
db_connect_glob();

// ----- Extract catalog data from the dbase -------
$stop = time();                        // now
$start = $stop - ($hrsBack * 60 * 60);  // convert time to epoch secs

// Read one extra row as a marker that we truncated (indicates there were more)
$rowFetchCount = $maxRows + 1;
$eventTypes = "('eq','qb','sh','ex')";    // event types to retreive

// The dbase query
$sql_query="SELECT /*+ FIRST_ROWS */ 
	Event.evid \"evid\", 
	NetMag.magnitude \"mag\", 
	NetMag.magtype \"mt\", 
        TrueTime.getString(origin.datetime) \"ot\", 
        Origin.lat \"lat\", Origin.lon \"lon\", Origin.depth \"z\",
	Origin.ndef \"ndef\",
        Origin.wrms \"rms\", Origin.gap \"gap\", 
	Event.etype \"type\", 
        Origin.gtype \"gtype\",
        Origin.rflag \"rflag\",
        Origin.subsource \"src\",
        WHERES.LOCALE_BY_TYPE(Origin.lat, Origin.lon, Origin.depth, 1, 'town') \"town\"
      FROM Event, Origin, NetMag 
       WHERE selectflag = 1  and etype in $eventTypes  and
        (Event.prefor = Origin.orid(+)) and (Event.prefmag = NetMag.magid(+)) 
         and origin.datetime between $start and $stop	
	 and (ROWNUM<=$rowFetchCount) 
       Order by Origin.datetime desc
";

// execute the query
$result = db_query_glob($sql_query);

// returns the results in table form
$ncols = OCINumCols($result);

// for each row returned
	while (OCIFetch($result))
	{
	if ($knt > $maxRows) {return $knt;};  // hit the end
	
	// parse the individual attributes by name
	$evid    = OCIResult($result, "evid");
	$mag     = OCIResult($result, "mag");
	$magtype = OCIResult($result, "mt");
	$ot      = OCIResult($result, "ot");
	$lat     = OCIResult($result, "lat");	
	$lon     = OCIResult($result, "lon");
	$z       = OCIResult($result, "z");
	$ndef    = OCIResult($result, "ndef");
	$rms     = OCIResult($result, "rms");
	$gap     = OCIResult($result, "gap");
	$type    = OCIResult($result, "type");
	$gtype   = OCIResult($result, "gtype");
	$rflag   = OCIResult($result, "rflag");
	$src     = OCIResult($result, "src");
	$town    = OCIResult($result, "town");


// parse and reformat where string 
// "    3.47 km (WNW 68.5? Elv) from Borrego Springs, CA " becomes
// "  3.5 km NW  of Borrego Springs, CA"
// New format as of 6/13/07
// "   74.0 km (  46.0 mi) SSW ( 194. azimuth) from Santa Rosa Is., CA "
// "   14.4 km (   9.0 mi) ENE (  66. azimuth) from Palomar Observatory, CA "
//  012345678901234567890123456789012345678901234567890

$km = substr($town, 0,10);
$az = substr($town, 22, 3);
$ref= chop(substr($town, 47));  
//$townStr = sprintf ("%5.1f km %3s of %s", $km, substr($town, 13,3), chop(substr($town, 33)));
$townStr = sprintf ("%5.1f km %3s of %s", $km, $az, $ref);
// format the html



	$anchor = "<a href=\"makeSimpleWaveview.php?EVID=$evid\" name=\"$evid\">";

//	$evidstr = sprintf ("%10d", $evid);
	$evidstr = sprintf ("%8d", $evid);
	$sz = sprintf("%1.1f", $z);      // only way to right-justified!
	$srms = sprintf("%1.2f", $rms);

	// format an output row 
	$str1 = sprintf ("%s <b>%2.1f</b> M%s %19s %2s %2s" ,
	         $evidstr, $mag, $magtype, $ot, $type, $gtype);
	$str2 = sprintf ("<i> %3.3s %1s %1.4f %1.4f %4s %3d %4s</i>",
	         $src, $rflag, $lat, $lon, $sz, $ndef, $srms);

	$str2 .= "<br>$townStr";

	echo "\n$anchor";
	echo "$str1";
	echo "</a>$break";

// Highlight if ML > value defined above
	if ($mag > $redMag) {
  		echo "<FONT COLOR=red>";
		echo "$str2";
  		echo "</FONT>";
	} else {	

		echo "$str2";
	}	

	$idList[$knt++] = $evid;

	}  // end of while loop


// disconnect
db_logoff();

	echo "</pre>\n"; 

	return $knt;

}   // end of function format_catalog_html()

// end of PHP  ===================================================
?>
</html>
