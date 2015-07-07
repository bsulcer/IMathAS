<?php
require_once(__DIR__ . '/../includes/OAuth.php');
require_once(__DIR__ . '/../includes/db.php');

class IMathASLTIOAuthDataStore extends OAuthDataStore {
	function lookup_consumer($consumer_key) {
        $row = db_query_one('SELECT password, rights, groupid '
            . 'FROM imas_users WHERE SID = ? '
            . 'AND (rights = 11 OR rights = 76 OR rights = 77)',
            array($consumer_key));
        if (is_null($row)) {
            return null;
        }
        else {
            return new OAuthConsumer($consumer_key,
                $row['password'], null, $row['rights'],
                $row['groupid']);
        }
	}

	function lookup_token($consumer, $token_type, $token) {
		return new OAuthToken($consumer, "");
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        return db_query_scalar('SELECT id FROM imas_ltinonces '
            . 'WHERE nonce = ?', array($nonce));
	}

	function record_nonce($nonce) {
		$now = time();
		db_insert('INSERT INTO imas_ltinonces '
            . '(nonce, time) VALUES (?, ?)',
            array($nonce, $now));
		db_update('DELETE FROM imas_ltinonces '
            . 'WHERE time < ?',
            array($now - (60 * 90)));
	}

	function mark_nonce_used($request) {
		$nonce = @$request->get_parameter('oauth_nonce');
		$this->record_nonce($nonce);
	}

	function new_request_token($consumer) {
		return NULL;
	}

	function new_access_token($token, $consumer) {
		return NULL;
	}
}
