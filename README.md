twitterphplib
=============

TwitterPHPLib is a Twitter API client written in PHP.


# How to use #

## 1. Import ##

```php
include '/home/air/var/www/html/twitter/twitterstats/lib/tmhOAuth.php';

include '/home/air/var/www/html/twitter/twitterstats/lib/TwitterApp.php';
```


## 2. Initialization ##

```php
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
```

## 3. Examples ##

### Get Twitter acount user informations ###

```php
$ta->getUsersInfos($access_token, $access_token_secret);

echo "<b>" . $ta->userdata->screen_name . "</b> (" . $ta->userdata->screen_name . ")<br/>";
```

### Get tweets ###

```php
$tweets = $ta->getTweets($tweet_since_id);

 foreach ($tweets as $tweet){
           $tweet_id = $tweet->id_str;
           $tweet_text = addslashes($tweet->text);
           $tweet_created_at = $tweet->created_at;
           $tweet_created_at2 = date("Y-n-j H:m:s", strtotime($tweet->created_at));
           $tweet_account_id = $tweet->user->id_str;
           $tweet_favorite_count = $tweet->favorite_count;
	   ...
 }
```


