<?php
namespace AppRouter; use DOMDocument; use stdClass; use Exception; use DateTime; use DateInterval; use InvalidArgumentException; class Router { const NO_ROUTE_FOUND_MSG = 'No route found'; private $routes; private $error; private $baseNamespace; private $currentPrefix; private $service = null; public function __construct($error, $baseNamespace = '') { $this->routes = []; $this->error = $error; $this->baseNamespace = $baseNamespace == '' ? '' : $baseNamespace . '\\'; $this->currentPrefix = ''; } public function setService($service) { $this->service = $service; } public function getService($service) { return $this->service; } public function route($method, $regex, $handler) { if ($method == '*') { $method = ['GET', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'POST', 'HEAD']; } foreach ((array) $method as $m) { $this->addRoute($m, $regex, $handler); } return $this; } private function addRoute($method, $regex, $handler) { $this->routes[strtoupper($method)][$this->currentPrefix . $regex] = [$handler, $this->service]; } public function mount($prefix, callable $routes, $service = false) { $previousPrefix = $this->currentPrefix; $this->currentPrefix = $previousPrefix . $prefix; if ($service !== false) { $previousService = $this->service; $this->service = $service; } $routes($this); $this->currentPrefix = $previousPrefix; if ($service !== false) { $this->service = $previousService; } return $this; } public function get($regex, $handler) { $this->addRoute('GET', $regex, $handler); return $this; } public function post($regex, $handler) { $this->addRoute('POST', $regex, $handler); return $this; } public function put($regex, $handler) { $this->addRoute('PUT', $regex, $handler); return $this; } public function head($regex, $handler) { $this->addRoute('HEAD', $regex, $handler); return $this; } public function delete($regex, $handler) { $this->addRoute('DELETE', $regex, $handler); return $this; } public function options($regex, $handler) { $this->addRoute('OPTIONS', $regex, $handler); return $this; } public function trace($regex, $handler) { $this->addRoute('TRACE', $regex, $handler); return $this; } public function connect($regex, $handler) { $this->addRoute('CONNECT', $regex, $handler); return $this; } public function dispatch($method, $path) { if (!isset($this->routes[$method])) { $params = [$method, $path, 404, new HttpRequestException(self::NO_ROUTE_FOUND_MSG)]; return $this->call($this->error, $this->service == null ? $params : array_merge([$this->service], $params)); } else { foreach ($this->routes[$method] as $regex => $route) { $len = strlen($regex); if ($len > 0) { $callback = $route[0]; $service = isset($route[1]) ? $route[1] : null; if ($regex[0] != '/') $regex = '/' . $regex; if ($len > 1 && $regex[$len - 1] == '/') $regex = substr($regex, 0, -1); $regex = str_replace('@', '\\@', $regex); if (preg_match('@^' . $regex . '$@', $path, $params)) { array_shift($params); try { return $this->call($callback, $service == null ? $params : array_merge([$service], $params)); } catch (HttpRequestException $ex) { $params = [$method, $path, $ex->getCode(), $ex]; return $this->call($this->error, $this->service == null ? $params : array_merge([$this->service], $params)); } catch (Exception $ex) { $params = [$method, $path, 500, $ex]; return $this->call($this->error, $this->service == null ? $params : array_merge([$this->service], $params)); } } } } } return $this->call($this->error, array_merge($this->service == null ? [] : [$this->service], [$method, $path, 404, new HttpRequestException(self::NO_ROUTE_FOUND_MSG)])); } private function call($callable, array $params = []) { if (is_string($callable)) { if (strlen($callable) > 0) { if ($callable[0] == '@') { $callable = $this->baseNamespace . substr($callable, 1); } } else { throw new InvalidArgumentException('Route/error callable as string must not be empty.'); } $callable = str_replace('.', '\\', $callable); } if (is_array($callable)) { if (count($callable) !== 2) throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.'); if (strlen($callable[0]) > 0) { if ($callable[0][0] == '@') { $callable[0] = $this->baseNamespace . substr($callable[0], 1); } } else { throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.'); } $callable[0] = str_replace('.', '\\', $callable[0]); } return call_user_func_array($callable, $params); } public function dispatchGlobal() { $pos = strpos($_SERVER['REQUEST_URI'], '?'); return $this->dispatch($_SERVER['REQUEST_METHOD'], '/' . trim(substr($pos !== false ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'], strlen(implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/')), '/')); } } class HttpRequestException extends Exception { }
$root = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('root', $root);

ini_set('display_errors', 1);

// =============================================== 404 IF SOMETHING GOES WRONG 
$router = new Router(function ($method, $path, $statusCode, $exception) {
    http_response_code($statusCode);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    echo " Wrong Request";
});

// =============================================== FUNCTION IF NEED TO PRINT THE CONTENT 
function dd($data)
{
    print_r($data);
    die();
}

// =============================================== HEADERS DECRALATIONS
function HEADERS()
{
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
}

// =============================================== CURL REQUEST FUNCTION 
function REQUEST($params)
{

    $url = $params['endpoint'];
    unset($params['endpoint']);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    )
    );

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}


// =============================================== AUTHENTICATION 
function tbo_authenticate($c1, $c2)
{
    $params = array(
        'endpoint' => 'https://xmloutapi.tboair.com/API/V1/Authenticate/ValidateAgency',
        'UserName' => $c1,
        'Password' => $c2,
        'BookingMode' => 'API',
        'IPAddress' => '192.168.11.92',
    );


    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $params['endpoint'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    )
    );

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

// =============================================== SEARCH 
$router->post('api/v1/search', function () {

    HEADERS();

    if (isset($_POST['origin']) && trim($_POST['origin']) !== "") {
    } else {
        echo "origin : LHE - param or value missing ";
        die;
    }
    if (isset($_POST['destination']) && trim($_POST['destination']) !== "") {
    } else {
        echo "destination : DXB - param or value missing ";
        die;
    }
    if (isset($_POST['departure_date']) && trim($_POST['departure_date']) !== "") {
    } else {
        echo "departure_date : 10-10-2021 - param or value missing ";
        die;
    }
    if (isset($_POST['childrens']) && trim($_POST['childrens']) !== "") {
    } else {
        echo "childrens : 1 - param or value missing ";
        die;
    }
    if (isset($_POST['adults']) && trim($_POST['adults']) !== "") {
    } else {
        echo "adults : 1 - param or value missing ";
        die;
    }
    if (isset($_POST['infants']) && trim($_POST['infants']) !== "") {
    } else {
        echo "infants : 1 - param or value missing ";
        die;
    }
    if (isset($_POST['c1']) && trim($_POST['c1']) !== "") {
    } else {
        echo "c1 - param or value missing ";
        die;
    }
    if (isset($_POST['c2']) && trim($_POST['c2']) !== "") {
    } else {
        echo "c2 - param or value missing ";
        die;
    }
    if ($_POST['type'] == 'round') {
        if (isset($_POST['return_date']) && trim($_POST['return_date']) !== "") {
        } else {
            echo "return_date : 10-10-2021 - param or value missing ";
            die;
        }
    }

//
//    $RESP = (tbo_authenticate($_POST['c1'], $_POST['c2']));
//
//    // CONDITION CHECK AUTHENTICATION
//    if (isset(json_decode($RESP)->TokenId)) {
//        $TokenId = (json_decode($RESP)->TokenId);
//    } else {
//        dd("Authentication Failed Please Check Credentials");
//        die;
//    };

    // dd(json_decode($response)->TokenId);

    if ($_POST['type'] == "oneway") {

        $segment = [
            array(
                "Origin" => "LHE",
                "Destination" => "DXB",
                "PreferredDepartureTime" => "2023-05-15T00:00:00",
                "PreferredArrivalTime" => "2023-05-15T00:00:00",
                "PreferredAirlines" => [],
            )
        ];


//        $params = array(
//            "endpoint" => "https://xmloutapi.tboair.com/API/V1/Search/Search",
//            "IPAddress" => "192.168.11.92",
//            "TokenId" => $TokenId,
//            "EndUserBrowserAgent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.128 Safari/537.36",
//            "PointOfSale" => "ID",
//            "RequestOrigin" => "Saudia Arabia",
//            "UserData" => null,
//            "JourneyType" => 1,
//            "AdultCount" => 1,
//            "ChildCount" => 0,
//            "InfantCount" => 0,
//            "FlightCabinClass" => 1,
//            "DirectFlight" => false,
//            "Segment" => ($segment)
//        );
//
//        $response = REQUEST($params);

        $response = file_get_contents('result.json');

        $results = (json_decode($response)->Results);
        $uri = explode('/', $_SERVER['REQUEST_URI']);
        $root = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
        $final_array = [];
        foreach ($results as $result){
           foreach ($result as $value){
               $segments["segments"] = array();
               foreach ($value->Segments as $flight_data){
                   foreach ($flight_data as $segment){

                       if ($_SERVER['HTTP_HOST'] == 'localhost') {
                           $img_code = $root . "/" . $uri[1] . '/modules/global/resources/flights/airlines/'.$segment->OperatingCarrier.'.png';
                       } else {
                           $img_code = $root . '/modules/global/resources/flights/airlines/'.$segment->OperatingCarrier.'.png';
                       }

                       $bj = (object)[

                           'flight_id'=> $segment->FlightNumber,
                           'departure_flight_no'=> $segment->FlightNumber,
                           'img' => $img_code,
                           'departure_time' => date('H:i', strtotime($segment->DepartureTime)),
                           'arrival_time' => date('H:i', strtotime($segment->ArrivalTime)),
                           'departure_date' => date('d-m-Y', strtotime($segment->DepartureTime)),
                           'arrival_date' => date('d-m-Y', strtotime($segment->ArrivalTime)),
                           'departure_code' => $segment->Origin->AirportCode,
                           'arrival_code' => $segment->Destination->AirportCode,
                           'departure_airport'=> $segment->Origin->AirportName,
                           'arrival_airport'=> $segment->Destination->AirportName,
                           'duration_time'=>  $segment->Duration,
                           'currency_code'=> $value->FareBreakdown[0]->Currency,
                           'price'=>  number_format((float)$value->Fare->TotalFare, 2, '.', ''),
                           'adult_price'=> number_format((float)$value->Fare->TotalFare, 2, '.', ''),
                           'child_price'=> number_format((float)$value->Fare->TotalFare, 2, '.', ''),
                           'infant_price'=> number_format((float)$value->Fare->TotalFare, 2, '.', ''),
                           'url'=> '',
                           'airline_name'=>$segment->AirlineDetails->AirlineName,
                           'airline_code'=> $segment->OperatingCarrier,
                           'class_type'=>  $segment->CabinClass,
                           'form'=> '',
                           'form_name'=> '',
                           'action'=> '',
                           'type'=> 'tbo',
                           'luggage'=> 'As per standard baggage policy',
                           'desc'=> '',
                           'booking_token'=> '',
                           'baggage' => $segment->IncludedBaggage,
                           'cabin_baggage' => $segment->CabinBaggage,
                    ];
                       $segments["segments"][0][] = $bj;
                   }

                   $final_array[] = $segments;
               }
           }
        }

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        if (!empty($final_array)) {
            echo json_encode($final_array);
        }else{
            echo json_encode([array('msg'=>'no_result')]);
        }


//        $main_array = array();
//        $object_array = array();
//        foreach ($results as $key) {
//            foreach ($key as $k => $value) {
//                $test_array = array();
//                foreach ($value->Segments as $seg2) {
//                    dd($seg2);
//                    $test_array[] = (object)array(
//                        'img' => './modules/global/resources/flights/airlines/' . $seg2[0]->OperatingCarrier . '.png',
//                        'flight_no' => $seg2[0]->FlightNumber,
//                        'airline' => $seg2[0]->AirlineDetails->AirlineName,
//                        'class' => $seg2[0]->CabinClass,
//                        'baggage' => $seg2[0]->IncludedBaggage,
//                        'cabin_baggage' => $seg2[0]->CabinBaggage,
//                        'departure_time' => date('H:i', strtotime($seg2[0]->DepartureTime)),
//                        'arrival_time' => date('H:i', strtotime($seg2[0]->ArrivalTime)),
//                        'departure_date' => date('d-m-Y', strtotime($seg2[0]->DepartureTime)),
//                        'arrival_date' => date('d-m-Y', strtotime($seg2[0]->ArrivalTime)),
//                        'departure_code' => $seg2[0]->Origin->AirportCode,
//                        'arrival_code' => $seg2[0]->Destination->AirportCode,
//                        'currency' => $key[0]->FareBreakdown[0]->Currency,
//                        'price' => number_format((float)$key[0]->Fare->TotalFare, 2, '.', ''),
//                        'duration_time' => $seg2[0]->Duration,
//                        'adult_price' => 100,
//                        'child_price' => 50,
//                        'infant_price' => 25,
//                        'booking_data' => '',
//                        'redirect_url' => '',
//                        'supplier' => 'tbo',
//                        'type' => 'oneway',
//                    );
//                }
//                $object_array[] = $test_array;
//            }
//            $main_array[]["segments"] = $object_array;
//            $object_array = [];
//        }
//
//        if (!empty($main_array)) {
//            echo json_encode($main_array);
//        } else {
//            echo json_encode([array('message' => 'no_result')]);
//        }


    }

});


$router->dispatchGlobal();