<html>
<head>
<pre>
<?php

// Get the CUBE format e-mail message from the dbase
// Dumps text with only <pre> formatting

// Invoked with:  "cgi-bin/cubeEmail.php?&EVID=xxxxx"


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

include_once "phpmods/format_events.php";

$evid   = $_GET["EVID"];

// dump CUBE format
format_CubeEmail($evid);

?>
</pre>
</head>
</html>
