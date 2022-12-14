<?php

/*
Plugin Name: Brave SMS
Plugin URI: https://webforttechnologies.com/
Description: A wordpress plugin for sending bulk SMS using Twilio
Version:  1.0.0
Author: Kishan Kumar
*/

require_once( plugin_dir_path( __FILE__ ) .'/twilio-lib/src/Twilio/autoload.php');
use Twilio\Rest\Client;

class BraveSMS
{
    public $pluginName = "BraveSMS";

    public function displayBraveSMSSettingsPage(){
       include_once "braveSMS-admin-settings.php";
    }

    public function addBraveSMSAdminOption(){
        add_options_page(
           "BraveSMS PAGE",
           "BraveSMS",
           "manage_options",
           $this->pluginName,
           [$this, "displayBraveSMSSettingsPage"]
        );
    }


    /**
     * Registers and Defines the necessary fields we need.
     *  @since    1.0.0
     */
    public function braveSMSAdminSettingsSave()
    {
        register_setting(
            $this->pluginName,
            $this->pluginName,
            [$this, "pluginOptionsValidate"]
        );
        add_settings_section(
            "braveSMS_main",
            "Main Settings",
            [$this, "braveSMSSectionText"],
            "braveSMS-settings-page"
        );
        add_settings_field(
            "api_sid",
            "API SID",
            [$this, "braveSMSSettingSid"],
            "braveSMS-settings-page",
            "braveSMS_main"
        );
        add_settings_field(
            "api_auth_token",
            "API AUTH TOKEN",
            [$this, "braveSMSSettingToken"],
            "braveSMS-settings-page",
            "braveSMS_main"
        );
    }

    /**
     * Displays the settings sub header
     *  @since    1.0.0
     */
    public function braveSMSSectionText()
    {
        echo '<h3 style="text-decoration: underline;">Edit api details</h3>';
    }

    /**
     * Renders the sid input field
     *  @since    1.0.0
     */
    public function braveSMSSettingSid()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_sid]'
                name='$this->pluginName[api_sid]'
                size='40'
                type='text'
                value='{$options['api_sid']}'
                placeholder='Enter your API SID here'
            />
        ";
    }

    /**
     * Renders the auth_token input field
     *
     */
    public function braveSMSSettingToken()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_auth_token]'
                name='$this->pluginName[api_auth_token]'
                size='40'
                type='text'
                value='{$options['api_auth_token']}'
                placeholder='Enter your API AUTH TOKEN here'
            />
        ";
    }

    /**
     * Sanitizes all input fields.
     *
     */
    public function pluginOptionsValidate($input)
    {
        $newinput["api_sid"] = trim($input["api_sid"]);
        $newinput["api_auth_token"] = trim($input["api_auth_token"]);
        return $newinput;
    }

    /**
     * Register the sms page for the admin area.
     *  @since    1.0.0
     */
    public function registerBraveSMSSmsPage()
    {
        // Create our settings page as a submenu page.
        add_submenu_page(
            "tools.php", // parent slug
            __("braveSMS PAGE", $this->pluginName . "-sms"), // page title
            __("Brave SMS", $this->pluginName . "-sms"), // menu title
            "manage_options", // capability
            $this->pluginName . "-sms", // menu_slug
            [$this, "displayBraveSMSSmsPage"] // callable function
        );
    }

    /**
     * Display the sms page - The page we are going to be sending message from.
     *  @since    1.0.0
     */
    public function displayBraveSMSSmsPage()
    {
        include_once "braveSMS-admin-sms-page.php";
    }


    public function send_message()
    {
        if (!isset($_POST["send_sms_message"])) {
            return;
        }

        $to        = (isset($_POST["numbers"])) ? $_POST["numbers"] : "";
        $sender_id = (isset($_POST["sender"]))  ? $_POST["sender"]  : "";
        $message   = (isset($_POST["message"])) ? $_POST["message"] : "";

        //gets our api details from the database.
        $api_details = get_option($this->pluginName);
        if (is_array($api_details) and count($api_details) != 0) {
            $TWILIO_SID = $api_details["api_sid"];
            $TWILIO_TOKEN = $api_details["api_auth_token"];
        }

        try {
            $client = new Client($TWILIO_SID, $TWILIO_TOKEN);
            $response = $client->messages->create(
                $to,
                array(
                    "from" => $sender_id,
                    "body" => $message
                )
            );
            self::DisplaySuccess();
        } catch (Exception $e) {
            self::DisplayError($e->getMessage());
        }
    }

    /**
     * Designs for displaying Notices
     *
     * @since    1.0.0
     * @access   private
     * @var $message - String - The message we are displaying
     * @var $status   - Boolean - its either true or false
     */
    public static function adminNotice($message, $status = true) {
        $class =  ($status) ? "notice notice-success" : "notice notice-error";
        $message = __( $message, "sample-text-domain" );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
    }

    /**
     * Displays Error Notices
     *
     * @since    1.0.0
     * @access   private
     */
    public static function DisplayError($message = "Aww!, there was an error.") {
        add_action( 'adminNotices', function() use($message) {
            self::adminNotice($message, false);
        });
    }

    /**
     * Displays Success Notices
     *
     * @since    1.0.0
     * @access   private
     */
    public static function DisplaySuccess($message = "Successful!") {
        add_action( 'adminNotices', function() use($message) {
            self::adminNotice($message, true);
        });
    }
}

// Create a new BraveSMS instance
$braveSMSInstance = new BraveSMS();
// Add setting menu item
add_action("admin_menu", [$braveSMSInstance , "addBraveSMSAdminOption"]);
// Saves and update settings
add_action("admin_init", [$braveSMSInstance , 'BraveSMSAdminSettingsSave']);

// Hook our sms page
add_action("admin_menu", [$braveSMSInstance , "registerBraveSMSSmsPage"]);

// calls the sending function whenever we try sending messages.
add_action( 'admin_init', [$braveSMSInstance , "send_message"] );