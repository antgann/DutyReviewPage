<?php
//
// make the page to view, edit, and send event email
//
// to:   felt@eqinfo.wr.usgs.gov
// from: seismologist@eqinfo.wr.usgs.gov
//
//  The script that actually sends the mail is formmail.pl
//
//  Script has hard-coded host network dependencies


include_once "phpmods/format_events.php";
include_once "phpmods/config.php";

$evid   = $_GET["EVID"];

// Get the default the email text for the event
$emailtext = format_CubeEmailToString($evid);


echo <<< EOFx
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
  <head>
    <title>Send mail $evid</title>
  </head>

  <body>
    <h2>Send mail for event $evid</h2>

<h3> 
Send mail to <b>FELT</b> e-mail list. Edit this form as necessary. Include comments at the bottom.
</h3>

<FORM ACTION="formmail.pl" METHOD="POST" >

      <TABLE>
	<TR> 
	  <TD>To:</TD>
	  <TD> &nbsp </TD>
	  <TD> <INPUT TYPE=text NAME="recipient" SIZE=25 Value="$toAddrFelt"> 
	  </TD>
	</TR>
</TABLE> 
<TEXTAREA name=" " Rows=30 Cols=80>
$emailtext
</TEXTAREA>

<INPUT Type=HIDDEN Name="subject" Value="CIT/USGS Earthquake Message">
<INPUT Type=HIDDEN Name="email"   Value="$fromAddrFelt">
<INPUT Type=HIDDEN Name="print_config"   Value=" ">
<INPUT Type=HIDDEN Name="redirect"   Value="mailsent.html">

      <TABLE>
	<TR> 
	  <TD>
		<INPUT TYPE=SUBMIT Value="Send e-mail">
	  </TD>
	  <TD> &nbsp </TD>
	  <TD>
		<INPUT TYPE=BUTTON Value="Cancel" onClick="window.close()">
	  </TD>
	</TR>
      </TABLE>

</FORM>

  </body>
</html>
EOFx;
?>
