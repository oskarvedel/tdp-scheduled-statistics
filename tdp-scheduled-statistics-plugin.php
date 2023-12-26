<?php

/**
 * Plugin Name: tdp-scheduled-statistics-plugin
 * Version: 1.0
 */

require_once dirname(__FILE__) . '/scheduled-statistics-calcs-per-geolocation.php';
require_once dirname(__FILE__) . '/scheduled-statistics-calcs-per-gd-place.php';
include dirname(__FILE__) . '/tdp-common-statistics.php';

// Define the activation function
function tdp_scheduled_statistics_plugin_activation_function()
{
    // Check if the scheduled event is already set
    // wp_schedule_event(time(), 'daily', 'tdp_scheduled_statistics_daily_event');
    trigger_error("tdp_scheduled_statistics_plugin_daily_function activated", E_USER_NOTICE);
}

register_activation_hook(__FILE__, 'tdp_scheduled_statistics_plugin_activation_function');

// Define the deactivation function
function  tdp_scheduled_statistics_plugin_deactivation_function()
{
    // Unschedule the daily event when the plugin or theme is deactivated
    trigger_error("tdp_scheduled_statistics_plugin_daily_function deactivated", E_USER_NOTICE);
    wp_clear_scheduled_hook('tdp_scheduled_statistics_daily_event');
}

// Hook the activation and deactivation functions
register_deactivation_hook(__FILE__, 'tdp_scheduled_statistics_plugin_deactivation_function');


// Hook the daily function to the scheduled event
add_action('tdp_scheduled_statistics_daily_event', 'tdp_scheduled_statistics_plugin_daily_function');

// Define the function to be executed daily
function tdp_scheduled_statistics_plugin_daily_function()
{
    update_statistics_data_for_all_gd_places();
    update_statistics_data_for_all_geolocations();
    trigger_error("tdp_scheduled_statistics_plugin_daily_function just ran", E_USER_NOTICE);
}

//add a button to update statistics data for all gd_places the plugin settings page
function add_update_statistics_data_for_all_gd_places_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_statistics_data_for_all_gd_places')) . '">Run statistics for gd_places</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-statistics/tdp-scheduled-statistics-plugin.php', 'add_update_statistics_data_for_all_gd_places_button');

function handle_update_statistics_data_for_all_gd_places()
{
    update_statistics_data_for_all_gd_places();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_statistics_data_for_all_gd_places', 'handle_update_statistics_data_for_all_gd_places');

//add a button to update statistics data for all geolocations the plugin settings page
function add_update_statistics_data_for_all_geolocations_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_statistics_data_for_all_geolocations')) . '">Run statistics for geolocations</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-statistics/tdp-scheduled-statistics-plugin.php', 'add_update_statistics_data_for_all_geolocations_button');

function handle_update_statistics_data_for_all_geolocations()
{
    update_statistics_data_for_all_geolocations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_statistics_data_for_all_geolocations', 'handle_update_statistics_data_for_all_geolocations');


function add_update_all_statistics_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_all_statistics')) . '">Run ALL statistics</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-statistics/tdp-scheduled-statistics-plugin.php', 'add_update_all_statistics_button');

function handle_update_all_statistics()
{
    update_statistics_data_for_all_gd_places();
    update_statistics_data_for_all_geolocations();
    trigger_error("updated ALL statistics", E_USER_NOTICE);
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_all_statistics', 'handle_update_all_statistics');
