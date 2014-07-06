<?php
/**
* DynamicDNS cron updater script
* Run it every hour or so
* 
* @author Gusts 'gusC' Kaksis <gusts.kaksis@gmail.com>
*/

// Determine path
$PATH = pathinfo(__FILE__, PATHINFO_DIRNAME);
// Open log session
openlog('DDNS-Provider', LOG_PID | LOG_PERROR, LOG_LOCAL0);

// Load configuration
if (is_file($PATH.'/config.inc.php')){
  include($PATH.'/config.inc.php');
} else {
	syslog(LOG_WARNING, 'Configuration file not found');
	exit;
}

// Get last known IP address
$last_ip = '';
if (is_file($PATH.'/lastip.txt')){
	$last_ip = file_get_contents($PATH.'/lastip.txt');
} else {
	syslog(LOG_INFO, 'Last IP address not registered');
}
// Get current IP address
$current_ip = file_get_contents($DYNDNS_URL.'ip.php');

if ($last_ip != $current_ip){
	// Preform update
	foreach ($HOSTS as $host){
		$params = array(
			'user' => $USER,
			'hash' => $HASH,
			'host' => $host,
			'ip' => $current_ip
		);
		$user_agent = "DDNS-Updater; v0.1";
	
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_POST, 1);
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	  curl_setopt($ch, CURLOPT_URL, $DYNDNS_URL.'update.php');
	  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
	
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		
		if ($http_code == 200){
			if ($response == 'OK'){
				file_put_contents($PATH.'/lastip.txt', $current_ip);
				syslog(LOG_INFO, 'DNS update was a success');
			} else {
				syslog(LOG_WARNING, 'DNS update failed with "'.$response.'"');
			}
		} else {
			syslog(LOG_WARNING, 'DNS update failed with HTTP status: '.$http_code);
		}
	}
}