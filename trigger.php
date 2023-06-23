<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"> 
<!-- This defines the frames for the Trigger Review Page
     The waves & buttons frames are event specific and created by triggerCatalog.php  -->

<html>
  <head>
<?php
    include_once "cgi-bin/phpmods/config.php";

    print "<title>$networkName Trigger Review Page</title>";
 ?>
  </head>
  <frameset rows="75%,*" style="margin:2">
   <frameset cols="80%,*">
    <frame src="cgi-bin/blankPage.html" name="waves">
    <frameset rows="90%,*" style="margin:0"BORDER=0 FRAMEBORDER=0 FRAMESPACING=0 >
      <frame src="cgi-bin/blankPage.html" name="buttons">
      <frame  name="statusframe">
   </frameset>
   </frameset>
   <frame src="cgi-bin/catalogTrigger.php" name="catalog">
  </frameset>
</html>

<!--
   FRAME MAP

+---------------------------------+-------------+
| [waves]                         | [buttons]   |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 +-------------+
|                                 | [status]    |
+---------------------------------+-------------+
| [catalog]                                     |
|                                               |
|                                               |
+-----------------------------------------------+

DDG - added status frame 6/29/06
-->
