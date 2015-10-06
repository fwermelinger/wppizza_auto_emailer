<?php
    /**
        * Plugin Name: .WPPizza Auto Emailer
        * Plugin URI: https://github.com/fwermelinger/wppizza_auto_emailer
        * Description: Sends emails to customers after a certain amount of time. ATTENTION: Emails will be sent as soon as the plugin is activated!
        * Version: 1.0
        * Author: Florian Wermelinger
        * Author URI: http://www.webtopf.ch
        * License: GPL2
        */
    
        /*  Copyright 2014  Florian Wermelinger  (email : florian@webtopf.ch)
    
        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as 
        published by the Free Software Foundation.
    
        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
    
        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */

        defined('ABSPATH') or die("No direct access!");

        //add_action( 'plugins_loaded', 'wppizza_extend_otc');
        register_uninstall_hook( __FILE__, 'wppizza_auto_emailer_uninstall');

        // Settings page
        if ( ! class_exists( 'WPPIZZA_AE_SETTINGSPAGE' ) ) {
            class WPPIZZA_AE_SETTINGSPAGE {    
                //Holds the values to be used in the fields callbacks
                private $options;   

                public function __construct() {
                    add_action('admin_menu', array($this, 'add_plugin_page'));
                    add_action('admin_init', array($this, 'page_init'));
                    if(!is_admin()){
                        add_action('init', array( $this, 'wppizza_ae_wpml_localization'),99);
                    }
                }

                //Add options page                
                public function add_plugin_page() {
                    // This page will be under "Settings"
                    add_submenu_page(
                        'edit.php?post_type=wppizza',
                        'WPPizza Auto Emailer Settings', 
                        '~ Auto Emailer', 
                        'edit_posts', 
                        'wppizza-auto-emailer',  
                        array( $this, 'wppizza_ae_createpage' )                        
                        );
                }

                // Options page callback             
                public function wppizza_ae_createpage() {
                    include 'inc/options.php';    //page with the layout
                }

                // Register and add settings             
                public function page_init() {        


                }

                /*******************************************************
                *
                *	[WPML : make localizations strings wpml compatible]
                *
                ******************************************************/
                function wppizza_otc_wpml_localization() {
                    require('inc/wpml.inc.php');
                }
            }
        }

        function wpae_unschedule(){
            // Get the timestamp of the next scheduled run and Un-schedule the event
            $timestamp = wp_next_scheduled( 'wpae_daily_sendemail' );    
            if($timestamp) {
                wp_unschedule_event( $timestamp, 'wpae_daily_sendemail' ); 
            }
        }

        function wpae_install() {
            //remove old scheduled event if it exists
            wpae_unschedule();

            //we need to use gmt time() because wordpress cron internally uses gmt too. 
            //http://www.sitepoint.com/mastering-wordpress-cron/
            $timestamp = time();
            wp_schedule_event($timestamp, 'daily', 'wpae_daily_sendemail');
        }      

        function wpae_uninstall() {
            wpae_unschedule();
        }

        

        function wpae_ae_get_email_recepients($days) {
            global $wpdb;

            $query ='';
            $query.='SELECT u.user_email as emailaddr, ID FROM xmk_users u ';
            $query.='INNER JOIN ';
            $query.='   (SELECT DISTINCT subquery.wp_user_id FROM ';
            $query.='       (select o.wp_user_id, o.order_date, (select max(order_date) FROM '.$wpdb->prefix.'wppizza_orders WHERE wp_user_id = o.wp_user_id) as lastorder ';
            $query.='           FROM '.$wpdb->prefix.'wppizza_orders as o ';
            $query.='           WHERE o.order_date BETWEEN CURDATE() - INTERVAL '.($days).' DAY ';
            $query.='           AND CURDATE() - INTERVAL '.($days-1).' DAY ';
            $query.='           AND o.wp_user_id <> 0 ';
            $query.='           AND o.payment_status = \'COMPLETED\') as subquery ';
            $query.='   WHERE subquery.order_date = subquery.lastorder) userids ';
            $query.='ON (userids.wp_user_id = u.ID)';

            $emails = $wpdb->get_results($query, ARRAY_A);
            if($emails){
                return $emails;
            }            
        }

        function wpae_get_userlang_appendix($userid){
            $userlang = get_user_meta( $user_id, 'ynot_userlang', true );
            if ($userlang == false){
                $userlang = 'en';
            }
            if ($userlang != 'en'){
                return '_'. $userlang;
            }
            return '';
        }
        function wpae_autoemailer_callback() {
            $emailerOptionName = 'wppizza-auto-emailer-config';
            $emailerDebugAddrName = 'wppizza-auto-emailer-config-debugemail';

            $statusString = 'Starting emailer at '.current_time('mysql').PHP_EOL;
            //get all orders that were done 14 days before now
            $options = get_option($emailerOptionName);
            $debugAddr = get_option($emailerDebugAddrName);

            if($options){
                $statusString.= 'found '. count($options) .' configs.'.PHP_EOL;
                foreach ($options as $emailConfig) {
                    $emailsToSend = wpae_ae_get_email_recepients($emailConfig['number_of_days']);
                    $statusString.= 'found '. count($emailsToSend) .' email addresses.'.PHP_EOL;
                    
                    if($emailsToSend){
                        foreach($emailsToSend as $k=>$v){
                            $customerAddress = $v['emailaddr'];

                            $to = $customerAddress;

                            if(strlen($debugAddr)>0){
                                $to = $debugAddr;
                            }  

                            $langAppendix = wpae_get_userlang_appendix($v['ID']);                            
                            $subject = stripslashes($emailConfig['subject_line'.$langAppendix]);
                            $message = stripslashes($emailConfig['email_text'.$langAppendix]);                       

                            $statusString.= 'sending email to '. $customerAddress .'; subject: '.$subject.PHP_EOL;
                            $sendResult = wp_mail( $to, $subject, $message,'Bcc: ynotseoul@gmail.com' );
                        }
                    }
                }
            }

            //send status mail to me
            //wp_mail('f.wermelinger@gmail.com', 'Ynot Emails sent', $statusString,'Bcc: ynotseoul@gmail.com');
            file_put_contents(__DIR__.'/sentemails.log', $statusString.PHP_EOL, FILE_APPEND);
        }
        add_action('wpae_daily_sendemail', 'wpae_autoemailer_callback');
        if (is_admin()) 
        {

            register_activation_hook( __FILE__, 'wpae_install' );
            register_deactivation_hook(__FILE__, 'wpae_uninstall');            
            $my_settings_page = new WPPIZZA_AE_SETTINGSPAGE();
        }
     ?>