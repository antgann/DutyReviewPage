<?php
/* 
   Event formatting routines

*/
include_once "db_conn.php";
include_once "oracle.php";
include_once "db_utils.php";
include_once "config.php";         // local values

// DDG 6/16/11 
// - get NetCode from config.php

function checkURL($URL) {
  $headers = @get_headers($URL, 1);
  return ( strpos($headers[0],"200") );
  //return ($headers[0] == 'HTTP/1.1 200 OK');
}

// //////////////////////////////////////////////////////////////////////

// Create links to outside products, only if they exist
function format_productLinks($_evid) {
  // added global vars from config.php -aww 2012/04/20
  global $networkCode, $shakeMapURLs, $recEqRootURL, $USGSRootURL;

//NOTE: links need to be customized for the equivalent cicese (CICESE) targets :
  //
  // Recent Earthquakes
  //
  // What about net code case for php filename ? substitute strtoLower($networkCode) ? -aww 2011/09/07
  $netC = strtolower($networkCode);
  $recEqEventURL = "${recEqRootURL}/eventpage/${netC}${_evid}#executive";

  if (checkURL($recEqEventURL)) { 
   echo "<A HREF=\"$recEqEventURL\" Target=\"_new\"><b>Recent Earthquakes</b> page for $_evid</A><BR>\n";
  } else {
   echo "<b>No</b> <a href=\"$recEqRootURL/map\"Target=\"_new\">Recent Earthquakes Page</a><br>\n";
  }

  //
  // ShakeMap: must test for image - mainpage is dynamic an always returns 'true'
  //

  // Event web page is dynamic so will return valid even for bogus IDs
  // :. must test presence of actual *image file*
  // Ex: http://earthquake.usgs.gov/eqcenter/shakemap/sc/shake/<evid>/download/intensity.jpg

  // List of URLs to check for valid ShakeMaps is in config.php
  $no_smap = true;
  $SmapRootURL  = "${recEqRootURL}/shakemap";
  foreach ($shakeMapURLs as $url) {
    $SmapEventURL = $url . "/$_evid";
    $testURL = "${SmapEventURL}/download/intensity.jpg";
    if (checkURL($testURL)) { 
        echo "<A HREF=\"$SmapEventURL\" Target=\"_new\">ShakeMap for $_evid ($url)</A><br />";
        $no_smap = false;
    }
  }
  if ($no_smap) {
    echo "<b>No</b> <a href=\"$SmapRootURL\"Target=\"_new\">ShakeMap</a><br>\n";
  }

  // Did You Feel It?
  // Check the GEOJSON feed from usgs.gov and see if a DYFI product is present

  $dyfiRoot    = "${recEqRootURL}/dyfi/";
  $dyfiEventURL = "${recEqRootURL}/eventpage/${netC}${_evid}#dyfi";
  $dyfiJSONURL = "${USGSRootURL}/fdsnws/event/1/query?eventid=${netC}${_evid}&format=geojson";

  try {
    $dyfiJSON = file_get_contents($dyfiJSONURL);

    if ($dyfiJSON === false) {
    // FAILURE TO GET JSON
    }
  } catch (Exception $e) {
    // Handle exception
  }

  $dyfiexists = strpos($dyfiJSON,"dyfi");
  if ($dyfiexists === false) { 
    echo "<b>No</b> <a href=\"$dyfiRoot\"Target=\"_new\">DYFI</a><br>\n";
  } else {
    echo "<A HREF=\"$dyfiEventURL\" Target=\"_new\">Did-You-Feel-It for $_evid</A><br>\n";    
  }

} 

// ----------------------------------------------- 
function format_CubeEmail($_evid) {
   echo format_CubeEmailToString($_evid);
}

// -----------------------------------------------
 
function format_CubeEmailToString($_evid) {

//  format_CubeEmail($_evid);

/* EXAMPLE:
             >>> UPDATE OF PREVIOUSLY REPORTED EVENT <<<
                   == PRELIMINARY EVENT REPORT ==
 Southern California Seismic Network (SCSN) operated by Caltech and USGS

 Version : This report supersedes any earlier reports of this event.
 This event has been reviewed by a seismologist.

 Magnitude   :   0.96 Ml  (A micro quake)
 Time        :   21 Aug 2005   03:17:53 AM, PDT
             :   21 Aug 2005   10:17:53 GMT
 Coordinates :   33 deg. 30.46 min. N, 116 deg. 31.54 min. W
             :   33.5077 N, 116.5257 W
 Depth       :     8.0 miles ( 12.8 km)
 Quality     :   Execellent
 Event ID    :   CI 14175400
 Location    :     9 mi. ( 14 km) ESE from Anza, CA
             :    21 mi. ( 34 km) S   from Palm Springs, CA
             :    31 mi. ( 50 km) ESE from EASTSIDE RES. QUARRY, CA
             :     0 mi. (  1 km) NE  from San Jacinto fault

More Information about this event and other earthquakes is available at:
 http://www.scsn.org/scsn/scsn.html

ADDITIONAL EARTHQUAKE PARAMETERS
________________________________
rms misfit                   : 0.14 seconds
horizontal location error    : 0.5 km
vertical location error      : 0.9 km
maximum azimuthal gap        :  80 degrees
distance to nearest station  : 7.0 km
event ID                     : CI 14175400

*/
  $msg = "";

  db_connect_glob();   // connect to dbase using info from db_conn.php

  // The dbase query
  $sql_query="SELECT Formats.getCubeEmailFormat($_evid) \"msg\" from dual";

//DEBUG //echo $sql_query;

  // execute the query
  $result = db_query_glob($sql_query);

// for each row returned
	while (OCIFetch($result)) {

   	  // parse the individual attributes by name
	  $msg .= OCIResult($result, "msg");

	} // end of while loop

  db_logoff(); // disconnect

  return $msg;
 }
// //////////////////////////////////////////////////////////////////////

function format_event_history ($_evid) {
/*
 * Format the output

14175400 1.0 Ml Jig 2005/08/21 10:17:53 33.5077 -116.5257 12.8 23 0.14 80 le F (12436808)<-

*/

  //connect to dbase using info from db_conn.php
  db_connect_write(); // NOTE to use call to magpref package need power user -aww 2015/10/27

// The dbase query
$sql_query="SELECT distinct
	Event.evid \"evid\", 
	NetMag.magnitude \"mag\", NetMag.magtype \"mt\", Netmag.magid \"magid\",
        Netmag.nsta \"nsta\", Netmag.magalgo \"algo\",
        Netmag.uncertainty \"mrms\", 
        TrueTime.nominal2string(origin.datetime) \"ot\", 
        Origin.lat \"lat\", Origin.lon \"lon\", Origin.depth \"z\",
	Origin.ndef \"ndef\", Origin.wrms \"rms\", Origin.gap \"gap\", 
	Event.etype \"type\", Origin.gtype \"gtype\", Origin.rflag \"rflag\", Origin.subsource \"src\",
	Origin.orid \"orid\", Event.prefor\"prefor\", Event.prefmag \"prefmag\",
        Event.selectflag \"selectFlag\", Origin.lddate \"odate\", Netmag.lddate \"mdate\",
        magpref.getMagPriority(Netmag.magid) \"priority\"
        FROM Event, Origin, NetMag WHERE 
        (Event.evid = Origin.evid) AND (Origin.orid = NetMag.orid(+)) AND (Event.evid = $_evid)
        ORDER BY Origin.lddate, NetMag.lddate, NetMag.magid, Origin.orid
";

// execute the query
$result = db_query_glob($sql_query);

        // Title
	echo "<b>        evid mag    pri #st #mrms magalgo   src  origin-datetime     lat     lon         z #ph  rms  gap et gt r lddate of magnitude (orid/magid)</b><br>";

// for each row returned
	while (OCIFetch($result)) {

	// parse the individual attributes by name
	$evid    = OCIResult($result, "evid");
	$mag     = OCIResult($result, "mag");
	$magtype = OCIResult($result, "mt");
	$magid   = OCIResult($result, "magid");
	$nsta    = OCIResult($result, "nsta");
	$algo    = OCIResult($result, "algo");
	$mrms    = OCIResult($result, "mrms");
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
	$orid    = OCIResult($result, "orid");
	$prefor  = OCIResult($result, "prefor");	
	$prefmag = OCIResult($result, "prefmag");	
	$selectFlag = OCIResult($result, "selectFlag");
	$odate  = OCIResult($result, "odate");
	$mdate  = OCIResult($result, "mdate");
	$priority  = OCIResult($result, "priority");

	// do some pre-formatting
	//$sz = sprintf("%1.1f", $z);      // only way to right-justified!
	//$srms = sprintf("%1.2f", $rms);

	# use ASCII arrow to indicate preferred solution
	if($orid == $prefor) {
	  $arrow = " >";
          if ($magid == $prefmag) {
	    $arrow = "=>";
          }
	} else {
          if ($magid == $prefmag) {
	    $arrow = "= ";
          }
          else {
	     $arrow = "  ";
          }
	}


            // NOTE: php rounding math is wrong, so add 0.000001 to $mag to force a round up, but
            // better would be to instead  change default precision=14 to 17 in installed php lib:
            // /usr/local/php/lib/php.ini -aww 20150123
            $mag += .000001;

	$str = sprintf ("%2.2s%10d %2.1f M%s %3d %3d %5.2f %-9.9s %-4.4s %19s %6.3f %8.3f %5.1f %3d %5.2f %3d %2s %2s %1s %s (%d/%d)" ,
	 $arrow, $evid, $mag, $magtype, $priority, $nsta, $mrms, $algo, $src, $ot, $lat, $lon, $z, $ndef, $rms, $gap, 
 	 $type, $gtype, $rflag, $mdate, $orid, $magid);

	 // dumpt the string
	 echo "$str\n";	

	} // end of while loop

	if ($selectFlag == 0) {
	   echo "*EVENT DELETED*\n";
	}


  db_logoff(); // disconnect
 }
// //////////////////////////////////////////////////////////////////////

function format_rt_alarms($_evid) {

    $rtdb = getMasterRTdb();

    if ($rtdb == 'unknown')
        return;

    print "<b>Alarm Action         State       Cnt Time  (from: $rtdb)</b>\n";
    format_alarms($_evid, $rtdb);

}

function format_dc_alarms($_evid) {

   $dcdb = getDefDbname();
   print "<b>Alarm Action         State       Cnt Time  (from: $dcdb)</b>\n";
   format_alarms($_evid, $dcdb);

}

function formatSecondaryAlarms($_evid) {

    global $secondaryAlarmDBS;

    foreach ( $secondaryAlarmDBS as $db ) {
       print "<b>Alarm Action         State       Cnt Time  (from: $db)</b>\n";
       format_alarms($_evid, $db);
       print "<br>\n";
    }

}

// //////////////////////////////////////////////////////////////////////

function format_alarms($_evid, $_dbase) {

    if ($_dbase == 'unknown') {
          return;
    }

    //db_connect_glob();   // connect to default dbase
    db_connect_read_db($_dbase);   // connect to input dbase

    // The dbase query
    $sql_query= "Select event_id \"id\", alarm_action \"action\", action_state \"state\", 
               mod_time \"time\", modcount \"count\"
               from Alarm_Action 
               WHERE event_id = $_evid 
               order by mod_time, alarm_action";

    //error_log("DEBUG $sql_query ",0);
    //print "<h2>DEBUG: $sql_query</h2>\n";
    // execute the query
    //$result = db_query_glob($sql_query);
    $result = db_query_glob($sql_query);

    // for each row returned
    while (OCIFetch($result)) {

	// parse the individual attributes by name
	$action  = OCIResult($result, "action");
	$state   = OCIResult($result, "state");
	$count   = OCIResult($result, "count");
	$time    = OCIResult($result, "time");

	$str = sprintf ("%-20s %-12s %2s %-s ", $action, $state, $count, $time);
    	echo "$str\n";

    } // end of while loop

    db_logoff(); // disconnect
}

function getODate($_evid) {

$sql_query="SELECT lddate \"odate\" FROM Origin WHERE evid = $_evid";

    $str = 'false';

    db_connect_glob();
    // execute the query
    $result = db_query_glob($sql_query);

    // for each row returned - should only be one
	OCIFetch($result);
        $str = OCIResult($result, "odate");
    
    db_logoff();

    return $str; 
}

?>
