<?php

/**
 * Cuisine Twitter widget
 * 
 * displays tweets.
 *
 * @author 		Chef du Web
 * @category 	Widgets
 * @package 	Cuisine
 */

class cuisine_widget_twitter extends WP_Widget { 

	function cuisine_widget_twitter() {
		/* Widget settings. */
		$widget_ops = array('description' => __('Display Your Tweets') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'cuisine_widget_twitter' );

		/* Create the widget. */
		$this->WP_Widget( 'cuisine_widget_twitter', __('Twitter'), $widget_ops, $control_ops );
	}

	function widget($args, $instance) {
	
		extract($args, EXTR_SKIP);

		echo $before_widget;

		//Our variables from the widget settings.
		$Chef_title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		$Chef_name = $instance['name'];
		$Chef_numTweets = $instance['numTweets'];
		$Chef_cacheTime = $instance['cacheTime'];

		//Setup Twitter API OAuth tokens
		$Chef_consumerKey = $instance['consumerKey'];
		$Chef_consumerSecret = $instance['consumerSecret'];
		$Chef_accessToken = $instance['accessToken'];
		$Chef_accessTokenSecret = $instance['accessTokenSecret'];

		$Chef_exclude_replies = isset( $instance['exclude_replies'] ) ? $instance['exclude_replies'] : false;


		if (!empty($Chef_title))
			echo $before_title . $Chef_title . $after_title;;

			// START WIDGET CODE HERE
			?>

			<ul class="tweets" id="twitter_update_list">
			<?php
			/*
	 		 * Uses:
			 * Twitter API call:
			 *     http://dev.twitter.com/doc/get/statuses/user_timeline
			 * WP transient API ref.
			 *		http://www.problogdesign.com/wordpress/use-the-transients-api-to-list-the-latest-commenter/
			 * Plugin Development and Script enhancement
			 *    http://www.planet-interactive.co.uk
			 */

			// Configuration.
			$numTweets 			= $Chef_numTweets; 	// Num tweets to show
			$name 				= $Chef_name;				// Twitter UserName
			$cacheTime 			= $Chef_cacheTime; 		// Time in minutes between updates.

			// Get from https://dev.twitter.com/
			// Login - Create New Application, fill in details and use required data below
			$consumerKey 		= $Chef_consumerKey;		// OAuth Key
			$consumerSecret 	= $Chef_consumerSecret;	// OAuth Secret
			$accessToken 		= $Chef_accessToken;		// OAuth Access Token
			$accessTokenSecret 	= $Chef_accessTokenSecret;// OAuth Token Secret

			$exclude_replies 	= $Chef_exclude_replies; // Leave out @replies?

			$transName = 'list-tweets'; // Name of value in database.
			$backupName = $transName . '-backup'; // Name of backup value in database.

			// Do we already have saved tweet data? If not, lets get it.
			if(false === ($tweets = get_transient($transName) ) ) :    

			// Get the tweets from Twitter.
			include 'includes/twitteroauth/twitteroauth.php';
			  
			$connection = new TwitterOAuth(
				$consumerKey,   		// Consumer key
				$consumerSecret,   	// Consumer secret
				$accessToken,   		// Access token
				$accessTokenSecret	// Access token secret
			);

			// If excluding replies, we need to fetch more than requested as the
			// total is fetched first, and then replies removed.
			$totalToFetch = ($exclude_replies) ? max(50, $numTweets * 3) : $numTweets;
			
			$fetchedTweets = $connection->get(
				'statuses/user_timeline',
				array(
					'screen_name'     => $name,
					'count'           => $totalToFetch,
					'exclude_replies' => $exclude_replies
				)
			);
			  
			// Did the fetch fail?
			if($connection->http_code != 200) :
				$tweets = get_option($backupName); // False if there has never been data saved.
			    
			else :
				// Fetch succeeded.
				// Now update the array to store just what we need.
				// (Done here instead of PHP doing this for every page load)
				$limitToDisplay = min($numTweets, count($fetchedTweets));
			
				for($i = 0; $i < $limitToDisplay; $i++) :
					$tweet = $fetchedTweets[$i];
			    
			    	// Core info.
			    	$name = $tweet->user->name;
			    	$permalink = 'http://twitter.com/'. $name .'/status/'. $tweet->id_str;

			    	/* Alternative image sizes method: http://dev.twitter.com/doc/get/users/profile_image/:screen_name */
			    	$image = $tweet->user->profile_image_url;

			    	// Message. Convert links to real links.
			    	$pattern = '/http:(\S)+/';
			    	$replace = '<a href="${0}" target="_blank" rel="nofollow">${0}</a>';
			    	$text = preg_replace($pattern, $replace, $tweet->text);

			    	// Need to get time in Unix format.
			    	$time = $tweet->created_at;
			    	$time = date_parse($time);
			    	$uTime = mktime($time['hour'], $time['minute'], $time['second'], $time['month'], $time['day'], $time['year']);

			    	// Now make the new array.
			    	$tweets[] = array(
			    		'text' => $text,
			    		'name' => $name,
			    		'permalink' => $permalink,
			    		'image' => $image,
			    		'time' => $uTime
			    		);
				endfor;

				// Save our new transient, and update the backup.
				set_transient($transName, $tweets, 60 * $cacheTime);
				update_option($backupName, $tweets);
				endif;

			endif;

			// Now display the tweets, if we can.
			if($tweets) : ?>
			    <?php foreach($tweets as $t) : ?>
			        <li><?php echo $t['text']; ?>
			            <br/><em>
			            <a href="http://www.twitter.com/<?php echo $name; ?>" target="_blank" title="Volf <?php echo $name; ?> op Twitter"><?php echo human_time_diff($t['time'], current_time('timestamp')); ?> geleden</a>
			            </em>
			        </li>
			    <?php endforeach; ?>

			<?php else : ?>
			    <li>Waiting for Twitter...</li>
			<?php endif; ?>
			</ul>
			<?php
			// END OF WIDGET CODE HERE
			echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {  
		$instance = $old_instance;
		
	    //Strip tags from title and name to remove HTML  
	    $instance['title'] 				= strip_tags( $new_instance['title'] );  
	    $instance['name'] 				= strip_tags( $new_instance['name'] );  
	    $instance['numTweets'] 			= $new_instance['numTweets'];
	    $instance['cacheTime'] 			= $new_instance['cacheTime'];
	    $instance['consumerKey'] 		= $new_instance['consumerKey'];
	    $instance['consumerSecret'] 	= $new_instance['consumerSecret'];
	    $instance['accessToken'] 		= $new_instance['accessToken'];
	    $instance['accessTokenSecret'] 	= $new_instance['accessTokenSecret'];
	    $instance['exclude_replies'] 	= $new_instance['exclude_replies'];
	
		return $instance;
	}

	function form($instance) {
			
		//Set up some default widget settings.
		$defaults = array( 
			  'title' 			=> __('Tweets', 'cuisine')
			, 'name' 			=> __('chefduweb', 'cuisine')
			, 'numTweets' 		=> 4 // How many to display
			, 'cacheTime' 		=> 5 // Time in minutes between updates
			, 'consumerKey' 		=> 'xxxxxxxxxxxx' // Consumer key
			, 'consumerSecret' 		=> 'xxxxxxxxxxxx' // Consumer secret
			, 'accessToken' 		=> 'xxxxxxxxxxxx' // Access token
			, 'accessTokenSecret'	=> 'xxxxxxxxxxxx' // Access token secret
			, 'exclude_replies'	=> true 
		);
		$instance 			= wp_parse_args( (array) $instance, $defaults );
		$title 				= $instance['title'];
		$name 				= $instance['name'];
		$numTweets 			= $instance['numTweets'];
		$cacheTime 			= $instance['cacheTime'];
		$consumerKey 		= $instance['consumerKey'];
		$consumerSecret 	= $instance['consumerSecret'];
		$accessToken 		= $instance['accessToken'];
		$accessTokenSecret 	= $instance['accessTokenSecret'];
		$exclude_replies 	= $instance['exclude_replies'];
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('name'); ?>">Twitter Name: <input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('numTweets'); ?>">Number of Tweets: <input class="widefat" id="<?php echo $this->get_field_id('numTweets'); ?>" name="<?php echo $this->get_field_name('numTweets'); ?>" type="text" value="<?php echo esc_attr($numTweets); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cacheTime'); ?>">Time in Minutes between updates: <input class="widefat" id="<?php echo $this->get_field_id('cacheTime'); ?>" name="<?php echo $this->get_field_name('cacheTime'); ?>" type="text" value="<?php echo esc_attr($cacheTime); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('consumerKey'); ?>">Consumer Key: <input class="widefat" id="<?php echo $this->get_field_id('consumerKey'); ?>" name="<?php echo $this->get_field_name('consumerKey'); ?>" type="text" value="<?php echo esc_attr($consumerKey); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('consumerSecret'); ?>">Consumer Secret: <input class="widefat" id="<?php echo $this->get_field_id('consumerSecret'); ?>" name="<?php echo $this->get_field_name('consumerSecret'); ?>" type="text" value="<?php echo esc_attr($consumerSecret); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('accessToken'); ?>">Access Token: <input class="widefat" id="<?php echo $this->get_field_id('accessToken'); ?>" name="<?php echo $this->get_field_name('accessToken'); ?>" type="text" value="<?php echo esc_attr($accessToken); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('accessTokenSecret'); ?>">Access Token Secret: <input class="widefat" id="<?php echo $this->get_field_id('accessTokenSecret'); ?>" name="<?php echo $this->get_field_name('accessTokenSecret'); ?>" type="text" value="<?php echo esc_attr($accessTokenSecret); ?>" /></label>
		</p>
		<p>  
		    <input class="checkbox" type="checkbox" <?php checked( isset( $instance['exclude_replies']), true ); ?> id="<?php echo $this->get_field_id( 'exclude_replies' ); ?>" name="<?php echo $this->get_field_name( 'exclude_replies' ); ?>" />   
		    <label for="<?php echo $this->get_field_id( 'exclude_replies' ); ?>"><?php _e('Exclude @replies', 'cuisine'); ?></label>  
		</p>
	<?php
	}
}

function cuisine_widget_twitter_init(){
	register_widget('cuisine_widget_twitter');

}

add_action('widgets_init', 'cuisine_widget_twitter_init');