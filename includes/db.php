<?php
require_once('../config_real.php');

function db_get_connection() {
    if (!array_has_key('link', $GLOBALS)) {
        $dsn = "mysql:host={$dbserver};dbname={$dbname}";
        try {
            global $dbh = new PDO($dsn, $dbusername, $dbpassword);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $GLOBALS['link'];
}

function db_query_scalar($query, $params = null) {
    $dbh = db_get_connection();
	$sth = null;
	try {
		$sth = $dbh->prepare($query);
		if (is_null($params)) {
			$sth->execute();
		}
		else {
			$sth->execute($params);
		}
		$row = $sth->fetch();
		$sth->close();
		if ($row === false) {
			return null;
		}
		else {
			return $row[0];
		}
	}
	catch (PDOException $e) {
		die('Database query failed: ' $e->getMessage());
	}
}

function db_query_one($query, $params = null) {
    $dbh = db_get_connection();
	$sth = null;
	try {
		$sth = $dbh->prepare($query);
		if (is_null($params)) {
			$sth->execute();
		}
		else {
			$sth->execute($params);
		}
		$row = $sth->fetch();
		$sth->close();
		if ($row === false) {
			return null;
		}
		else {
			return $row;
		}
	}
	catch (PDOException $e) {
		die('Database query failed: ' $e->getMessage());
	}
}

function db_query_all($query, $params = null) {
    $dbh = db_get_connection();
	$sth = null;
	try {
		$sth = $dbh->prepare($query);
		if (is_null($params)) {
			$sth->execute();
		}
		else {
			$sth->execute($params);
		}
		$rows = $sth->fetchAll();
		$sth->close();
		return $rows;
	}
	catch (PDOException $e) {
		die('Database query failed: ' $e->getMessage());
	}
}

function db_insert($query, $params = null) {
    $dbh = db_get_connection();
	$sth = null;
	try {
		$sth = $dbh->prepare($query);
		if (is_null($params)) {
			$sth->execute();
		}
		else {
			$sth->execute($params);
		}
		$insertId = $dbh->lastInsertId;
		$sth->close();
		return $insertId;
	}
	catch (PDOException $e) {
		die('Database query failed: ' $e->getMessage());
	}
}

function db_update($query, $params = null) {
    $dbh = db_get_connection();
	$sth = null;
	try {
		$sth = $dbh->prepare($query);
		if (is_null($params)) {
			$sth->execute();
		}
		else {
			$sth->execute($params);
		}
		$rowCount = $sth->rowCount;
		$sth->close();
		return $rowCount;
	}
	catch (PDOException $e) {
		die('Database query failed: ' $e->getMessage());
	}
}
