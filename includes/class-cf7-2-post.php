<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Cf7_2_Post
 * @subpackage Cf7_2_Post/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Cf7_2_Post
 * @subpackage Cf7_2_Post/includes
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
class Cf7_2_Post {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Cf7_2_Post_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'post-my-contact-form-7';
		$this->version = '1.2.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Cf7_2_Post_Loader. Orchestrates the hooks of the plugin.
	 * - Cf7_2_Post_i18n. Defines internationalization functionality.
	 * - Cf7_2_Post_Admin. Defines all hooks for the admin area.
	 * - Cf7_2_Post_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cf7-2-post-loader.php';
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wordpress-gurus-debug-api.php';
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cf7-2-post-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cf7-2-post-admin.php';
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/cf7-post-admin-table.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-cf7-2-post-public.php';

		$this->loader = new Cf7_2_Post_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Cf7_2_Post_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Cf7_2_Post_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Cf7_2_Post_Admin( $this->get_plugin_name(), $this->get_version() );
    /* WP hooks */
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    //modify the CF7 post type
    $this->loader->add_action('init', $plugin_admin, 'modify_cf7_post_type',20);
    //cf7 sub-menu
    $this->loader->add_action('admin_menu',  $plugin_admin, 'add_cf7_sub_menu' );
    //$this->loader->add_filter( 'custom_menu_order', $plugin_admin, 'change_cf7_submenu_order' );
    //modify the cf7 list table columns
    $this->loader->add_filter('manage_wpcf7_contact_form_posts_columns' , $plugin_admin, 'modify_cf7_list_columns',30,2);
    $this->loader->add_action('manage_wpcf7_contact_form_posts_custom_column', $plugin_admin, 'populate_custom_column',10,2);
    //ajax submission
    $this->loader->add_action('wp_ajax_save_post_mapping', $plugin_admin, 'ajax_save_post_mapping');
    //register dynamic posts
    $this->loader->add_action('init',$plugin_admin, 'register_dynamic_posts',20);
    //make sure our dependent plugins exists.
    $this->loader->add_action( 'admin_init', $plugin_admin, 'check_plugin_dependency');
    //override the cf7 shortcodes
    $this->loader->add_action( 'plugins_loaded', $plugin_admin, 'override_cf7_shortcode',20);
    //reset the cf7 admin table
    $cf7_admin = Cf7_WP_Post_Table::set_table();
    if(!$cf7_admin->hooks()){
      $this->loader->add_action( 'admin_enqueue_scripts', $cf7_admin , 'enqueue_styles');
      //add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
      //modify the CF7 post type
      $this->loader->add_action('init', $cf7_admin, 'modify_cf7_post_type' ,20);
      //cf7 sub-menu
      $this->loader->add_action('admin_menu',  $cf7_admin, 'add_cf7_sub_menu' );
      $this->loader->add_filter( 'custom_menu_order', $cf7_admin, 'change_cf7_submenu_order' );
      //modify the cf7 list table columns
      $this->loader->add_filter('manage_wpcf7_contact_form_posts_columns' , $cf7_admin, 'modify_cf7_list_columns' );
      $this->loader->add_action('manage_wpcf7_contact_form_posts_custom_column', $cf7_admin, 'populate_custom_column' ,10,2);
      $this->loader->add_filter('post_row_actions',$cf7_admin, 'modify_cf7_list_row_actions' , 10, 2);
      //change the 'Add New' button link.
      $this->loader->add_action('admin_print_footer_scripts',$cf7_admin, 'change_add_new_button');
      //catch cf7 delete redirection
      $this->loader->add_filter('wp_redirect',$cf7_admin, 'filter_cf7_redirect',10,2);
    }
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Cf7_2_Post_Public( $this->get_plugin_name(), $this->get_version() );
    /* WP hooks */
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

    /*CF7 Hooks*/
    $this->loader->add_filter( 'wpcf7_posted_data', $plugin_public, 'save_cf7_2_post');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Cf7_2_Post_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
