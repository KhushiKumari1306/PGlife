<?php
session_start();

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require "../includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$city_name = mysqli_real_escape_string($conn, $_GET["city"]); // Security fix

// Get city info
$sql_1 = "SELECT * FROM cities WHERE name = '$city_name'";
$result_1 = mysqli_query($conn, $sql_1);
if (!$result_1) {
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
    return;
}

$city = mysqli_fetch_assoc($result_1);
if (!$city) {
    http_response_code(404);
    echo json_encode(["error" => "No PGs in this city"]);
    return;
}

$city_id = $city['id'];

// Get all properties for the city - NO LIMIT HERE
$sql_2 = "SELECT * FROM properties WHERE city_id = $city_id ORDER BY id"; // Added ORDER BY
$result_2 = mysqli_query($conn, $sql_2);
if (!$result_2) {
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
    return;
}

$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

// Get interested users
$sql_3 = "SELECT * FROM interested_users_properties WHERE property_id IN (
            SELECT id FROM properties WHERE city_id = $city_id
          )";
$result_3 = mysqli_query($conn, $sql_3);
if (!$result_3) {
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
    return;
}

$interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);

// Process properties
$new_properties = array();
foreach ($properties as $property) {
    $property_images = glob("../img/properties/" . $property['id'] . "/*");
    
    // Handle missing images gracefully
    $property_image = "img/properties/default.jpg"; // Default image
    if (!empty($property_images)) {
        $property_image = "img/properties/" . $property['id'] . "/" . basename($property_images[0]);
    }

    $interested_users_count = 0;
    $is_interested = false;
    
    foreach ($interested_users_properties as $interested_user_property) {
        if ($interested_user_property['property_id'] == $property['id']) {
            $interested_users_count++;
            if ($interested_user_property['user_id'] == $user_id) {
                $is_interested = true;
            }
        }
    }
    
    $property['interested_users_count'] = $interested_users_count;
    $property['is_interested'] = $is_interested;
    $property['image'] = $property_image;
    $new_properties[] = $property;
}

// Debug output - uncomment to verify
// file_put_contents('debug.log', print_r($new_properties, true));

echo json_encode($new_properties);
?>