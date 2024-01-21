<?php

$DEBUG = false;

if ($DEBUG) {
	ini_set("display_errors", "1");
	ini_set("display_startup_errors", "1");
	error_reporting(E_ALL);
} else {
	error_reporting(0);
	ini_set("display_errors", 0);
}

function redirect($url) {
	ob_start();
	header("Location: " . $url);
	ob_end_flush();
	die();
}

function encode($a) {
	return bin2hex($a);
}

function decode($a) {
	return hex2bin($a);
}

function verify($a, $b) {
	return (strcmp($a, $b) === 0);
}

session_start();

if (isset($_POST["username"]) && isset($_POST["password"])) {
	if (strlen($_POST["password"]) < 4) {
		redirect("login.html?err=" . urlencode("Invalid login."));
	}
	
	if (strlen($_POST["password"]) > 64) {
		redirect("login.html?err=" . urlencode("Invalid login."));
	}
	
	if (strlen($_POST["username"]) < 1) {
		redirect("login.html?err=" . urlencode("Invalid login."));
	}
	
	if (strlen($_POST["username"]) > 32) {
		redirect("login.html?err=" . urlencode("Invalid login."));
	}
	
	$safe_username = encode($_POST["username"]);
	$safe_password = encode($_POST["password"]);
	
	$DATABASE_HOST = "localhost";
	$DATABASE_USER = "root";
	$DATABASE_PASS = "";
	$DATABASE_NAME = "phplogin";
	$DATABASE_TABLE = "account";
	
	try {
		$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS);
	} catch (mysqli_sql_exception $e) {
		redirect("register.html?err=Account system currently unavailable.");
	}
	
	if ($conn->connect_errno) {
		redirect("register.html?err=Account system currently unavailable.");
	}
	
	// Check if login database exists
	$sql = "SELECT COUNT(*) AS `exists` FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMATA.SCHEMA_NAME='" . $DATABASE_NAME . "'";
	$query = $conn->query($sql);
	$row = $query->fetch_object();
	$dbExists = (bool) $row->exists;
	$query->close();
	
	// If the database doesn't exist, there can't be any possible matches.
	if (!$dbExists) {
		redirect("register.html?username=" . urlencode($_POST["username"]) . "&msg=" . urlencode("Please register first."));
	}
	
	$conn->select_db($DATABASE_NAME);
	
	try {
		$stmt = $conn->prepare("SELECT id, username, password FROM `" . $DATABASE_TABLE . "` WHERE username = ?");
	} catch (mysqli_sql_exception $e) {
		redirect("register.html?username=" . urlencode($_POST["username"]) . "&msg=" . urlencode("Please register first."));
	}
	
	if ($stmt) {
		// Bind parameters (s = string, i = int, b = blob, etc)
		$stmt->bind_param("s", $safe_username);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		
		if ($rows > 0) {
			$stmt->bind_result($id, $username, $password);
			$stmt->fetch();
			if (verify($safe_password, $password)) {
				session_regenerate_id();
				$_SESSION["loggedin"] = TRUE;
				$_SESSION["username"] = $_POST["username"];
				$_SESSION["id"] = $id;
				echo "Welcome " . $_POST["username"] . "!";
			} else {
				redirect("login.html?err=" . urlencode("Invalid login."));
			}
			
		} else {
			redirect("register.html?username=" . urlencode($_POST["username"]));
		}
		
		$stmt->close();
	}
	
	$conn->close();
} else {
	redirect("login.html");
}

?>
