<html><head>

<?php

// Display the history of an event
// Dumps text with only <pre> formatting

// Invoked with:  "cgi-bin/eventhist.php?&EVID=xxxxx"

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";

$evid   = $_GET["EVID"];

// ----- Extract catalog data from the dbase -------

// connect to dbase using info from db_conn.php
db_connect_glob();

// format the results as html
format_event_history($evid);

// disconnect
db_logoff();


// //////////////////////////////////////////////////////////////////////

function format_event_history ($_evid) {
/*
 * Format the output

14175400 1.0 Ml Jig 2005/08/21 10:17:53 33.5077 -116.5257 12.8 23 0.14 80 le F (12436808)<-

*/

// The dbase query
$sql_query="SELECT /*+ FIRST_ROWS */ 
	Event.evid \"evid\", 
	NetMag.magnitude \"mag\", 
	NetMag.magtype \"mt\", 
        TrueTime.nominal2string(origin.datetime) \"ot\", 
        Origin.lat \"lat\", Origin.lon \"lon\", Origin.depth \"z\",
	Origin.ndef \"ndef\",
        Origin.wrms \"rms\", Origin.gap \"gap\", 
	Event.etype \"type\", 
	Origin.gtype \"gtype\", 
        Origin.rflag \"rflag\",
         Origin.subsource \"src\",
	Origin.orid \"orid\", Event.prefor\"prefor\",
        Event.selectflag \"selectFlag\", Origin.lddate \"lddate\"

        FROM Event, Origin, NetMag 
        WHERE 
        (Event.evid = Origin.evid(+)) and (Origin.prefmag = NetMag.magid(+))
        and Event.evid = $_evid

        Order by Origin.lddate, NetMag.lddate
";

// execute the query
$result = db_query_glob($sql_query);

echo "<pre>\n";

	echo "      evid  mag    src date       time     lat     lon       z     #ph  rms  gap et gt r orid      \n";

// for each row returned
	while (OCIFetch($result)) {

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
	$gtype    = OCIResult($result, "gtype");
	$rflag   = OCIResult($result, "rflag");
	$src     = OCIResult($result, "src");
	$orid    = OCIResult($result, "orid");
	$prefor  = OCIResult($result, "prefor");	
	$selectFlag = OCIResult($result, "selectFlag");
	$lddate  = OCIResult($result, "lddate");

	// do some pre-formatting
	$evidstr = sprintf ("%10d", $evid);
	$sz = sprintf("%1.1f", $z);      // only way to right-justified!
	$srms = sprintf("%1.2f", $rms);

            // NOTE: php rounding math is wrong, so add 0.000001 to $mag to force a round up, but
            // better would be to instead  change default precision=14 to 17 in installed php lib:
            // /usr/local/php/lib/php.ini -aww 20150123
            $mag += .000001;

	$str = sprintf ("%s %2.1f M%s %3.3s %19s %1.4f %1.4f %5s %3d %5s %3d %2s 2s %1s (%s)" ,
	 $evidstr, $mag, $magtype, $src, $ot, $lat, $lon, $sz, $ndef, $srms, $gap, 
 	 $type, $gtype, $rflag, $orid);

	# use ASCII arrow to indicate preferred solution
	if($orid == $prefor) {
	  $arrow = "<-";
	} else {
	  $arrow = "  ";
	}

	// dumpt the string
	echo "$str$arrow\n";	

	} // end of while loop

	if ($selectFlag == 0) {
	   echo "*DELETED*\n";
	}
	echo "</pre>\n";
 }

?>
