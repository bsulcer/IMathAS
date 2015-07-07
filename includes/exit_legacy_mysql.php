<?php
// teardown legacy mysql connection
if (isset($link)) {
	mysql_close($link);
}
