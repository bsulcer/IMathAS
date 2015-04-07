<?php

function utils_get_cfg($section, $name, $default = null) {
	global $CFG;
	if (isset($CFG[$section]) && isset($CFG[$section][$name])) {
		return $CFG[$section][$name];
	} else {
		return $default;
	}
}

function utils_start_session() {
	global $sessionpath;
	if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
	ini_set('session.gc_maxlifetime',86400);
	ini_set('auto_detect_line_endings',true);
 
	$domainlevel = utils_get_cfg('GEN', 'domainlevel', -2);
	if ($_SERVER['HTTP_HOST'] != 'localhost' && $domainlevel != 0) {
		session_set_cookie_params(0, '/', '.'.implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),$domainlevel)));
 	}

	session_start();
	return session_id();
}

function utils_detect_url_scheme() {
	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		return 'https://';
	} else {
		return 'http://';
	}
}
