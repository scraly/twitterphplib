twitterphplib
=============

TwitterPHPLib is a Twitter API client written in PHP.


# How to use #

## 1. Import ##

`include '/home/air/var/www/html/twitter/twitterstats/lib/tmhOAuth.php';`

`include '/home/air/var/www/html/twitter/twitterstats/lib/TwitterApp.php';`


## 2. Initialization ##

<code>
// set the consumer key and secret

define('CONSUMER_KEY',      'xxx');
define('CONSUMER_SECRET',   'xxx');

// our tmhOAuth settings

$config = array(
'consumer_key'      => CONSUMER_KEY,
'consumer_secret'   => CONSUMER_SECRET
);

//init

$ta = new TwitterApp(new tmhOAuth($config));
</code>

## 3. Examples ##

### Get Twitter acount user informations ###

`$ta->getUsersInfos($access_token, $access_token_secret);`


