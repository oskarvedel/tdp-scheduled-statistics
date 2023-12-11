<?php

//require_once dirname(__FILE__) . '/statistics-common.php';
//require_once dirname(__FILE__) . '/consolidate_geolocations.php';
//require_once dirname(__FILE__) . '/../tdp-common/tdp-common-plugin.php';

/**
 * Retrieves all gd_place IDs from the current archive result.
 *
 * @return array An array of gd_place IDs.
 */
function get_all_gd_places_from_archive_result()
{
    $all_post_ids = array();
    $all_post_names = array();

    // Loop through each post in the current archive result
    if (have_posts()) :
        while (have_posts()) : the_post();
            // Check if the post type is 'gd_place'
            if (get_post_type() === 'gd_place') {
                // Add gd_place ID to the array
                $all_post_ids[] = get_the_ID();
                $all_post_names[] = get_the_title();
            }
        endwhile;
    endif;

    return array(
        'post_ids' => $all_post_ids,
        'post_names' => $all_post_names
    );
}

/**
 * Retrieves depotrum data for a list of gd_places.
 *
 * @param array $gd_place_ids_list An array of gd_place IDs.
 * @return array An array containing combined depotrum data for the specified list of gd_places.
 */
function get_statistics_data_for_list_of_gd_places($gd_place_ids_list)
{
    $statistics_data = [];
    $counter = 0;

    // Loop through each gd_place ID in the provided list
    foreach ($gd_place_ids_list as $gd_place_id) {
        // Get depotrum data for a single gd_place
        $statistics_data_for_single_gd_place = get_statistics_data_for_single_gd_place_statistics($gd_place_id);
        // Check if depotrum data is available for the gd_place
        if ($statistics_data_for_single_gd_place) {
            // Add depotrum data to the existing statistics data
            foreach ($statistics_data_for_single_gd_place as $field => $value) {
                $value = floatval($value); //convert value to float
                if ($value !== 0 && $value !== null) {
                    if (strpos($field, 'smallest') !== false || strpos($field, 'largest') !== false) {
                        $statistics_data[$field] = find_smallest_or_largest_m2_size_per_geolocation($field, $value, $statistics_data);
                    } else if (strpos($field, 'lowest') !== false || strpos($field, 'highest') !== false) {
                        $statistics_data[$field] = find_lowest_or_highest_price_per_geolocation($field, $value, $statistics_data);
                        if (strpos($field, 'lowest')  !== false) {
                            //trigger_error("updating lowest price field: " . $field . " with value: " . $value, E_USER_WARNING);
                        }
                    } else {
                        $statistics_data[$field] = add_fields($field, $value, $statistics_data);
                        //trigger_error("updarting non-smallestorlargest field: " . $field . " value: " . $value, E_USER_WARNING);
                    }
                }
            }
            $counter++;
        }
    }

    // Calculate averages
    foreach ($statistics_data as $field => $value) {
        if (!is_numeric($value)) {
            trigger_error("field: " . $field . " value: " . $value . " is not numeric. statistics_data var_dump: " . var_dump($statistics_data), E_USER_WARNING);
        }
        if (strpos($field, 'average') !== false) {
            round($statistics_data[$field] = $value / $counter, 2);
        }
    }

    return $statistics_data;
}

function add_fields($field, $value, $statistics_data)
{
    if (!is_numeric($value)) {
        trigger_error("field: " . $field . " value: " . $value . " is not numeric. statistics_data var_dump: " . var_dump($statistics_data), E_USER_WARNING);
    }
    if (isset($statistics_data[$field])) {
        return $statistics_data[$field] += $value;
    } else {
        return  $value;
    }
}

function find_smallest_or_largest_m2_size_per_geolocation($field, $value, $statistics_data)
{
    if (!isset($statistics_data[$field])) {
        return $value;
    }

    if ((strpos($field, 'smallest') && $value < $statistics_data[$field]) || (strpos($field, 'largest') && $value > $statistics_data[$field])) {
        return $value;
    } else {
        return $statistics_data[$field];
    }
}

function find_lowest_or_highest_price_per_geolocation($field, $value, $statistics_data)
{
    if (!isset($statistics_data[$field])) {
        //trigger_error("value not set encountered in geolocations statistics-calcs. field: " . $field . " value: " . $value, E_USER_WARNING);
        return $value;
    }

    if ((strpos($field, 'lowest') && $value < $statistics_data[$field]) || (strpos($field, 'highest') && $value > $statistics_data[$field])) {
        //trigger_error("field: " . $field . " value: " . $value ."higher or lower than " . $statistics_data[$field] . " returning value", E_USER_WARNING);
        return $value;
    } else {
        //trigger_error("field: " . $field . " value: " . $value ."NOT higher or lower than " . $statistics_data[$field] . " returning original field", E_USER_WARNING);
        return $statistics_data[$field];
    }
}

function update_gd_place_list_for_geolocation_func()
{
    //consolidate_geolocations();
    //get current list of geolocation ids
    /*
    $geolocation_id = extract_geolocation_id_via_url();
    if (!$geolocation_id) {
        return;
    }
    $current_gd_place_id_list = get_post_meta($geolocation_id, 'gd_place_list', false);

    //get list of place ids from archive result
    $new_gd_place_list = get_all_gd_places_from_archive_result();
*/
    /*
    echo "current gd_place_list var_dump:";
    var_dump($current_gd_place_list);
    echo "<br>";

    echo "new_gd_place_ids_list var_dump:";
    var_dump($new_gd_place_list);
    */
    /*
    $geolocation_slug = extract_geolocation_slug_via_url();

    // Check if the lists are different
    if ($current_gd_place_id_list !== $new_gd_place_list['post_ids']) {
        //if current_gd_place_list is unitialized, initialize it to prevent an error in the array_diff call
        $current_gd_place_id_list = is_bool($current_gd_place_id_list) ? [] : $current_gd_place_id_list;
        // Find the added IDs
        $added_ids = array_diff($new_gd_place_list['post_ids'], $current_gd_place_id_list);
        if (!empty($added_ids)) {
            $message = 'gd_place_ids updated for location ' . $geolocation_slug . '/' . $geolocation_id . "\n";
            $message .= 'New gd_place_list:';
            foreach ($new_gd_place_list['post_names'] as $post_name) {
                $message .= "\n" . $post_name;
            }
            $message .= "\n";
            $message .= 'Added IDs: ' . implode(', ', $added_ids) . "\n";
            trigger_error($message, E_USER_WARNING);
        }
    }

    update_post_meta($geolocation_id, 'gd_place_names', $new_gd_place_list['post_names']);
    update_post_meta($geolocation_id, 'gd_place_list', $new_gd_place_list['post_ids']);
    update_post_meta($geolocation_id, 'num of gd_places', count($new_gd_place_list['post_ids']));
    */
}



add_shortcode("update_gd_place_list_for_geolocation", "update_gd_place_list_for_geolocation_func");

function update_statistics_data_for_all_geolocations()
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

    foreach ($geolocations as $geolocation) {
        $geolocation_id = $geolocation->ID;
        $message = "updating data for geolocation: " . $geolocation->post_name . " with id: " . $geolocation_id . "\n";
        //trigger_error("updating data for geolocation: " . $geolocation->post_name, E_USER_WARNING);

        $gd_place_ids_list = get_post_meta($geolocation_id, 'gd_place_list', false);

        $message .= "gd_place_list var_dump: \n";
        foreach ($gd_place_list as $gd_place) {
            $message .= "gd_place_list element: " . $gd_place . "\n";
        }
        $message .= "gd_place_list var_dump:" . var_dump($gd_place_list) . "\n";
        $message .= "gd_place_list print_r:" . print_r($gd_place_list) . "\n";

        trigger_error($message, E_USER_WARNING);

        $depotrum_data = get_statistics_data_for_list_of_gd_places($gd_place_ids_list);
        //trigger_error("depotrum_data var_dump:" . var_dump($depotrum_data), E_USER_WARNING);
        foreach ($depotrum_data as $field => $value) {
            update_post_meta($geolocation_id, $field, $value);
            if (strpos($field, 'lowest')  !== false) {
                //trigger_error("updating lowest price field: " . $field . " with value: " . $value .  "for geolocation "  . $geolocation->post_name, E_USER_WARNING);
            }
            //trigger_error("updated field: " . $field . " with value: " . $value . "for geolocation" . $geolocation->post_name , E_USER_WARNING);
        }
    }
    trigger_error("updated statistics data for all geolocations", E_USER_NOTICE);
}
