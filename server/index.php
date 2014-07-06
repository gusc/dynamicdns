<?php
/**
* DynamicDNS public update form
* 
* @author Gusts 'gusC' Kaksis <gusts.kaksis@gmail.com>
*/
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Lang" content="en">
<meta name="author" content="Gusts 'gusC' Kaksis">
<meta name="description" content="Dynamic DNS updater">
<meta name="keywords" content="dyndns">
<title>Update your DynamicDNS</title>
</head>
<body>
<form action="update.php" method="post">
	<p>
		<label for="user">Username:</label>
		<input type="text" name="user" id="user" />
	</p>
	<p>
		<label for="pass">Password:</label>
		<input type="password" name="pass" id="pass" />
	</p>
	<p>
		<label for="host">Hostname:</label>
		<input type="text" name="host" id="host" />
	</p>
	<p>
		<label for="ip">IP:</label>
		<input type="text" name="ip" id="ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
	</p>
	<input type="submit" name="send" value="Update" />
</form>
</body>
</html>
