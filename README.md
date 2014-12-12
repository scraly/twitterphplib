TwitterPHPLib
=============

TwitterPHPLib is a Twitter API client written in PHP.


# How to use #

## 1. Import ##

```php
include '/home/.../tmhOAuth.php';
include '/home/.../TwitterLib.php';
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

//init and get twitter account informations
$ta = new TwitterApp(new tmhOAuth($config));
$ta->getUsersInfos($access_token, $access_token_secret);

echo "<b>" . $ta->userdata->screen_name . "</b> (" . $ta->userdata->screen_name . ")<br/>";

```

## 3. Examples ##


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

### Get ReTweets ###

```php
$rts = $ta->getRTs($rt_since_id);
echo "Nb de Retweets à récupérer : " . sizeof($rts) . "<br/><br/>";

  foreach ($rts as $retweet){
           $retweet_id = $retweet->id_str;
           $retweet_text = addslashes($retweet->text);
           $retweet_created_at2 = date("Y-n-j H:m:s", strtotime($retweet->created_at));
           $retweet_count = $retweet->retweet_count;
	   ...
  }
```

### Get Mentions ###

```php
$mentions = $ta->getMentions($mention_since_id);
echo "Nb de Mentions à récupérer : " . sizeof($mentions) . "<br/><br/>";

  foreach ($mentions as $m){
           $mention_id = $m->id_str;
           $mention_text = addslashes($m->text);
           $mention_created_at2 = date("Y-n-j H:m:s", strtotime($m->created_at));
           $source_user_id = $m->user->id_str;
           $source_user_screen_name = $m->user->screen_name;
           $source_user_name = addslashes($m->user->name);
           $source_user_profile_image_url = $m->user->profile_image_url;
           $source_user_location = addslashes($m->user->location);
           $source_user_description = addslashes($m->user->description);
           $source_user_created_at = $m->user->created_at;
           $source_user_created_at2 = date("Y-n-j H:m:s", strtotime($m->user->created_at));
           $source_user_url = $m->user->url;
           $source_user_followers_count = $m->user->followers_count;
           $source_user_friends_count = $m->user->friends_count;
	   ...
  }
```


### Send a tweet ###

```php
$sent = $ta->sendTweet($msg);
```
