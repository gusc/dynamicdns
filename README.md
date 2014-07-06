# Requirements

1. BIND DNS server daemon
2. Some HTTP web server
3. PHP

# Server side installation

## Create DNSSec key

Log into your server as a root and generate a new key for your DNS updater:

```
dnssec-keygen -a hmac-sha512 -b 512 -n HOST dyndns.example.com.
```

This will create two files starting with letter K, then your domain name, then some gibberish and ending with .key and .private.
Get the key value from .key file:

```
cat Kdyndns.example.com*.key
```

this will output something like this:

```
dyndns.example.com. IN KEY 512 3 165 bwfb5O8+1VUiV3un66ucV1c9yAyT5Hdzs1gUHpcWWZrFEihtLlKl1E4E wFsTNbWg+EvK/ddOq7wWmZ9GYaPYbw==
```

Copy the Base64 part from the key file (the one that looks like: **bwfb5O8+1VUiV3un66ucV1c9yAyT5Hdzs1gUHpcWWZrFEihtLlKl1E4E wFsTNbWg+EvK/ddOq7wWmZ9GYaPYbw==**)
The space in the key is OK, don't worry.

## Create zone configuration

Open up named.conf in your favourite text editor and add your zone configurations:

```
key "dyndns.example.com." {
    algorithm hmac-sha512;
    secret "bwfb5O8+1VUiV3un66ucV1c9yAyT5Hdzs1gUHpcWWZrFEihtLlKl1E4E wFsTNbWg+EvK/ddOq7wWmZ9GYaPYbw==";
};
zone "dyndns.example.com" {
    type master;
    file "/path/to/your/zones/dyndns.example.com";
    allow-update { key "dyndns.example.com."; };
    notify yes;
};
```

## Create zone file

Now create a zone file and update accordingly (add your NS and primary A records, update SOA record):

```
$ORIGIN .
$TTL 7200               ; 2 hours
dyndns.example.com      IN SOA  ns.example.com. hostmaster.example.com. (
                                2014010101 ; serial
                                3600       ; refresh (1 hour)
                                900        ; retry (15 minutes)
                                604800     ; expire (1 week)
                                3600       ; minimum (1 hour)
                        )
                        NS      ns.example.com.
                        NS      ns2.example.com.
                        A       123.456.789.012
$ORIGIN dyndns.example.com.
```

Reload your DNS server with:

```
rndc reload
```

and probably check your log files if everything went on smoothly.

## Create a webservice

Create a virtual host in your favourite HTTP server and install all the server side scripts into your desired virtual host.
Update your configuration files accordingly:

	* copy the config.inc.php.sample to confing.inc.php and edit it's contents
	* copy the clients.inc.php.sample to clients.inc.php and add your clients
	
Test your updates through provided update form.

# Client side installation

Place your files somewhere on your client server/computer.
Update your configuration files accordingly:

	* copy the config.inc.php.sample to config.inc.php

Test your updates by launching the client:

```
php updater.php
```

Copy the updater.cron.sample to updater.cron, edit it accordingly and add your updater script to crontab:

```
crontab updater.cron
```

Voila!