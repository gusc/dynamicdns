<?php
/**
* Dynamic DNS server side update script
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

// Load user/subdomain database
$clients = array();
if (is_file($PATH.'/clients.inc.php')){
  include($PATH.'/clients.inc.php');
}

// Short sanity check for given IP
function checkip($ip){
  $iptupel = explode('.', $ip);
  foreach ($iptupel as $value){
    if ($value < 0 || $value > 255){
      return false;
    }
  }
  return true;
}   

// Retrieve IPs (originating POST IP and target IP)
$post_ip = $_SERVER['REMOTE_ADDR'];
$ip = '';
if (isset($_POST['ip'])){
  $ip = $_POST['ip'];
} else {
	$ip = $post_ip;
}
// Retrieve user
$user = '';
if (isset($_POST['user'])){
  $user = $_POST['user'];
} else {
  syslog(LOG_WARNING, 'No user given by connection from '.$post_ip);
  exit(0);
}
if (strlen($user) <= 0){
  syslog(LOG_WARNING, 'No user given or empty by connection from '.$post_ip);
  exit(0);
}
// Retrieve password or hash
$pass = '';
if (isset($_POST['pass'])){
  $pass = md5($_POST['pass']);
} else if (isset($_POST['hash'])){
	$pass = $_POST['hash'];
} else {
  syslog(LOG_WARNING, 'No password given by connection from '.$post_ip);
  exit(0);
}
if (strlen($pass) <= 0){
  syslog(LOG_WARNING, 'No password given or empty by connection from '.$post_ip);
  exit(0);
}

// Validate user
if (!isset($clients[$user])){
	echo 'ERR: auth failed';
  syslog(LOG_WARNING, 'User "'.$user.'" does not exist');
  exit(0);
} else if ($clients[$user]['password'] != $pass){
	echo 'ERR: auth failed';
	syslog(LOG_WARNING, 'User "'.$user.'" provided incorrect password');
  exit(0);
}
$hosts = $clients[$user]['hosts'];		

// check for given domain
if (isset($_POST['host'])){
  $host = $_POST['host'];
} else {
  syslog(LOG_WARNING, 'User "'.$user.'" from '.$post_ip.' didn\'t provide any domain');
  exit(0);
}

// short sanity check for given IP
if (preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $ip) && checkip($ip) && $ip != '0.0.0.0' && $ip != '255.255.255.255'){
  // short sanity check for given domain
  if (preg_match('/^[\w\d-_\*\.]+$/', $host)){
    // check whether user is allowed to change domain
    if (in_array($host, $hosts)){
      // shell escape all values
      $host = escapeshellcmd($host);
      $user = escapeshellcmd($user);
      $ip = escapeshellcmd($ip);

      // prepare command
      $data = '<<EOF
zone '.$ZONE.'
update delete '.$host.'.'.$ZONE.' A
update add '.$host.'.'.$ZONE.' 300 A '.$ip.'
send
EOF';
      // run DNS update
      $cmdout = array();
      $cmd = $NSUPDATE.' -k '.$NSKEYFILE.' '.$data;
      exec($cmd, $cmdout, $ret);
      // check whether DNS update was successful
      if ($ret != 0){
      	echo 'ERR: DNS server responded with: '.$ret;
        syslog(LOG_INFO, 'Changing DNS for "'.$host.'.'.$ZONE.'" to "'.$ip.'" failed with code '.$ret);
      } else {
        echo 'OK';
        syslog(LOG_INFO, 'Changing DNS for "'.$host.'.'.$ZONE.'" to "'.$ip.'" is a success');
			}
    } else {
      echo 'ERR: domain not found';
      syslog(LOG_INFO, 'Domain "'.$host.'" is not allowed for "'.$user.'" from '.$post_ip);
    }
  } else {
  	echo 'ERR: domain malformed';
    syslog(LOG_INFO, 'Domain "'.$host.'" for "'.$user.'" from '.$post_ip.' was wrong');
  }
} else {
	echo 'ERR: IP address malformed';
  syslog(LOG_INFO, 'IP "'.$ip.'" for "'.$user.'" from "'.$post_ip.'" was wrong');
}

// Close log session
closelog();