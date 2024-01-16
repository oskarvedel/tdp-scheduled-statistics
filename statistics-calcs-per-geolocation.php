<?php

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
            $counter++;
            // Add depotrum data to the existing statistics data
            foreach ($statistics_data_for_single_gd_place as $field => $value) {
                $value = floatval($value); //convert value to float
                if ($value != 0 && $value !== null) {
                    if (strpos($field, 'smallest') !== false || strpos($field, 'largest') !== false) {
                        $statistics_data[$field] = find_smallest_or_largest_m2_size_per_geolocation($field, $value, $statistics_data);
                    } else if (strpos($field, 'lowest') !== false || strpos($field, 'highest') !== false) {
                        $statistics_data[$field] = find_lowest_or_highest_price_per_geolocation($field, $value, $statistics_data);
                    } else {
                        $statistics_data[$field] = add_fields($field, $value, $statistics_data);
                        // trigger_error("updarting non-smallestorlargest field: " . $field . " value: " . $value, E_USER_WARNING);
                    }
                }
            }
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
    if (array_key_exists($field, $statistics_data)) {
        return $statistics_data[$field] += $value;
    } else {
        return  $value;
    }
}

function find_smallest_or_largest_m2_size_per_geolocation($field, $value, $statistics_data)
{
    if (!array_key_exists($field, $statistics_data)) {
        return $value;
    }

    if ((strpos($field, 'smallest') !== false  && $value < $statistics_data[$field]) || (strpos($field, 'largest')  !== false  && $value > $statistics_data[$field])) {
        return $value;
    } else {
        return $statistics_data[$field];
    }
}

function find_lowest_or_highest_price_per_geolocation($field, $new_value, $statistics_data)
{
    if (!array_key_exists($field, $statistics_data)) {
        //trigger_error("value not set encountered in geolocations statistics-calcs. field: " . $field . " value: " . $value, E_USER_WARNING);
        return $new_value;
    }
    $existing_value = $statistics_data[$field];

    if ((strpos($field, 'lowest')  !== false  && $new_value < $existing_value) || (strpos($field, 'highest')  !== false && $new_value > $existing_value)) {
        //trigger_error("field: " . $field . " value: " . $value ."higher or lower than " . $statistics_data[$field] . " returning value", E_USER_WARNING);
        return $new_value;
    } else {
        //trigger_error("field: " . $field . " value: " . $value ."NOT higher or lower than " . $statistics_data[$field] . " returning original field", E_USER_WARNING);
        return $existing_value;
    }
}

function get_gd_places_within_radius_statistics($geolocation, $radius)
{
    $all_gd_places_sorted_by_distance = get_post_meta($geolocation, 'all_gd_places_sorted_by_distance', false);
    $gd_places_within_radius = array_filter($all_gd_places_sorted_by_distance, function ($distance) use ($radius) {
        return $distance <= $radius;
    });
    //$gd_places_within_radius = array_keys($all_gd_places_sorted_by_distance);

    return $gd_places_within_radius;
}

function update_statistics_data_for_all_geolocations()
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
    foreach ($geolocations as $geolocation) {
        $geolocation_id = $geolocation->ID;

        //get all gd_places for geolocation
        $gd_place_list = get_post_meta($geolocation_id, 'gd_place_list', false);
        $gd_place_list_ids = array_map(function ($item) {
            return intval($item['ID']); // or return $item['id']; if $item is an array
        }, $gd_place_list);

        //get all gd_places within 2 km
        $gd_places_within_2_km = get_gd_places_within_radius($geolocation_id, 2);
        // asort($gd_places_within_2_km);
        $gd_places_within_2_km_ids = array_keys($gd_places_within_2_km);

        //get all gd_places in neighbourhoods
        $gd_places_in_neighbourhoods = get_gd_places_in_neighbourhoods($geolocation_id);
        $gd_places_in_neighbourhoods_ids = array_keys($gd_places_in_neighbourhoods);

        //combine all gd_place lists
        $gd_place_list_combined = [];
        $gd_place_list_combined = array_merge($gd_place_list_ids, $gd_places_within_2_km_ids, $gd_places_in_neighbourhoods_ids);
        $gd_place_list_combined = array_unique($gd_place_list_combined);

        $depotrum_data = get_statistics_data_for_list_of_gd_places($gd_place_list_combined);
        //trigger_error("depotrum_data var_dump:" . var_dump($depotrum_data), E_USER_WARNING);
        foreach ($depotrum_data as $field => $value) {
            update_post_meta($geolocation_id, $field, $value);
            //trigger_error("updated field: " . $field . " with value: " . $value . "for geolocation" . $geolocation->post_name , E_USER_WARNING);
        }
    }
    trigger_error("updated statistics data for all geolocations", E_USER_NOTICE);
}
