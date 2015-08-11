<?php
//LTI Tool Provider interface customized for carnegiehub.org

header('P3P: CP="ALL CUR ADM OUR"');

require_once(__DIR__ . '/../config_local.php');
require_once(__DIR__ . '/../includes/db.php');
require_once(__DIR__ . '/../includes/utils.php');

$urlmode = utils_detect_url_scheme();
$imas_root_url = $urlmode . $_SERVER['HTTP_HOST'] . $imasroot;

function load_session($sessionid, $die_on_miss = true) {
	$result = db_query_one('SELECT sessiondata, userid '
		. 'FROM imas_sessions WHERE sessionid = ?',
		array($sessionid));
	if ($result === false) {
        if ($die_on_miss) {
		    die('No authorized session exists. This is most likely caused by '
			    . 'your browser blocking third-party cookies.  Please adjust '
			    . 'your browser settings and try again.');
        }
        else {
            return array(null, null);
        }
	}
	else {
		$sessiondata = unserialize(base64_decode($result['sessiondata']));
		$userid = $result['userid'];
		return array($sessiondata, $userid);
	}
}

function update_session($sessionid, $sessiondata, $tzoffset, $tzname) {
	$sessionid = (int) $sessionid;
	$sessiondata = base64_encode(serialize($sessiondata));
	$tzoffset = (string) $tzoffset;
	$tzname = (string) $tzname;
	db_update('UPDATE imas_sessions SET sessiondata = ?, tzoffset = ?, '
		. 'tzname = ? WHERE sessionid = ?',
		array($sessiondata, $tzoffset, $tzname, $sessionid));
}

function create_or_update_session($sessionid, $sessiondata, $userid) {
}

function update_last_access($userid, $time) {
	$userid = intval($userid);
	$time = is_null($time) ? time() : intval($time);
	db_update('UPDATE imas_users SET lastaccess = ? WHERE id = ?',
		array($time, $userid));
}

function redirect($path) {
    global $imas_root_url;
    $url = $imas_root_url . $path;
    error_log("redirecting to {$url}");
	header("Location: {$url}");
	exit;
}

function find_course_for_assessment($aid) {
	return db_query_scalar('SELECT courseid FROM imas_assessments '
		. 'WHERE id = ?', array($aid));
}

function find_course($sis_courseid) {
    $enrollkey = "sisid:{$sis_courseid}";
    return db_query_scalar('SELECT id from imas_courses '
        . 'WHERE enrollkey = ?', array($enrollkey));
}

function find_lti_user($ltiorg, $ltiuserid) {
	$orgparts = explode(':', $ltiorg);
	$shortorg = $orgparts[0];
	return db_query_scalar('SELECT userid FROM imas_ltiusers '
		. 'WHERE org LIKE ? AND ltiuserid = ? '
		. 'ORDER BY id', array("{$shortorg}:%", $ltiuserid));
}

function find_or_create_user($sis_userid, $ltirole, $first_name, $last_name, $email) {
	$username = "sisid:{$sis_userid}";
	$userid = db_query_scalar('SELECT id from imas_users '
		. 'WHERE SID = ?', array($username));
	if (is_null($userid)) {
		if ($ltirole == 'instructor') {
			$rights = 20;
		}
		else {
			$rights = 10;
		}
		$userid = db_insert('INSERT INTO imas_users '
			. '(SID, password, rights, FirstName, LastName, '
			. 'email, msgnotify) VALUES '
			. '(?, ?, ?, ?, ?, ?, ?)',
			array($username, 'pass', $rights, $first_name,
				$last_name, $email, 0));
	}
	return $userid;
}

function log_content_tracking() {
    db_insert('INSERT INTO imas_content_track '
        . '(userid, courseid, type, typeid, viewtime, info) '
        . '(?, ?, ?, ?, ?, ?)',
        array($userid, $courseid, $type, $typeid, $viewtime, $info));
}

function find_or_create_lti_course($org, $contextid, $courseid) {
	$orgparts = explode(':', $org);
	$shortorg = $orgparts[0];
    $found_courseid = db_query_scalar('SELECT courseid from imas_lti_courses '
        . 'WHERE org LIKE ? and contextid = ?',
        array("{$shortorg}%", $contextid));
    if (is_null($found_courseid)) {
        db_insert('INSERT INTO imas_lti_courses (org, contextid, courseid) '
            . 'VALUES (?, ?, ?)', array($org, $contextid, $courseid));
    }
    else if ($found_courseid != $courseid) {
        error_log("course id mismatch in lti_courses; expected {$courseid}, found {$found_courseid}");
        die('Course linkage broken.');
    }
}

function find_or_create_lti_placement($org, $contextid, $linkid, $type, $typeid) {
	$orgparts = explode(':', $org);
	$shortorg = $orgparts[0];
    $result = db_query_one('SELECT placementtype, typeid from imas_lti_placements '
        . 'WHERE org LIKE ? and contextid = ? AND linkid = ?',
        array("{$shortorg}%", $contextid, $linkid));
    if (is_null($result)) {
        db_insert('INSERT INTO imas_lti_placements (org, contextid, linkid, typeid, placementtype) '
            . 'VALUES (?, ?, ?, ?, ?)', array($org, $contextid, $linkid, $typeid, $type));
    }
    else if ($result[0] != $type || $result[1] != $typeid) {
        die('Placement linkage broken.');
    }
}

function find_or_create_enrollment($courseid, $userid, $role, $section) {
    if ($role == 'instructor') {
        $id = db_query_scalar('SELECT id from imas_teachers '
            . 'WHERE userid = ? and courseid = ?',
            array($userid, $courseid));
        if (is_null($id)) {
            db_insert('INSERT INTO imas_teachers (userid, courseid) '
                . 'VALUES (?, ?)', array($userid, $courseid));
        }
    }
    else if ($role == 'teachingassistant') {
        $id = db_query_scalar('SELECT id from imas_tutors '
            . 'WHERE userid = ? and courseid = ?',
            array($userid, $courseid));
        if (is_null($id)) {
            db_insert('INSERT INTO imas_tutors (userid, courseid, section) '
                . 'VALUES (?, ?)', array($userid, $courseid, $section));
        }
    }
    else {
        $id = db_query_scalar('SELECT id from imas_students '
            . 'WHERE userid = ? and courseid = ?',
            array($userid, $courseid));
        if (is_null($id)) {
            db_insert('INSERT INTO imas_students (userid, courseid, section) '
                . 'VALUES (?, ?, ?)', array($userid, $courseid, $section));
        }
    }
}

function find_or_create_ltiuser($ltiorg, $ltiuserid, $userid) {
	$orgparts = explode(':', $ltiorg);
	$shortorg = $orgparts[0];
    $found_userid = db_query_scalar('SELECT userid from imas_ltiusers '
        . 'WHERE org like ? AND ltiuserid = ?',
        array("${shortorg}:%", $ltiuserid));
    if (is_null($found_userid)) {
        db_insert('INSERT INTO imas_ltiusers (org, ltiuserid, userid) '
            . 'VALUES (?, ?, ?)', array($ltiorg, $ltiuserid, $userid));
    }
    else if ($found_userid != $userid) {
        die('User linkage broken.');
    }
}

function do_launch_redirect($sessiondata) {
	if ($sessiondata['ltiitemtype'] == 0) { //is aid
		$aid = $sessiondata['ltiitemid'];
		$cid = find_course_for_assessment($aid);
		if ($sessiondata['ltirole'] == 'learner') {
            log_content_tracking($userid, $cid, 'assesslti', $aid, $now, '');
		}
		redirect("/assessment/showtest.php?cid={$cid}&id={$aid}");
    }
    else if ($sessiondata['ltiitemtype'] == 1) { //is cid
		$cid = $sessiondata['ltiitemid'];
		redirect("/course/course.php?cid={$cid}");
	} else { //will only be instructors hitting this option
		redirect("/ltihome.php");
	}
}

$sessionid = utils_start_session();
$atstarthasltiuserid = isset($_SESSION['ltiuserid']);
$askforuserinfo = false;

if (isset($_GET['accessibility'])) {
    error_log('case 1');
	list($sessiondata, $userid) = load_session($sessionid);
	include('accessibility.php');
	exit;
}
else if (isset($_GET['launch'])) {
    error_log('case 2');
	list($sessiondata, $userid) = load_session($sessionid);

	if ($_POST['access']==1) { //text-based
		 $sessiondata['mathdisp'] = $_POST['mathdisp'];
		 $sessiondata['graphdisp'] = 0;
		 $sessiondata['useed'] = 0;
	 } else if ($_POST['access']==2) { //img graphs
		 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
		 $sessiondata['graphdisp'] = 2;
		 $sessiondata['useed'] = 1;
	 } else if ($_POST['access']==4) { //img math
		 $sessiondata['mathdisp'] = 2;
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = 1;
	 } else if ($_POST['access']==3) { //img all
		 $sessiondata['mathdisp'] = 2;
		 $sessiondata['graphdisp'] = 2;
		 $sessiondata['useed'] = 1;
	 } else {
		 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
		 $sessiondata['graphdisp'] = $_POST['graphdisp'];
		 $sessiondata['useed'] = 1;
	 }

	$tzname = array_get($_POST, 'tzname', '');
	$tzoffset = array_get($_POST, 'tzoffset', '');
	update_session($sessionid, $sessiondata, $tzoffset, $tzname);
    do_launch_redirect($sessiondata);
}
else if (isset($_SESSION['ltiuserid']) && !isset($_REQUEST['oauth_consumer_key'])) {
    error_log('case 3');
	list($sessiondata, $userid) = load_session($sessionid);
	$keyparts = explode('_', $_SESSION['ltikey']);
    // fall through to launch code below
} else {
    error_log('case 4');
	//not postback of new LTI user info, so must be fresh request
	//verify necessary POST values for LTI.  OAuth specific will be checked later
    // NOTE: could probably use standard lis_*_sourcedid fields instead of custom
	$required_lti_params = array('user_id', 'context_id', 'roles',
		'oauth_consumer_key', 'lis_person_name_given',
		'lis_person_name_family', 'lis_person_contact_email_primary',
		'custom_carnegiehub_user_id', 'custom_carnegiehub_course_id');
	foreach ($required_lti_params as $param) {
		if (empty($_REQUEST[$param])) {
			die("Invalid launch parameters: {$param} is required");
		}
	}
	$ltiuserid = $_REQUEST['user_id'];
	$ltirole = $_REQUEST['roles'];
	$ltikey = $_REQUEST['oauth_consumer_key'];
	$ltiorg = array_get($_REQUEST, 'tool_consumer_instance_guid', 'Unknown');
	$carnegiehub_userid = $_REQUEST['custom_carnegiehub_user_id'];
	$carnegiehub_courseid = $_REQUEST['custom_carnegiehub_course_id'];

	if (isset($_SESSION['ltiuserid']) && $_SESSION['ltiuserid'] != $ltiuserid) {
		//new user - need to clear out session
		utils_clear_session();
	}

	//check OAuth Signature!
	require_once(__DIR__ . '/../includes/OAuth.php');
	require_once('ltioauthstore.php');

	//set up OAuth
	$store = new IMathASLTIOAuthDataStore();
	$server = new OAuthServer($store);
	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$request = OAuthRequest::from_request();
	$base = $request->get_signature_base_string();
	try {
		$requestinfo = $server->verify_request($request);
	} catch (Exception $e) {
		reporterror($e->getMessage());
	}
	$store->mark_nonce_used($request);

	$keyparts = explode('_',$ltikey);
	$_SESSION['ltiorigkey'] = $ltikey;

    $courseid = find_course($carnegiehub_courseid);
    if (is_null($courseid)) {
        die('Invalid course.');
    }
	$_SESSION['ltilookup'] = 'u';
	$ltiorg = "{$ltikey}:{$ltiorg}";
	$keytype = 'g';
	if (isset($_REQUEST['custom_place_aid'])) {
		$placeaid = intval($_REQUEST['custom_place_aid']);
		$keytype = 'cc-g';
		$sourcecid = find_course_for_assessment($placeaid);
        if (is_null($sourcecid)) {
            die('Invalid assessment.');
        }
        if ($sourcecid != $courseid) {
            die('Invalid assessment for course.');
        }
		$_SESSION['place_aid'] = array($sourcecid, $_REQUEST['custom_place_aid']);
    }
    else {
        $_SESSION['place_cid'] = $courseid;
    }

	//Store all LTI request data in session variable for reuse on submit
	//if we got this far, secret has already been verified
	$_SESSION['ltiuserid'] = $ltiuserid;
	$_SESSION['ltiorg'] = $ltiorg;
	$ltirole = strtolower($_REQUEST['roles']);
	if (strpos($ltirole, 'instructor') !== false || strpos($ltirole, 'administrator') !== false) {
		$ltirole = 'instructor';
	} else {
		$ltirole = 'learner';
	}
	if (strpos($ltirole, 'teachingassistant') !== false) {
        $_SESSION['ltiorigrole'] = 'teachingassistant';
    }
    else {
        $_SESSION['ltiorigrole'] = $ltirole;
    }

	$_SESSION['ltirole'] = $ltirole;
	$_SESSION['lti_context_id'] = $_REQUEST['context_id'];
	$_SESSION['lti_context_label'] = (!empty($_REQUEST['context_label']))?$_REQUEST['context_label']:$_REQUEST['context_id'];
	$_SESSION['lti_resource_link_id'] = $_REQUEST['resource_link_id'];
	$_SESSION['lti_lis_result_sourcedid'] = $_REQUEST['lis_result_sourcedid'];
	$_SESSION['lti_outcomeurl'] = $_REQUEST['lis_outcome_service_url'];
	$_SESSION['lti_key'] = $ltikey;
	$_SESSION['lti_keytype'] = $keytype;
	$_SESSION['lti_keyrights'] = $requestinfo[0]->rights;
	$_SESSION['lti_keygroupid'] = intval($requestinfo[0]->groupid);
	if (isset($_REQUEST['selection_directive']) && $_REQUEST['selection_directive']=='select_link') {
		$_SESSION['selection_return'] = $_REQUEST['launch_presentation_return_url'];
	}

	$userid = find_lti_user($ltiorg, $ltiuserid);
	if (is_null($userid)) {
		$userid = find_or_create_user($carnegiehub_userid,
			$ltirole,
			$_REQUEST['lis_person_name_given'],
			$_REQUEST['lis_person_name_family'],
			$_REQUEST['lis_person_contact_email_primary']);
		find_or_create_ltiuser($ltiorg, $ltiuserid, $userid);
	}
	$_SESSION['ltikey'] = $ltikey;
    // fall through to launch code below
}

//if here, we know the local userid.

//if it's a common catridge placement and we're here, then either we're using domain credentials, or
//course credentials for a non-source course.

//see if lti_courses is created
//  if not, see if source cid is instructors course
// 	if so, set lti_course
//	if not, create a new blank course
//
//see if courseid==source course cid
//  if not, copy assessment into course, set placement
//  if so, set placement

//determine request type, and check availability
$now = time();


$placementtype = null;
$courseid = null;
$typeid = null;
if (array_key_exists('place_aid', $_SESSION)) {
    $placementtype = 'assess';
    $courseid = $_SESSION['place_aid'][0];
    $typeid = $_SESSION['place_aid'][1];
}
else {
    $placementtype = 'course';
    $courseid = $typeid = $_SESSION['place_cid'];
}
find_or_create_lti_course($_SESSION['ltiorg'],
    $_SESSION['lti_context_id'], $courseid);
find_or_create_lti_placement($_SESSION['ltiorg'],
    $_SESSION['lti_context_id'], $_SESSION['lti_resource_link_id'],
    $placementtype, $typeid);
find_or_create_enrollment($courseid, $userid, $_SESSION['ltiorigrole'],
    $_SESSION['lti_context_label']);


error_log("before={$_SESSION['ltiorg']}");
//check if db session entry exists for session
$promptforsettings = false;
list($sessiondata, $session_userid) = load_session($sessionid, false);
$oldsession = $_SESSION;
if (!is_null($sessiondata)) {
    if ($session_userid != $userid || !$atstarthasltiuserid) {
        session_destroy();
        error_log("after={$_SESSION['ltiorg']}");
        session_start();
        session_regenerate_id();
        $sessionid = session_id();
        setcookie(session_name(), session_id());
        $sessiondata = array();
        $createnewsession = true;
    }
    else {
        if (!isset($sessiondata['mathdisp'])) {
            $promptforsettings = true;
        }
        $createnewsession = false;
    }
}
else {
    $sessiondata = array();
    $createnewsession = true;
}

if ($placementtype == 'assess') {
	$sessiondata['ltitlwrds'] = '';
	$sessiondata['ltiitemtype'] = 0;
	$sessiondata['ltiitemid'] = $typeid;
}  else if ($placementtype == 'course') {
	$sessiondata['ltiitemtype'] = 1;
	$sessiondata['ltiitemid'] = $typeid;
} else {
	$sessiondata['ltiitemtype']=-1;
}
$sessiondata['ltiorg'] = $oldsession['ltiorg'];
$sessiondata['ltirole'] = $oldsession['ltirole'];
$sessiondata['lti_context_id']  = $oldsession['lti_context_id'];
$sessiondata['lti_resource_link_id']  = $oldsession['lti_resource_link_id'];
$sessiondata['lti_lis_result_sourcedid']  = stripslashes($oldsession['lti_lis_result_sourcedid']);
$sessiondata['lti_outcomeurl']  = $oldsession['lti_outcomeurl'];
$sessiondata['lti_context_label'] = $oldsession['lti_context_label'];
$sessiondata['lti_launch_get'] = $oldsession['lti_launch_get'];
$sessiondata['lti_key'] = $oldsession['lti_key'];
$sessiondata['lti_keytype'] = $oldsession['lti_keytype'];
$sessiondata['lti_keylookup'] = $oldsession['ltilookup'];
$sessiondata['lti_origkey'] = $oldsession['ltiorigkey'];
if (isset($oldsession['selection_return'])) {
	$sessiondata['lti_selection_return'] = $oldsession['selection_return'];
}

if ($oldsession['lti_keytype']=='gc') {
	$sessiondata['lti_launch_get']['cid'] = $courseid;
}

$enc = base64_encode(serialize($sessiondata));
if ($createnewsession) {
    error_log("creating session {$sessionid} for user {$userid}");
    db_insert('INSERT INTO imas_sessions (sessionid, userid, sessiondata, time) '
        . 'VALUES (?, ?, ?, ?)', array($sessionid, $userid, $enc, time()));
} else {
    error_log("updating session {$sessionid} for user {$userid}");
    db_update('UPDATE imas_sessions SET sessiondata = ?, userid = ? '
       . 'WHERE sessionid = ?', array($enc, $userid, $sessionid));
}
if ($promptforsettings || $createnewsession) {
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?accessibility=ask");
	exit;
}
else {
    do_launch_redirect($sessiondata);
}
