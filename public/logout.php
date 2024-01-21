<?php

function redirect($url) {
	ob_start();
	header("Location: " . $url);
	ob_end_flush();
	die();
}

session_start();

session_destroy();

session_regenerate_id();

redirect("login.html?msg=" . urlencode("Successfully logged out."));

?>
