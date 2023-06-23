<?php
//
// make the page to send email announcing duty change
//
// to:   duty_ont@eqinfo.wr.usgs.gov
// from: seismologist@eqinfo.wr.usgs.gov
//
//
//  Script has hard-coded host network dependencies
//

include_once "phpmods/config.php";

$remoteUser = $_SERVER["REMOTE_USER"];  

$defaultText = "The Southern California Duty Seismologist has changed.";


?>

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
  <head>
    <title>Send Duty-On E-mail</title>
  </head>

  <body>
    <h1>Send Duty-On E-mail</h1>

<h3> 
Send e-mail notification of Duty change. <br>
<b>Type e-mail text below:</b>
</h3>

<FORM ACTION="formmail.pl" METHOD="POST" >

      <TABLE>
	<TR> 
	  <TD>To:</TD>
	  <TD> &nbsp </TD>
	  <TD> <INPUT TYPE=text NAME="recipient" SIZE=25 Value="<?php echo $toAddrDuty ?>"> 
	  </TD>
	</TR>

	<TR>
	  <TEXTAREA name=" " style="height: 300px;" Rows=20 Cols=40><?php echo $defaultText?></TEXTAREA>
	  </TR>
</TABLE> 

<INPUT Type=HIDDEN Name="subject" Value="SoCal Duty Seismologist Changed">
<INPUT Type=HIDDEN Name="email"   Value="<?php echo $fromAddrDuty ?>">
<INPUT Type=HIDDEN Name="print_config"   Value=" ">
<INPUT Type=HIDDEN Name="redirect"   Value="mailsent.html">

<!-- If you put a name on this it will show up in the response page AND in the message itself!
 <INPUT TYPE=SUBMIT Name="subButton" Value="Send e-mail"> -->

      <TABLE>
	<TR> 
	  <TD>
		<INPUT TYPE=SUBMIT Value="Send e-mail">
	  </TD>
	  <TD> &nbsp </TD>

	</TR>
      </TABLE>

</FORM>

  </body>
</html>
