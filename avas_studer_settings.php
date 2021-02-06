<?php
/**
 *
 *
 * @author Madhu <madhu.avasarala@gmail.com>
 * @author Mostafa <mostafa.soufi@hotmail.com>
 * Updated for Cashfree on 20191001
 */
class avas_studer_settings
{

    /**
     * Holds the values to be used in the fields callbacks
     */
    public $options;

	/**
     * Autoload method
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array($this, 'create_avas_studer_settings_page') );

		//call register settings function
	    add_action( 'admin_init', array($this, 'init_avas_studer_settings' ) );
    }

    /**
     * Register woocommerce submenu trigered by add_action 'admin_menu'
     * @return void
     */
    public function create_avas_studer_settings_page()
	{
        // add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
		add_submenu_page(
            'studer', 'Studer Settings', 'Studer Settings', 'manage_options', 'studer_settings', array($this, 'avas_studer_settings_page')
        );
    }



    /**
     * Renders the form for getting settings values for plugin
	 * The settings consist of: cashfree merchant ID, key, Moodle API key
     * @return void
     */
    public function avas_studer_settings_page()
	{

		?>
		<div class="wrap">
            <h1>SriToni cashfree Settings</h1>
            <form method="post" action="options.php">
            <?php
                // https://codex.wordpress.org/Settings_API
                // following is for hidden fields and security of form submission per api
                settings_fields( 'studer_settings' );
                // prints out the sections and fields per API
                do_settings_sections( 'studer_settings' ); // slug of page
                submit_button();    // wordpress submit button for form
            ?>
            </form>
        </div>
        <?php
    }


	/**
	*
	*/
	public function init_avas_studer_settings()
	{
		// register_setting( string $option_group, string $option_name, array $args = array() )
        $args = array(
                        'sanitize_callback' => array( $this, 'sanitize' ),  // function name for callback
            //          'default' => NULL,                  // default values when calling get_options
                     );
		register_setting( 'studer_settings', 'studer_settings' );

		// add_settings_section( $id, $title, $callback, $page );
		add_settings_section( 'studer_section', 'Studer API Settings', array( $this, 'print_section_info' ), 'studer_settings' );

    //add_settings_field( 'reconcile', 'Try Reconciling Payments?', array( $this, 'reconcile_callback' ), 'sritoni_settings', 'cashfree_api_section' );
    add_settings_field( 'studer_email',       'Studer user email',    array( $this, 'studer_email_callback' ),        'studer_settings', 'studer_section' );
		add_settings_field( 'studer_password',    'Studer user password', array( $this, 'studer_password_callback' ),     'studer_settings', 'studer_section' );
    add_settings_field( 'studer_api_baseurl', 'Studer API base url',  array( $this, 'studer_api_baseurl_callback' ),  'studer_settings', 'studer_section' );

  }

	/**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
    * Get the settings option array and print cashfree_key value
    */
   public function studer_api_installation_id_callback()
   {

     $settings = (array) get_option( 'studer_settings' );
     $field = "studer_api_installation_id";
     $value = esc_attr( $settings[$field] );

     echo "<input type='text' name='studer_settings[$field]' id='studer_settings[$field]'
               value='$value'  size='50' class='code' />Studer Installation ID";

   }


	   /**
     * Get the settings option array and print cashfree_key value
     */
    public function studer_email_callback()
    {

    	$settings = (array) get_option( 'studer_settings' );
    	$field = "studer_email";
    	$value = esc_attr( $settings[$field] );

    	echo "<input type='text' name='studer_settings[$field]' id='studer_settings[$field]'
                value='$value'  size='50' class='code' />Studer User Account email ID";

    }


	/**
     * Get the settings option array and print cashfree_secret value
     */
    public function studer_password_callback()
    {
		$settings = (array) get_option( 'studer_settings' );
		$field = "studer_password";
		$value = esc_attr( $settings[$field] );

        echo "<input type='password' name='studer_settings[$field]' id='studer_settings[$field]'
                value='$value'  size='50' class='code' />Studer User Account Password";
    }


    /**
       * Get the settings option array and print cashfree_key value
       */
      public function studer_api_baseurl_callback()
      {

  	$settings = (array) get_option( 'studer_settings' );
  	$field = "studer_api_baseurl";
  	$value = esc_attr( $settings[$field] );

  	echo "<input type='text' name='studer_settings[$field]' id='studer_settings[$field]'
              value='$value'  size='50' class='code' />https://api.studer-innotec.com";

      }



	/**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {

		$new_input = array();

        if( isset( $input['studer_email'] ) )
            $new_input['studer_email'] = sanitize_text_field( $input['studer_email'] );

        if( isset( $input['studer_password'] ) )
            $new_input['studer_password'] = sanitize_text_field( $input['studer_password'] );

        if( isset( $input['studer_api_baseurl'] ) )
            $new_input['studer_api_baseurl'] = sanitize_text_field( $input['studer_api_installation_id'] );


        return $new_input;

    }



}
