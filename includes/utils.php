<?php
function utils_start_session() {
	if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
	ini_set('session.gc_maxlifetime',86400);
	ini_set('auto_detect_line_endings',true);
 
	if ($_SERVER['HTTP_HOST'] != 'localhost') {
		session_set_cookie_params(0, '/', '.'.implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
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
