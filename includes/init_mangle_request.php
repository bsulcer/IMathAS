<?php
// emulate the old magic_quotes (mis)feature by mangling the request globals
// NOTE: this is a stop-gap to deal with insecure SQL query composition
function addslashes_deep($value) {
    return (is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value));
}
if (!get_magic_quotes_gpc()) {
    $_GET = array_map('addslashes_deep', $_GET);
    $_POST  = array_map('addslashes_deep', $_POST);
    $_COOKIE = array_map('addslashes_deep', $_COOKIE);
}
