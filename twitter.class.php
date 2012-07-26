<?php
namespace Wired{

  class Twitter {

    var $options;

    function __construct( $args = null ) {
      $defaults = array(
    		'screen_name' => '',
    		'count' => '3',
    		'exclude_replies' => true,
    		'expiration' => 60 * 60 * 3, // 3 hours
    		'error_msg' => 'Follow us on twitter',
    		'tweet_template' => '<li>{tweet} {time_since}</li>',
    		'tweet_wrapper' => 'ul',
        'return' => false
    	);
    	$this->options = wp_parse_args( $args, $defaults );

    	if( empty($this->options['screen_name']) ) // exit if no screen_name
    	  return;

  	  if ( $this->options['count'] > 200 ) // Twitter paginates at 200 max tweets. update() should not have accepted greater than 200
  			$this->options['count'] = 200;

    }// END: __construct()

    function get_the_tweets(){
      // try to get from transient store first
      if( $tweets = get_transient( 'wired-twitter-'. $this->options['screen_name'] .'-'. $this->options['count'] ) )
        return json_decode($tweets, true);

      //if none stored get from twitter

      // stores params for sending to twitter
      $params = array(
  			'screen_name'=> $this->options['screen_name'], // Twitter account name
  			'trim_user'=>true, // only basic user data (slims the result)
  		);


  		/**
  		 * The exclude_replies parameter filters out replies on the server. If combined with count it only filters that number of tweets (not all tweets up to the requested count)
  		 * If we are not filtering out replies then we should specify our requested tweet count
  		 */
  		if ( $this->options['exclude_replies'] ){
  			$params['exclude_replies'] = true;
  		}else{
  			$params['count'] = $this->options['count'];
  		}

  		$twitter_json_url = esc_url_raw( 'http://api.twitter.com/1/statuses/user_timeline.json?' . http_build_query($params), array('http', 'https') );
  		unset($params);
  		$response = wp_remote_get( $twitter_json_url, array( 'User-Agent' => 'Wiredmedia Twitter Widget' ) );
  		$response_code = wp_remote_retrieve_response_code( $response );
  		if ( 200 == $response_code ) {
  			$tweets = wp_remote_retrieve_body( $response );
  			$tweets = json_decode( $tweets, true );
  			$expire = 900;
  			if ( !is_array( $tweets ) || isset( $tweets['error'] ) ) {
  				// error getting tweets, get them from the fallback store and set the expiration to a low number
  				$tweets = $this->get_fallback();
  				$this->store_transient( $tweets, 500 );
  			}else{
  			  // successfully got new tweets
  			  $tweets = array_slice($tweets, 0, $this->options['count']); // this needs to be done incase the user is excluding replies because the count param does not get set
  			  $this->store_transient( $tweets, $this->options['expiration'] );
  			  $this->store_fallback( $tweets );
  			}
  		} else {
  			// error getting tweets, get them from the fallback store and set the expiration to a low number
  			$tweets = $this->get_fallback();
  			$this->store_transient( $tweets, 500 );
  		}

      return $tweets;

    }// END: get_the_tweets()

    function the_tweets(){
      $tweets = $this->get_the_tweets();
      $output = '';
      $output .= ($this->options['tweet_wrapper']) ? '<'. $this->options['tweet_wrapper'] .' class="tweets">' : '';

  		foreach ( (array) $tweets as $tweet ):

  			if ( empty( $tweet['text'] ) )
  				continue;

  			$processed_tweet = (strstr($this->options['tweet_template'], '{tweet}')) ? $this->process_tweet( $tweet ) : '';
  			$time_since = (strstr($this->options['tweet_template'], '{time_since}')) ? $this->time_since( $tweet ) : '';
  			$tweet_template = str_replace('{tweet}', $processed_tweet, $this->options['tweet_template']);
  			$tweet_template = str_replace('{tweet_raw}', $tweet['text'], $tweet_template);
  			$tweet_template = str_replace('{time_since}', $time_since, $tweet_template);
        $tweet_template = str_replace('{tweet_link}', 'https://twitter.com/'. $this->options['screen_name'] .'/status/'. $tweet['id'], $tweet_template);

  			$output .= $tweet_template;

  		endforeach;

  		$output .= ($this->options['tweet_wrapper']) ? '</'. $this->options['tweet_wrapper'] .'>' : '';

      if($this->options['return']){
        return $output;
      }else{
  		  echo $output;
      }

    }// END: the_tweets();

    function process_tweet( $tweet ){ // pass in plain tweet, get out nice tweet
      /*
  		 * Create links from plain text based on Twitter patterns
  		 * @link http://github.com/mzsanford/twitter-text-rb/blob/master/lib/regex.rb Official Twitter regex
  		 */
      $text = make_clickable( esc_html( $tweet['text'] ) );
      $text = preg_replace_callback('/(^|[^0-9A-Z&\/]+)(#|\xef\xbc\x83)([0-9A-Z_]*[A-Z_]+[a-z0-9_\xc0-\xd6\xd8-\xf6\xf8\xff]*)/iu',  array($this, 'twitter_hashtag'), $text);
  		$text = preg_replace_callback('/([^a-zA-Z0-9_]|^)([@\xef\xbc\xa0]+)([a-zA-Z0-9_]{1,20})(\/[a-zA-Z][a-zA-Z0-9\x80-\xff-]{0,79})?/u', array($this, 'twitter_username'), $text);

      return $text;
    }

    function time_since( $tweet ){

      if ( isset($tweet['id_str']) ){
  			$tweet_id = urlencode($tweet['id_str']);
  		}else{
  			$tweet_id = urlencode($tweet['id']);
  		}
  		$time_since = new Time_Since( strtotime($tweet['created_at']) );
  		$time_since_output = '<a href="'. esc_url( 'http://twitter.com/'. $this->options['screen_name'] .'/statuses/'. $tweet_id ) . '" class="timesince">' . str_replace(' ', '&nbsp;', $time_since->output_time() ) . '&nbsp;ago</a>';
  		return $time_since_output;
    }

    function store_transient( $tweets, $expire ){
      set_transient( 'wired-twitter-'. $this->options['screen_name'] .'-'. $this->options['count'], json_encode($tweets), $expire );
    }

    private function store_fallback( $tweets ){
      /*
       * store in option so tweet data does not expire, we need this incase an error occures when getting new tweets
       * if the error occurs then we shall get the option store instead of the transient
       */
      delete_option('wired-twitter-'. $this->options['screen_name'] .'-'. $this->options['count']);
      add_option( 'wired-twitter-'. $this->options['screen_name'] .'-'. $this->options['count'], json_encode($tweets), '', 'no' );
    }

    private function get_fallback(){
      return json_decode(get_option( 'wired-twitter-'. $this->options['screen_name'] .'-'. $this->options['count'], true), $this->options['error_msg'] );
    }

  	/**
  	 * Link a Twitter user mentioned in the tweet text to the user's page on Twitter.
  	 *
  	 * @param array $matches regex match
  	 * @return string Tweet text with inserted @user link
  	 */
  	function twitter_username( $matches ) { // $matches has already been through wp_specialchars
  		return "$matches[1]@<a href='" . esc_url( 'http://twitter.com/' . urlencode( $matches[3] ) ) . "'>$matches[3]</a>";
  	}

  	/**
  	 * Link a Twitter hashtag with a search results page on Twitter.com
  	 *
  	 * @param array $matches regex match
  	 * @return string Tweet text with inserted #hashtag link
  	 */
  	function twitter_hashtag( $matches ) { // $matches has already been through wp_specialchars
  		return "$matches[1]<a href='" . esc_url( 'http://twitter.com/search?q=%23' . urlencode( $matches[3] ) ) . "'>#$matches[3]</a>";
  	}

    /*
     * clears cache by deleting transients only,
     * does not delete fallback option
     */
    public function refresh_cache(){
      global $wpdb;
      $options = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE '%wired-twitter%' AND option_name LIKE '%transient%'");
      foreach ( $options as $option) {
        delete_option( $option->option_name );
      }
    }

  }// END:Wired\Twitter
}

// api
namespace {
  function get_the_tweets( $args ){
    $twitter = new Wired\Twitter( $args );
    return $twitter->get_the_tweets();
  }

  function the_tweets( $args ){
    $twitter = new Wired\Twitter( $args );
    return $twitter->the_tweets();
  }
}