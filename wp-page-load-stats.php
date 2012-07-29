<?php
/*
Plugin Name: WP Page Load Stats
Plugin URI: http://mikejolley.com/projects/wp-page-load-stats/
Description: Display memory, page load time, average load time and query count in the footer. Requires PHP 5.2.0+
Version: 1.0.0
Author: Mike Jolley
Author URI: http://mikejolley.com
*/

class WP_Page_Load_Stats {
	
	private $average_option;
	
	/**
	 * Gets things started
	 */
	function __construct() {
		// Init
		add_action( 'init', array( &$this, 'init' ) );
		
		// Frontend
		add_action( 'wp_head', array( &$this, 'wp_head' ) );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
		
		// Backend
		add_action( 'admin_head', array( &$this, 'wp_head' ) );
		add_action( 'admin_footer', array( &$this, 'wp_footer' ) );
		
		// Enqueue
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue' ) );
		
		// Where to store averages
		$this->average_option = is_admin() ? 'wp_pls_admin_load_times' : 'wp_pls_load_times';
	}
	
	/**
	 * init function.
	 * 
	 * @access public
	 */
	function init() {
		load_plugin_textdomain( 'wp_pls', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
		if ( isset( $_GET['reset_wp_pls_stats'] ) && $_GET['reset_wp_pls_stats'] == 1 ) {
			update_option( $this->average_option, array() );
			wp_safe_redirect(  wp_get_referer() );
			exit;
		}
	}
	
	/**
	 * wp_head function.
	 * 
	 * @access public
	 */
	function wp_head() {
		echo "<script type='text/javascript'>
			function wp_pls_hide(){
			   var wpplsDiv = document.getElementById('wp_pls');
			   wpplsDiv.style.display = 'none';
			}
		</script>";
	}
		
	/**
	 * wp_footer function.
	 * 
	 * @access public
	 */
	function wp_footer() {
		$this->display();
	}
	
	/**
	 * enqueue function.
	 * 
	 * @access public
	 */
	function enqueue() {
        wp_enqueue_style( 'wp_pls-style', plugins_url('style.css', __FILE__) );
	}
	
	/**
	 * display function.
	 * 
	 * @access public
	 */
	function display() {
		// Get values we're displaying
		$timer_stop 		= timer_stop(0);
		$query_count 		= get_num_queries();
		$memory_usage 		= round( $this->convert_bytes_to_hr( memory_get_usage() ), 2 );
		$memory_peak_usage 	= round( $this->convert_bytes_to_hr( memory_get_peak_usage() ), 2 );
		$memory_limit 		= round( $this->convert_bytes_to_hr( $this->let_to_num( WP_MEMORY_LIMIT ) ), 2 );
		$load_times			= array_filter( (array) get_option( $this->average_option ) );
		
		$load_times[]		= $timer_stop;
		
		// Update load times
		update_option( $this->average_option, $load_times );
		
		// Get average
		if ( sizeof( $load_times ) > 0 )
			$average_load_time = round( array_sum( $load_times ) / sizeof( $load_times ), 4 );
		
		// Display the info
		?>
		<div id="wp_pls">
			<ul>
				<li><?php printf( __( '%s queries in %s seconds.', 'wp_pls' ), $query_count, $timer_stop ); ?></li>
				<li><?php printf( __( 'Average load time of %s (%s runs).', 'wp_pls' ), $average_load_time, sizeof( $load_times ) ); ?></li>
				<li><?php printf( __( '%s out of %s MB (%s) memory used.', 'wp_pls' ), $memory_usage, $memory_limit, round( ( $memory_usage / $memory_limit ), 2 ) * 100 . '%' ); ?></li>
				<li><?php printf( __( 'Peak memory usage %s MB.', 'wp_pls' ), $memory_peak_usage ); ?></li>
			</ul>
			<div class="actions">
				<a onclick="wp_pls_hide()"" href="javascript:void(0);">&times;</a>
				<a class="reset" href="<?php echo add_query_arg( 'reset_wp_pls_stats', 1 ); ?>">-</a>
			</div>
		</div>
		<?php
	}
	
	/**
	 * let_to_num function.
	 * 
	 * This function transforms the php.ini notation for numbers (like '2M') to an integer
	 *
	 * @access public
	 * @param $size
	 * @return int
	 */
	function let_to_num( $size ) {
	    $l 		= substr( $size, -1 );
	    $ret 	= substr( $size, 0, -1 );
	    switch( strtoupper( $l ) ) {
		    case 'P':
		        $ret *= 1024;
		    case 'T':
		        $ret *= 1024;
		    case 'G':
		        $ret *= 1024;
		    case 'M':
		        $ret *= 1024;
		    case 'K':
		        $ret *= 1024;
	    }
	    return $ret;
	}	
	
	/**
	 * convert_bytes_to_hr function.
	 * 
	 * @access public
	 * @param mixed $bytes
	 */
	function convert_bytes_to_hr( $bytes ) {
		$units = array( 0 => 'B', 1 => 'kB', 2 => 'MB', 3 => 'GB' );
		$log = log( $bytes, 1024 );
		$power = (int) $log;
		$size = pow(1024, $log - $power);
		return $size . $units[$power];
	}
	
}

$WP_Page_Load_Stats = new WP_Page_Load_Stats();