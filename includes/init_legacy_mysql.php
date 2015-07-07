<?php
// initialize a global connection for the deprecated (and soon to be removed) mysql library
$link = mysql_connect($dbserver, $dbusername, $dbpassword)
    or die("<p>Could not connect : " . mysql_error() . "</p></div></body></html>");
mysql_select_db($dbname)
    or die("<p>Could not select database</p></div></body></html>");
unset($dbserver);
unset($dbusername);
unset($dbpassword);
