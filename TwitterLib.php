<?php

/**
 * TwitterLib
 *
 * The library use OAuth 1.0A library named tmhOAuth (https://github.com/themattharris/tmhOAuth)
 * REST requests. OAuth authentication is sent using the an Authorization Header.
 *
 * @author aurelievache
 * @version 0.4
 *
 * @created November 2011
 * @updated December 2014
 */

class TwitterLib {

    /**
     * This variable holds the tmhOAuth object used throughout the class
     *
     * @var tmhOAuth An object of the tmhOAuth class
     */
    public $tmhOAuth;


    /**
     * User's Twitter account data
     *
     * @var array Information on the current authenticated user
     */
    public $userdata;


    /**
     * This variable holds the error
     *
     * @var error An object 
     */
    public $error;


    /**
     * Authentication state
     *
     * Values:
     *  - 0: not authed
     *  - 1: Request token obtained
     *  - 2: Access token obtained (authed)
     *
     * @var int The current state of authentication
     */
    protected $state;


    /**
     * Initialize a new TwitterLib object
     *
     * @param tmhOAuth $tmhOAuth A tmhOAuth object with consumer key and secret
     */
    public function  __construct(tmhOAuth $tmhOAuth) {

        // save the tmhOAuth object
        $this->tmhOAuth = $tmhOAuth;

        // start a session if one does not exist
        if(!session_id()) {
            session_start();
        }
        
        // determine the authentication status
        // default to 0
        $this->state = 0;

        // 2 (authenticated) if the cookies are set
        if(isset($_COOKIE['access_token'], $_COOKIE['access_token_secret'])) {
            $this->state = 2;
        }

        // otherwise use value stored in session
        elseif(isset($_SESSION['authstate'])) {
            $this->state = (int)$_SESSION['authstate'];
        }

        // if we are in the process of authentication we continue
        if($this->state == 1) {
            $this->auth();
        }

        // verify authentication, clearing cookies if it fails
        elseif($this->state == 2 && !$this->auth()) {
            $this->endSession();
        }

    }


    /**
     * Authenticate user with Twitter
     *
     * @return bool Authentication successful
     */
    public function auth() {
        
        // state 1 requires a GET variable to exist
        if($this->state == 1 && !isset($_GET['oauth_verifier'])) {
            $this->state = 0;
        }

        // Step 1: Get a request token
        if($this->state == 0) {
            return $this->getRequestToken();
        }

        // Step 2: Get an access token
        elseif($this->state == 1) {
            return $this->getAccessToken();
        }

        // Step 3: Verify the access token
        return $this->verifyAccessToken();
    }



    /**
     * Obtain a request token from Twitter
     *
     * @return bool False if request failed
     */
    private function getRequestToken() {
        
        // send request for a request token
        $this->tmhOAuth->request('POST', $this->tmhOAuth->url('oauth/request_token', ''), array(

            // pass a variable to set the callback
            'oauth_callback'    => $this->tmhOAuth->php_self()
        ));


        if($this->tmhOAuth->response['code'] == 200) {

            // get and store the request token
            $response = $this->tmhOAuth->extract_params($this->tmhOAuth->response['response']);
	    error_log($response);
            $_SESSION['authtoken'] = $response['oauth_token'];
            $_SESSION['authsecret'] = $response['oauth_token_secret'];

            // state is now 1
            $_SESSION['authstate'] = 1;

            // redirect the user to Twitter to authorize
            $url = $this->tmhOAuth->url('oauth/authorize', '') . '?oauth_token=' . $response['oauth_token'];
            header('Location: ' . $url);
            exit;
        }

        return false;
    }



    /**
     * Obtain an access token from Twitter
     *
     * @return bool False if request failed
     */
    private function getAccessToken() {

        // set the request token and secret we have stored
        $this->tmhOAuth->config['user_token'] = $_SESSION['authtoken'];
        $this->tmhOAuth->config['user_secret'] = $_SESSION['authsecret'];


        // send request for an access token
        $this->tmhOAuth->request('POST', $this->tmhOAuth->url('oauth/access_token', ''), array(

            // pass the oauth_verifier received from Twitter
            'oauth_verifier'    => $_GET['oauth_verifier']
        ));



        if($this->tmhOAuth->response['code'] == 200) {

            // get the access token and store it in a cookie
            $response = $this->tmhOAuth->extract_params($this->tmhOAuth->response['response']);
            setcookie('access_token', $response['oauth_token'], time()+3600*24*30);
            setcookie('access_token_secret', $response['oauth_token_secret'], time()+3600*24*30);

            // state is now 2
            $_SESSION['authstate'] = 2;

            // redirect user to clear leftover GET variables
            header('Location: ' . $this->tmhOAuth->php_self());

            exit;
        }

        return false;
    }

    /**
     * Get tweets
     * 
     * @param string $since_id Last tweet_id stored in our database
     *
     * @see https://dev.twitter.com/rest/reference/get/statuses/user_timeline
     *
     * Returns a collection of the most recent Tweets posted by the user indicated by the screen_name or user_id parameters.
     *
     * User timelines belonging to protected users may only be requested when the authenticated user either “owns” the timeline
     * or is an approved follower of the owner.
     *
     * This method can only return up to 3,200 of a user’s most recent Tweets. Native retweets of other statuses by the user is included
     * in this total, regardless of whether include_rts is set to false when requesting this resource.
     */
    public function getTweets($since_id) {
	if(isset($since_id)) {
           $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/user_timeline'), array(
            'count' => 200,
            'include_entities' => 'true',//default
            'include_rts' => 'true',
            'since_id' => $since_id
        ));
	} else {
        	$this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/user_timeline'), array(
            	'count' => 200,
                'include_rts' => 'true',
            	'include_entities' => 'true'//default
       	 	));
	}

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }

    /**
     * @deprecated
     *
     * Get RTs that account done - to be added to our Tweets datas
     *
     * @param string $since_id Last retweet_id stored in our database
     *
     * @see https://dev.twitter.com/docs/api/1/get/statuses/retweets_by_me
     */
    public function getRTsByMe($since_id) {
        if(isset($since_id)) {
           $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/retweeted_by_me'), array(
            'count' => 100,
            'since_id' => $since_id
        ));
        } else {
                $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/retweeted_by_me'), array(
                'count' => 100,
                ));
        }

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }


    /**
     * Get RTs
     *
     * @param string $since_id Last retweet_id stored in our database
     *
     * @see https://dev.twitter.com/rest/reference/get/statuses/retweets_of_me
     *
     * Returns the most recent tweets authored by the authenticating user that have been retweeted by others. 
     * This timeline is a subset of the user’s GET statuses / user_timeline.
     */
    public function getRTs($since_id) {
        if(isset($since_id)) {
           $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/retweets_of_me'), array(
            'count' => 100,
            'since_id' => $since_id
        ));
        } else {
                $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/retweets_of_me'), array(
                'count' => 100,
                ));
        }

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }

    /**
     * Who have done the RT
     *
     * @param string $tweet_id
     *
     * @return users_id
     *
     * @see https://dev.twitter.com/rest/reference/get/statuses/retweeters/ids
     *
     * Returns a collection of up to 100 user IDs belonging to users who have retweeted the tweet specified by the id parameter.
     */
    public function getWhoDoneTheRT($tweet_id) {
       	   $url = '1.1/statuses/retweeters/ids';

           $this->tmhOAuth->request('GET', $this->tmhOAuth->url($url), array(
            'count' => 10,
	    'id' => $tweet_id
        ));
        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }

    /**
     * Users infos
     *
     * @param string $user_id
     *
     * @return users
     *
     * @see https://dev.twitter.com/rest/reference/get/users/show
     *
     * Returns a variety of information about the user specified by the required user_id or screen_name parameter. 
     * The author’s most recent Tweet will be returned inline when possible.
     */
    public function getUserInfos($user_id) {
           $url = '1.1/users/show';

           $this->tmhOAuth->request('GET', $this->tmhOAuth->url($url), array(
            'count' => 10,
            'user_id' => $user_id
        ));
        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }


    /**
     * Get Mentions
     *
     * @param string $since_id Last mention_id stored in our database
     * 
     * @see https://dev.twitter.com/rest/reference/get/statuses/mentions_timeline
     *
     * Returns the 20 most recent mentions (tweets containing a users’s @screen_name) for the authenticating user.
     * The timeline returned is the equivalent of the one seen when you view your mentions on twitter.com.
     * This method can only return up to 800 tweets.
     */
    public function getMentions($since_id) {
        if(isset($since_id)) {
           $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/mentions_timeline'), array(
            'count' => 200,
            'since_id' => $since_id
        ));
        } else {
                $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/statuses/mentions_timeline'), array(
                'count' => 200,
                ));
        }

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }

    /**
     * Get followers
     * return a list of ids
     * 
     * @see https://dev.twitter.com/rest/reference/get/followers/ids
     *
     * Returns a cursored collection of user IDs for every user following the specified user.
     * At this time, results are ordered with the most recent following first — however, this ordering is subject to unannounced change and
     * eventual consistency issues. Results are given in groups of 5,000 user IDs and multiple “pages” of results can be navigated through
     * using the next_cursor value in subsequent requests. See Using cursors to navigate collections for more information.
     */
    public function getFollowers() {
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'));

        $res = json_decode($this->tmhOAuth->response['response']);
	if(isset($res->next_cursor)) {
	  echo "next_cursor= " . $res->next_cursor . "<br/>";
	  $result = $res->ids;
	}

	if(isset($res->next_cursor) && $res->next_cursor != null && $res->next_cursor != "0") {

  	  while($res->next_cursor != 0) {
	    $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'), array(
                'cursor' => $res->next_cursor
            ));
            $res = json_decode($this->tmhOAuth->response['response']);
            echo "next_cursor= " . $res->next_cursor . "<br/>";

            $result = array_merge($result, $res->ids);
	  }
  	  return $result;
	} else {
	  return $res->ids;
	}


/*	if(isset($res->next_cursor) && $res->next_cursor != null && $res->next_cursor != "0") {
	    $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'), array(
		'cursor' => $res->next_cursor
	    ));
	    $res = json_decode($this->tmhOAuth->response['response']);
            echo "next_cursor= " . $res->next_cursor . "<br/>";

	    return array_merge($result1, $res->ids);
	} else {
	  return $res->ids;
	}*/
    }

/*    public function getFollowers() {
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'));

        $res = json_decode($this->tmhOAuth->response['response']);
	echo "next_cursor= " . $res->next_cursor . "<br/>";

        return $res->ids;
    }*/

    /**
     * Get followers
     * return a list of ids
     * 
     * @see https://dev.twitter.com/rest/reference/get/followers/ids
     *
     * Returns a cursored collection of user IDs for every user following the specified user.
     * At this time, results are ordered with the most recent following first — however, this ordering is subject to unannounced change and
     * eventual consistency issues. Results are given in groups of 5,000 user IDs and multiple “pages” of results can be navigated through
     * using the next_cursor value in subsequent requests. See Using cursors to navigate collections for more information.
     */
    public function getFollowersForAccount($screen_name) {
	$this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'), array(
            'screen_name' => $screen_name
        ));
//        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/followers/ids'));

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res->ids;
    }


    /**
     * Get Followers details
     *
     * @param string $user_ids A comma separated list of user IDs, up to 100 are allowed in a single request
     *
     * @see https://dev.twitter.com/rest/reference/get/users/lookup
     *
     * Returns fully-hydrated user objects for up to 100 users per request, as specified by comma-separated values passed to the user_id
     * and/or screen_name parameters.
     * This method is especially useful when used in conjunction with collections of user IDs returned from GET friends / ids and GET 
     * followers / ids.
     */
    public function getFollowersDetails($user_ids) {
           $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/users/lookup'), array(
            'user_id' => $user_ids
        ));

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }



    /**
     * Identify and get user's informations according to tokens passed in parameter
     * 
     * @return bool Access token verified
     *
     * @see https://dev.twitter.com/rest/reference/get/account/verify_credentials
     *
     * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; returns a 401
     * status code and an error message if not. Use this method to test if supplied user credentials are valid.
     */
    public function getUsersInfos($access_token, $access_token_secret) {
        $this->tmhOAuth->config['user_token'] = $access_token;
        $this->tmhOAuth->config['user_secret'] = $access_token_secret;

        // send verification request to test access key
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/account/verify_credentials'));

        // store the user data returned from the API
        $this->userdata = json_decode($this->tmhOAuth->response['response']);

        // HTTP 200 means we were successful
        return ($this->tmhOAuth->response['code'] == 200);
    }


    /**
     * @deprecated ?
     * Verify the validity of our access token
     *
     * @return bool Access token verified
     *
     * @see https://dev.twitter.com/rest/reference/get/account/verify_credentials
     *
     * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; returns a 401
     * status code and an error message if not. Use this method to test if supplied user credentials are valid.
     */
    private function verifyAccessToken() {
        $this->tmhOAuth->config['user_token'] = $_COOKIE['access_token'];
        $this->tmhOAuth->config['user_secret'] = $_COOKIE['access_token_secret'];

        // send verification request to test access key
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/account/verify_credentials'));

        // store the user data returned from the API
        $this->userdata = json_decode($this->tmhOAuth->response['response']);

        // HTTP 200 means we were successful
        return ($this->tmhOAuth->response['code'] == 200);
    }


    /**
     * Check the current state of authentication
     *
     * @return bool True if state is 2 (authenticated)
     */
    public function isAuthed() {
        return $this->state == 2;
    }


    /**
     * Remove user's access token cookies
     */
    public function endSession() {
        $this->state = 0;
        $_SESSION['authstate'] = 0;
        setcookie('access_token', '', 0);
        setcookie('access_token_secret', '', 0);
    }

    

    /**
     * Send a tweet on the user's behalf
     *
     * @param string $text Text to tweet
     * @return bool Tweet successfully sent
     *
     * @see https://dev.twitter.com/rest/reference/post/statuses/update
     *
     * Updates the authenticating user’s current status, also known as tweeting.
     * For each update attempt, the update text is compared with the authenticating user’s recent tweets.
     * Any attempt that would result in duplication will be blocked, resulting in a 403 error. Therefore, a user cannot submit the same 
     * status twice in a row.
     *
     * While not rate limited by the API a user is limited in the number of tweets they can create at a time. If the number of updates
     * posted by the user reaches the current allowed limit this method will return an HTTP 403 error.
     */
    public function sendTweet($text) {

        // limit the string to 140 characters
        if(strlen($text) <= 140)
        {
          // POST the text to the statuses/update method
          $this->tmhOAuth->request('POST', $this->tmhOAuth->url('1.1/statuses/update'), array(
              'status' => $text
          ));

          return ($this->tmhOAuth->response['code'] == 200);
        }

        else
        {
          return false;
        }
    }

    

    /**
     * @deprecated ?
     *
     * Search status with query
     *
     * @param string $query Text to search
     * @return array of tweet result
     */
    public function search($query) {

        $this->tmhOAuth->config['host'] = 'search.twitter.com';

        // POST the text to the statuses/update method
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('search'), array(
            'q' => $query,
	    'lang' => 'fr',
	    'rpp' => 100,
	    'result_type' => 'recent'
        ));

        $this->tmhOAuth->config['host'] = 'api.twitter.com';
        
        $res = json_decode($this->tmhOAuth->response['response']);

        return $res->results;
    }
    

    /**
     * Create friendship (follow)
     *
     * @param string $screen_name User id to follow
     * @return bool friendship successfully create
     *
     * @see https://dev.twitter.com/rest/reference/post/friendships/create
     *
     * Allows the authenticating users to follow the user specified in the ID parameter.
     */
    public function follow($screen_name) {

        // POST the text to the statuses/update method
        $this->tmhOAuth->request('POST', $this->tmhOAuth->url('1.1/friendships/create'), array(
            'screen_name' => $screen_name,
            'follow' => true
        ));


        if($this->tmhOAuth->response['code'] == 200)
          return true;
        else
        {
          $this->error = $this->tmhOAuth->response;
          return false;
        }
          
    }

    

    /**
     * Verif exist friendship
     *
     * @param int $source_id User id of the subject user
     * @param int $target_id User id of the user to test for following
     * @return bool friendship successfully create
     *
     * @see https://dev.twitter.com/rest/reference/get/friendships/show
     *
     * Returns detailed information about the relationship between two arbitrary users.
     */
    public function isFollowMe($source_id, $target_id = '239377653') {

        // POST the text to the statuses/update method
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/friendships/show'), array(
            'source_id' => $source_id,
            'target_id' => $target_id
        ));

        $res = json_decode($this->tmhOAuth->response['response']);

        return $res->relationship->target->followed_by;
    }

    

    /**
     * List friends
     *
     * @param int $user_id User id of the subject user
     * @return array friends list of user
     *
     * @see https://dev.twitter.com/rest/reference/get/friends/ids
     * 
     * Returns a cursored collection of user IDs for every user the specified user is following (otherwise known as their “friends”).
     *
     * At this time, results are ordered with the most recent following first — however, this ordering is subject to unannounced change and
     * eventual consistency issues. Results are given in groups of 5,000 user IDs and multiple “pages” of results can be navigated through
     * using the next_cursor value in subsequent requests. See Using cursors to navigate collections for more information.
     */
    public function friendsList($user_id = '239377653') {

        // POST the text to the statuses/update method
        $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/friends/ids'), array(
            'user_id' => $user_id
        ));
        
        $res = json_decode($this->tmhOAuth->response['response']);

        return $res;
    }

    

    /**
     * Unfollow user
     *
     * @param int $user_id User id of the subject user
     * @return array friends list of user
     * 
     * @see https://dev.twitter.com/rest/reference/post/friendships/destroy
     *
     * Allows the authenticating user to unfollow the user specified in the ID parameter.
     * Returns the unfollowed user in the requested format when successful. Returns a string describing the failure condition when 
     * unsuccessful.
     *
     * Actions taken in this method are asynchronous and changes will be eventually consistent.
     */
    public function unfollow($user_id) {

        // POST the text to the statuses/update method
        $this->tmhOAuth->request('POST', $this->tmhOAuth->url('1.1/friendships/destroy'), array(
            'user_id' => $user_id
        ));

        return ($this->tmhOAuth->response['code'] == 200);
    }

    

    /**
     * Valid Tweet
     *
     * @param string $tweet Twitt to validate
     * @return bool Tweet valid
     * 
     */
    public function validTweet($tweet) {

        $tabNotValid = array(
                            'RT',
                            '@'
                            );
        $valid = true;

        foreach($tabNotValid as $mot)
        {
          $pos = strpos($tweet, $mot);

          if (!($pos === false)) {
            $valid = false;
            break;
          }
        }

        return $valid;
    }

}

