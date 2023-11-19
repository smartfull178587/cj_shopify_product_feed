<?php

function logToFile($txt) {
	$log_file = "log_file.txt";
	$log_file_handle = null;
	if (file_exists($log_file)) {
		$log_file_handle = fopen($log_file, "a");
	} else {
		$log_file_handle = fopen($log_file, "w");
	}

	$date = new DateTime();
	$date = $date->format("y:m:d h:i:s");

	fwrite($log_file_handle, '['.$date.'] '.$txt.PHP_EOL);
	fclose($log_file_handle);
}

?>