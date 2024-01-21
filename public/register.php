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

session_start();

if (isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["confirm"])) {
	if (strcmp($_POST["password"], $_POST["confirm"]) !== 0) {
		redirect("register.html?err=" . urlencode("Passwords do not match."));
	}
	
	if (strlen($_POST["password"]) < 4) {
		redirect("register.html?err=" . urlencode("Password too short."));
	}
	
	if (strlen($_POST["password"]) > 64) {
		redirect("register.html?err=" . urlencode("Password too long."));
	}
	
	if (strlen($_POST["username"]) < 1) {
		redirect("register.html?err=" . urlencode("Username too short."));
	}
	
	if (strlen($_POST["username"]) > 32) {
		redirect("register.html?err=" . urlencode("Username too long."));
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
		redirect("register.html?err=" . urlencode("Account system currently unavailable."));
	}
	
	if ($conn->connect_error) {
		//exit("Failed to connect to MySQL: " . mysqli_connect_error());
		redirect("register.html?err=" . urlencode("Account system currently unavailable."));
	}
	
	// Check if login database exists
	$sql = "SELECT COUNT(*) AS `exists` FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMATA.SCHEMA_NAME='" . $DATABASE_NAME . "'";
	$query = $conn->query($sql);
	$row = $query->fetch_object();
	$dbExists = (bool) $row->exists;
	$query->close();
	
	if (!$dbExists) {
		$sql = "CREATE DATABASE `" . $DATABASE_NAME . "`";
		if ($conn->query($sql) === TRUE) {
			echo "Database created successfully";
		} else {
			echo "Error creating database: " . $conn->error;
		}
	}
	
	$conn->select_db($DATABASE_NAME);
	
	$sql =
"CREATE TABLE IF NOT EXISTS `" . $DATABASE_TABLE . "` (
	`id` bigint NOT NULL AUTO_INCREMENT,
	`username` varchar(64) NOT NULL,
	`password` varchar(128) NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;";
	if ($conn->query($sql) !== TRUE) {
		redirect("register.html?err=" . urlencode("Account system currently unavailable."));
	}
	
	if ($stmt = $conn->prepare("SELECT id, password FROM `" . $DATABASE_TABLE . "` WHERE username = ?")) {
		// Bind parameters (s = string, i = int, b = blob, etc)
		$stmt->bind_param("s", $safe_username);
		$stmt->execute();
		$stmt->store_result();
		$rows = $stmt->num_rows;
		$stmt->close();
		
		if ($rows > 0) {
			redirect("register.html?err=" . urlencode("Username taken."));
		} else {
			$query = "INSERT INTO `" . $DATABASE_TABLE . "` (username, password) VALUES (?, ?)";
			if ($stmt2 = $conn->prepare($query)) {
				$stmt2->bind_param("ss", $safe_username, $safe_password);
				$stmt2->execute();
				$stmt2->close();
				redirect("login.html?username=" . urlencode($_POST["username"]) . "&msg=" . urlencode("Account created."));
			} else {
				redirect("register.html?err=" . urlencode("Account system currently unavailable."));
			}
		}
	}
	
	$conn->close();
} else {
	redirect("register.html");
}

?>
