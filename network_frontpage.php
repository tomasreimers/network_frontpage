<?php
/**
 * @package network_frontpage
 * @version 0.1
 */
/*
Plugin Name: Network Frontpage
Plugin URI: http://github.com/berkmancenter/network_frontpage
Description: This plugin allows users to self categorize to be placed in a network frontpage.
Author: Tomas Reimers
Version: 0.1
*/

require_once(ABSPATH . 'wp-includes/pluggable.php');

require_once('includes/network_frontpage_class.php');
$net_front_class = new Network_frontpage_class();

register_activation_hook(
    __FILE__,
    array($net_front_class, 'install')
);

// hook into menu - admin page
function network_frontpage_menu_hook(){
    global $net_front_class;
    add_options_page(
        __('Network Frontpage'),
        __('Network Frontpage'), 
        'manage_options', 
        'network-frontpage', 
        array($net_front_class, 'custom_page')
    );
}
add_action('admin_menu', 'network_frontpage_menu_hook');

?>