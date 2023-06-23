<!-- Port of logAsHtml.cgi to php. -->

<html>
    <head>
        <title>Review Log File</title>
    </head>
    <body>
        <pre>


<?php
    include_once("phpmods/config.php");
    $today = date("Y-m-d");

    $logArray = array_reverse(file($logFile));

    foreach ($logArray as $line) {
	$datePos = strpos($line, '@') + 2;
	if( strtotime(date("Y-m-d", strtotime(substr($line, $datePos, 10))) . " +3 month") > time() )
	{
	    print $line;
	}		
    }

?>


        </pre>
    </body>
</html>

