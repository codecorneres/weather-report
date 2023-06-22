<?php
/**
Plugin Name: Weather Report
Version: 4.4.2.7
Author: Code Corners
Text Domain: weather-master
Description: Weather Master is the heavy duty, professional wordpress weather plugin. Just like on TV.
*/
/*
Copyright 2023 Codecorners

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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'weather-api.php';

require_once ABSPATH . 'wp-admin/includes/plugin.php';

register_activation_hook(__FILE__, 'weather_plugin_activate');

register_deactivation_hook(__FILE__, 'weather_plugin_deactivate');

register_uninstall_hook(__FILE__, 'weather_plugin_uninstall');

function weather_plugin_enqueue_styles() {
    wp_enqueue_style( 'weather-plugin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/styles.css' );
}
add_action( 'wp_enqueue_scripts', 'weather_plugin_enqueue_styles' );


// Function to run on plugin uninstallation
function weather_plugin_uninstall() {
    delete_option('weather_plugin_api_key');
    delete_option('weather_plugin_default_location');

    flush_rewrite_rules();
}


// Add a menu item for the plugin in the admin dashboard
function weather_plugin_menu() {
    add_menu_page(
        'Weather Report', 
        'Weather Report',  
        'manage_options',  
        'weather-plugin-settings',
        'weather_plugin_settings'
    );
}
add_action('admin_menu', 'weather_plugin_menu');

// Callback function to display the settings page
function weather_plugin_settings() {
    // Check if the form is submitted and save the settings
    if (isset($_POST['submit'])) {
        // Validate and sanitize the API key
        $api_key = sanitize_text_field($_POST['api_key']);
        $default_location = sanitize_text_field($_POST['default_location']);
        $temperature_unit = isset($_POST['temperature_unit']) ? sanitize_text_field($_POST['temperature_unit']) : '';

        
        if (empty($api_key)) {
            $error_message = 'API key is required.';
        } elseif (empty($default_location)) {
           $error_message = 'Default location is required.';
        } elseif (empty($temperature_unit)) {
            $error_message = 'Please select a temperature unit.';
        }  else {
            update_option('weather_plugin_api_key', $api_key);
            update_option('weather_plugin_default_location', $default_location);
            update_option('weather_plugin_temperature_unit', $temperature_unit);
        }
    }

    $current_api_key = get_option('weather_plugin_api_key');
    $current_default_location = get_option('weather_plugin_default_location');
    $current_temperature_unit = get_option('weather_plugin_temperature_unit');

    ?>
    <div class="wrap">
        <h1>Weather Report Settings</h1>

        <?php if (isset($error_message)): ?>
            <div class="notice notice-error"><p><?php echo $error_message; ?></p></div>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="notice notice-success"><p><?php echo $success_message; ?></p></div>
        <?php endif; ?>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="api_key">API Key</label></th>
                    <td>
                        <input type="text" name="api_key" id="api_key" class="regular-text" value="<?php echo esc_attr($current_api_key); ?>" required="">
                        <p class="description">Enter your API key.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="default_location"> Location</label></th>
                    <td>
                        <input type="text" name="default_location" id="default_location" class="regular-text" value="<?php echo esc_attr($current_default_location); ?>" required="">
                        <p class="description">Enter your Default location</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="temperature_unit">Temperature Unit:</label></th>
                    <td>
                        <input type="radio" name="temperature_unit" id="temperature_unit_celsius" value="celsius" <?php checked($current_temperature_unit, 'celsius'); ?> />
                         <label for="temperature_unit_celsius">Celsius</label>
                         <input type="radio" name="temperature_unit" id="temperature_unit_fahrenheit" value="fahrenheit" <?php checked($current_temperature_unit, 'fahrenheit'); ?> />
                            <label for="temperature_unit_fahrenheit">Fahrenheit</label>
                            <br />

                    </td>
                </tr>

            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}


function display_weather_data_shortcode($atts) {
    // Get the API key and default location from the plugin settings
    $api_key = get_option('weather_plugin_api_key');
    $default_location = get_option('weather_plugin_default_location');
    $temperature_unit = get_option('weather_plugin_temperature_unit');

    // Merge the shortcode attributes with the default location
    $location = shortcode_atts(array('location' => $default_location), $atts)['location'];

    // Fetch the weather data
    $weather_data = get_weather_data($api_key, urlencode($location)); // Ensure location is properly encoded

    //echo'<pre>';print_r($weather_data);echo'</pre>';
    // Check for errors in the weather data
    if (isset($weather_data['error'])) {
        return 'Error retrieving weather data: ' . $weather_data['error']['message'];
    }

    // Format and display the weather data
    $tempincelcius = $weather_data['current']['temp_c'].'°C';
    $tempinFahrenheit = $weather_data['current']['temp_f'].'°F';
    $finaltemp =  ($temperature_unit == 'celsius') ? $tempincelcius : $tempinFahrenheit ;
    $humidity = $weather_data['current']['humidity'];
    $wind_speed = $weather_data['current']['wind_kph'];
    $condition = $weather_data['current']['condition']['icon'];
    $localtime = $weather_data['location']['localtime'];
    $windkph = round($weather_data['location']['current']['wind_kph']);

    //$output = 'Temperature: ' . $temperature . ' ' . $temperature_unit . '<br>';
    //$output .= 'Humidity: ' . $humidity . '%<br>';
    //$output .= 'Wind Speed: ' . $wind_speed . ' kph';

    $output = '<div class="splw-pro-wrapper ">
                  <div class="splw-pro-header">
                    <div class="splw-pro-header-title-wrapper">
                    <div class="header-title">Location: <strong>'.$default_location.'</strong></div>
                    <div class="current-time">Local Time: <strong>'.$localtime.'</strong></div>
                    <div class="cur-temp">
                       <img src="'.$condition.'">
                       <span class="cur-temp">Temprature: <strong>'.$finaltemp.'</strong></span>
                       <p>Humidity: <strong>  '.$humidity.'%</strong></p>
                       <p>Wind: <strong>W - '.$wind_speed.' KPH</strong></p>
                    </div>
                  </div>  
               </div>';

    return $output;
}
add_shortcode('weather_data', 'display_weather_data_shortcode');



function weather_plugin_activate() {
    
}

function weather_plugin_deactivate() {
    
}

