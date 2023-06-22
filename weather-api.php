<?php

function get_weather_data($api_key, $location) {
    $base_url = 'https://api.weatherapi.com/v1/forecast.json';
    $url = add_query_arg(
        array(
            'key' => $api_key,
            'q' => urlencode($location),
            'days' => 7,
            'aqi' => 'no',
            'alerts' => 'no'
        ),
        $base_url
    );

    // Make the API request using the WordPress HTTP API
    $response = wp_remote_get($url);

    // Check if the request was successful
    if (is_wp_error($response)) {
        // Handle API request error
        $error_message = $response->get_error_message();
        return array('error' => 'API request failed: ' . $error_message);
    }

    // Retrieve the response body
    $body = wp_remote_retrieve_body($response);

    // Parse the JSON response
    $data = json_decode($body, true);

    // Check if the response was successfully parsed
    if (is_null($data)) {
        // Handle invalid response error
        return array('error' => 'Invalid API response');
    }

    // Check for any API-specific error conditions in the response
    if (isset($data['error'])) {
        // Handle API-specific error
        return array('error' => $data['error']);
    }

    // Return the retrieved weather data
    return $data;
}

