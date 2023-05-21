<?php

// echo "WORKING";
// die;

// MODULE Hotelbeds Merchant API
// API DOCS https://developer.hotelbeds.com/documentation/getting-started/

// ENVIROMENT OF API SERVER
define('ENVIRONMENT', 'production');

// CREATE SEARACH LOG
file_put_contents("RQ.log", print_r($_POST, true));

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

include 'db_connect.php';

if (isset($_POST['evn']) && $_POST['evn'] == 'pro') {
    $endpoint = 'https://api.hotelbeds.com/';
}else{
    $endpoint = 'https://api.test.hotelbeds.com/';
}

header('Content-Type: application/json');

$apiKey = $_POST['c1']; // "eebb256acbadf94ecbbeba9ef81a6c49";
$sharedSecret = $_POST['c2']; //"1359c6b1cd";

// Signature is generated by SHA256 (Api-Key + Shared Secret + Timestamp (in seconds))
$signature = hash("sha256", $apiKey . $sharedSecret . time());


function callAPI($method, $url, $data)
{
    global $apiKey;
    global $signature;
    $curl = curl_init();
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Api-key: ' . $apiKey,
        'X-Signature:' . $signature,
        'Accept: application/json',
        'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    curl_close($curl);
    return $result;
}


function getAmenityDetails()
{
    $hotel_url = "https://api.test.hotelbeds.com/hotel-content-api/1.0/types/amenities";
    $post_opt = array(
        'fields' => 'all',
        'language' => 'ENG',
        'from' => '1',
        'to' => '1000',
        'useSecondaryLanguage' => 'true'
    );
    $get_data = callAPI('GET', $hotel_url, $post_opt);
    $response = json_decode($get_data, true);

    if (!isset($response['error'])) {
        return $response['amenities'];
    } else {
        return [];
    }
}



function searchHotels($search_opt)
{
    global $connect;

    // $cityName = strtolower($search_opt['city']);
    $cityName = ucwords(strtolower($search_opt['city']));


    $sql = "SELECT * FROM `_modules_hotels_hotelbeds_destinations` WHERE destination_name like '%$cityName%'";

    $result = $connect->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_array();

        $country_code = $row['country_code'];
        $destination_code = $row['destination_code'];

        $amenitiesArray = getAmenityDetails();
        $hotel_url = "https://api.test.hotelbeds.com/hotel-content-api/1.0/hotels";
        $post_opt = array(
            'destinationCode' => $destination_code,
            // 'countryCode' => $country_code,
            // 'countryCode' => 'PK',
            'showFullPortfolio' => true,
            'fields' => 'all',
            'language' => 'ENG',
            'useSecondaryLanguage' => 'true',
            'PMSRoomCode' => true,
            'from' => '1',
            'to' => '100'
        );

        $get_data = callAPI('GET', $hotel_url, $post_opt);
        $response = json_decode($get_data, true);

        if (isset($response['hotels']) && count($response['hotels']) > 0) {

            $hotels = $response['hotels'];

            $countryCode = $hotels[0]['countryCode'];

            $hotelsIdList = array_column($hotels, 'code');
            $hotel_booking_search_url = "https://api.test.hotelbeds.com/hotel-api/1.0/hotels";

            $checkin = date('Y-m-d', strtotime($search_opt['checkin']));
            $checkout = date('Y-m-d', strtotime($search_opt['checkout']));


            $filter = [];
            for ($i = 0; $i < $search_opt['childs']; $i++) {
                $filter[] = '{ "type": "CH", "age": 2 }';
            }

            $checkAvailibility = '{
                "stay": {
                    "checkIn": "' . $checkin . '",
                    "checkOut": "' . $checkout . '"
                },
                "occupancies": [
                    {
                        "rooms": ' . $search_opt['rooms'] . ',
                        "adults": ' . $search_opt['adults'] . ',
                        "children": ' . $search_opt['childs'] . ' ';

            ($search_opt['childs'] >= 1) ? ($checkAvailibility .= ',"paxes": [
                        ' . implode(' , ', $filter) . '
                        ]') : null;

            $checkAvailibility .= '}
                ],
                "dailyRate" : true,
                "hotels": {
                    "hotel": ' . json_encode($hotelsIdList) . '
                }

            }';

            $get_data1 = callAPI('POST', $hotel_booking_search_url, $checkAvailibility);
            $response1 = json_decode($get_data1, true);

            $filteredHotels = $response1['hotels']['hotels'];

            if (isset($filteredHotels)) {

                $responseList = array();

                foreach ($filteredHotels as $key => $obj) {

                    $index = array_search($obj['code'], array_column($hotels, 'code'));
                    $filteredHotelsCode = $hotels[$index];

                    // amenities description search
                    $amenitiesCodesArray = isset($filteredHotelsCode['amenityCodes']) ? $filteredHotelsCode['amenityCodes'] : [];
                    foreach ($amenitiesCodesArray as $codeIndex => $codeObj) {
                        $index = array_search($codeObj, array_column($amenitiesArray, 'code'));
                        if (!is_null($index)) {
                            $amenitiesCodesArray[$codeIndex] = $amenitiesArray[$index]['description']['content'];
                        } else {
                            $amenitiesCodesArray[$codeIndex] = "";
                        }
                    }

                    // get the image name from path
                    $img = array();
                    foreach ($filteredHotelsCode['images'] as $image) {
                        if ($image['imageTypeCode'] && $image['imageTypeCode'] == 'GEN') {  // fetch only cover photos
                            $imageName = explode('/', $image['path']);
                            $imageName = end($imageName);
                            $image_url = "https://photos.hotelbeds.com/giata/" . $image['path'];
                            $imgArray = $image_url;

                            // array(
                            //     'imageTypeCode' => $image['imageTypeCode'],
                            //     'path' => $image_url,
                            //     'order' => $image['order'],
                            //     'visualOrder' => $image['visualOrder'],
                            // );
                            $img[] = $imgArray;
                        }
                    }

                    $star = preg_replace('/[^0-9.]+/', '', $obj['categoryName']);

                    array_push($responseList, array(
                        'hotel_id' => $obj['code'],
                        'name' => $obj['name'],
                        'location' => isset($filteredHotelsCode['address']) ? $filteredHotelsCode['address']['content'] : "",
                        'stars' => $star, //
                        'rating' => $star, //
                        'latitude' => $obj['latitude'],
                        'longitude' => $obj['longitude'],
                        'price' => $obj['maxRate'],
                        'actual_price' => $obj['minRate'],
                        'img' => isset($img) ? $img[0] : [],
                        'currency' => $obj['currency'],
                        'actual_currency' => $obj['currency'],
                        // 'redirect' => isset($filteredHotelsCode['web']) ? $filteredHotelsCode['web'] : "",
                        'redirect' => "",
                        'city_code' => $obj['destinationCode'],
                        'country_code' => $countryCode,
                        'address' => isset($filteredHotelsCode['address']) ? $filteredHotelsCode['address']['content'] : "",
                        'discount' => "",
                        'amenities' => $amenitiesCodesArray,
                    ));
                }
                return (json_encode($responseList, JSON_PRETTY_PRINT));
                // return (json_encode($response1, JSON_PRETTY_PRINT));
            } else {
                return (json_encode($response1, JSON_PRETTY_PRINT));
            }
        } else {
            return json_encode($response);
        }
    } else {
        return "No Data Found";
    }

    return true;
}


// $search_opt = array(
//     "city" => "New York",
//     "checkin" => "2022-06-15",
//     "checkout" => "2022-06-31",
//     "adults" => "3",
//     "childs" => "0",
//     "rooms" => "2",
// );

$city = str_replace("-", " ", $_POST['city']);

$search_opt = array(
    "city" => $city,
    // "city" => "new york",
    "checkin" => $_POST['checkin'],
    "checkout" => $_POST['checkout'],
    "adults" => "3",
    "childs" => "0",
    "rooms" => $_POST['rooms'],
    "nationality" => $_POST['nationality']
);

$RESP = searchHotels($search_opt);
echo $RESP;

file_put_contents("RS.log", print_r($RESP, true));

