<?php
/*------------------------------------------------------------------------------
Make sure we're cleared to launch.  The tests here are meant to run on each
page request.  Ideally, the results could be cached so we don't have to hit
the database and check other plugins repeatedly.

------------------------------------------------------------------------------*/
class CCTMtests
{	
	//------------------------------------------------------------------------------
	/**
	 * @param	array	list of names of incompatible plugins
	 * @return	null	CCTM::$warnings gets populated if errors are detected.
	 */
	public static function incompatible_plugins($incompatible_plugins) {
		require_once(ABSPATH.'/wp-admin/includes/admin.php');
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');	

		$plugin_list = array();
		
		foreach ( $incompatible_plugins as $bad_plugin ) {
			foreach ($active_plugins as $plugin) {
				if ( $all_plugins[$plugin]['Name'] == $bad_plugin ) {
					$plugin_list[] = $bad_plugin;
				}
			}
		}
		
		if (!empty($plugin_list)) {
			$exit_msg = sprintf( __( '%1$s has detected that there are some active plugins that may be incompatible with it. We recommend disabling the following plugins:', CCTM_TXTDOMAIN)
				, '<strong>'.CCTM::name.'</strong>' );
			$exit_msg .= '<ul>';
			foreach ($plugin_list as $p ) {
				$exit_msg .= '<li><strong>'. $p . '</strong></li>';
			}
			$exit_msg .= '</ul>';
			$exit_msg .= sprintf( __('Continued use of these plugins may cause erratic behavior and certain functionality on your site may break entirely. See the <a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/IncompatiblePlugins">wiki</a> for more information.', CCTM_TXTDOMAIN) );
		}
		
		if ( !empty($exit_msg) ) {
			CCTM::$warnings[] = $exit_msg;
		}
	}
	
	//------------------------------------------------------------------------------
	 /**
	  * @param	string minimum req'd version of MySQL, e.g. 5.0.41
	  * @return none, but the $errors array is populated
	  */ 	
	public static function mysql_version_gt($ver) {
		global $wpdb;
		
		$result = $wpdb->get_results( 'SELECT VERSION() as ver' );

		if ( version_compare( $result[0]->ver, $ver, '<') ) 
		{	
			$exit_msg = sprintf( __( '%1$s requires MySQL %2$s or newer.', CCTM_TXTDOMAIN)
			, CCTM::name, $ver );
			$exit_msg .= ' ';
			$exit_msg .= __('Talk to your system administrator about upgrading.', CCTM_TXTDOMAIN);	

			CCTM::$errors[] = $exit_msg;
		}
	}
	
	//------------------------------------------------------------------------------
	public static function wp_version_gt($ver) {
		global $wp_version;
		
		if (version_compare($wp_version,$ver,'<')) {
			CCTM::$errors[] = sprintf( __('%1$s requires WordPress %2$s or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>', CCTM_TXTDOMAIN)
			, CCTM::name
			, $ver );
		}

	}

	//------------------------------------------------------------------------------
	public static function php_version_gt($ver) {
		
		if ( version_compare( phpversion(), $ver, '<') ) {
			$exit_msg = sprintf( __('%1$s requires PHP %2$s or newer.', CCTM_TXTDOMAIN )
				,  CCTM::name
				, $ver );
			$exit_msg .= ' ';	
			$exit_msg .= __('Talk to your system administrator about upgrading.', CCTM_TXTDOMAIN);	
			CCTM::$errors[] = $exit_msg;
		}
	}
	
	
	
	/**
	 * List all tests to run here.
	 * If there are errors, CCTMtests::$errors will get populated.
	 * Die on error.
	 */
	public static function run_tests() {
		
		// Run Tests (add new tests to the CCCTMtests class as req'd)
		self::wp_version_gt(CCTM::wp_req_ver);
		self::php_version_gt(CCTM::php_req_ver);
		self::mysql_version_gt(CCTM::mysql_req_ver);

		self::incompatible_plugins( array('Magic Fields','Custom Post Type UI','CMS Press') );
		
		if (!empty(CCTM::$errors)) {
			$msg = '<h3>'. sprintf( __('The %s plugin encountered errors! It cannot load!', CCTM_TXTDOMAIN)
				, CCTM::name) . '</h3>';
			$msg .= '<ul>';
			foreach (CCTM::$errors as $e) {
				$msg .= '<li>'.$e.'</li>';
			}
			$msg .= '</ul>';
			die($msg);  // We can't work with the errors.
		}
	}
	
}
/*EOF*/