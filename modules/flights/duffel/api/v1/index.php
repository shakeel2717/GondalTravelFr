<?php

namespace AppRouter;use Exception;use DateTime;use DateInterval;use InvalidArgumentException;class Router{const NO_ROUTE_FOUND_MSG='No route found';private $routes;private $error;private $baseNamespace;private $currentPrefix;private $service=null;public function __construct($error,$baseNamespace=''){$this->routes=[];$this->error=$error;$this->baseNamespace=$baseNamespace==''?'':$baseNamespace.'\\';$this->currentPrefix='';}public function setService($service){$this->service=$service;}public function getService($service){return $this->service;}public function route($method,$regex,$handler){if($method=='*'){$method=['GET','PUT','DELETE','OPTIONS','TRACE','POST','HEAD'];}foreach((array)$method as $m){$this->addRoute($m,$regex,$handler);}return $this;}private function addRoute($method,$regex,$handler){$this->routes[strtoupper($method)][$this->currentPrefix.$regex]=[$handler,$this->service];}public function mount($prefix,callable $routes,$service=false){$previousPrefix=$this->currentPrefix;$this->currentPrefix=$previousPrefix.$prefix;if($service!==false){$previousService=$this->service;$this->service=$service;}$routes($this);$this->currentPrefix=$previousPrefix;if($service!==false){$this->service=$previousService;}return $this;}public function get($regex,$handler){$this->addRoute('GET',$regex,$handler);return $this;}public function post($regex,$handler){$this->addRoute('POST',$regex,$handler);return $this;}public function put($regex,$handler){$this->addRoute('PUT',$regex,$handler);return $this;}public function head($regex,$handler){$this->addRoute('HEAD',$regex,$handler);return $this;}public function delete($regex,$handler){$this->addRoute('DELETE',$regex,$handler);return $this;}public function options($regex,$handler){$this->addRoute('OPTIONS',$regex,$handler);return $this;}public function trace($regex,$handler){$this->addRoute('TRACE',$regex,$handler);return $this;}public function connect($regex,$handler){$this->addRoute('CONNECT',$regex,$handler);return $this;}public function dispatch($method,$path){if(!isset($this->routes[$method])){$params=[$method,$path,404,new HttpRequestException(self::NO_ROUTE_FOUND_MSG)];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}else{foreach($this->routes[$method]as $regex=>$route){$len=strlen($regex);if($len>0){$callback=$route[0];$service=isset($route[1])?$route[1]:null;if($regex[0]!='/')$regex='/'.$regex;if($len>1&&$regex[$len-1]=='/')$regex=substr($regex,0,-1);$regex=str_replace('@','\\@',$regex);if(preg_match('@^'.$regex.'$@',$path,$params)){array_shift($params);try{return $this->call($callback,$service==null?$params:array_merge([$service],$params));}catch(HttpRequestException $ex){$params=[$method,$path,$ex->getCode(),$ex];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}catch(Exception $ex){$params=[$method,$path,500,$ex];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}}}}}return $this->call($this->error,array_merge($this->service==null?[]:[$this->service],[$method,$path,404,new HttpRequestException(self::NO_ROUTE_FOUND_MSG)]));}private function call($callable,array $params=[]){if(is_string($callable)){if(strlen($callable)>0){if($callable[0]=='@'){$callable=$this->baseNamespace.substr($callable,1);}}else{throw new InvalidArgumentException('Route/error callable as string must not be empty.');}$callable=str_replace('.','\\',$callable);}if(is_array($callable)){if(count($callable)!==2)throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');if(strlen($callable[0])>0){if($callable[0][0]=='@'){$callable[0]=$this->baseNamespace.substr($callable[0],1);}}else{throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');}$callable[0]=str_replace('.','\\',$callable[0]);}return call_user_func_array($callable,$params);}public function dispatchGlobal(){$pos=strpos($_SERVER['REQUEST_URI'],'?');return $this->dispatch($_SERVER['REQUEST_METHOD'],'/'.trim(substr($pos!==false?substr($_SERVER['REQUEST_URI'],0,$pos):$_SERVER['REQUEST_URI'],strlen(implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/')),'/'));}}class HttpRequestException extends Exception{}

$root=(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'];
$root.= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('root', $root);
//use AppRouter\Router;

/* 404 page init */
$router = new Router(function ($method, $path, $statusCode, $exception) {
http_response_code($statusCode);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
echo " Wrong Request";
});

$router->get('/', function() { ?>

<link rel="stylesheet" href="https://raw.githack.com/qaxim/material/main/assets/css/style.css" />
<form method="post" action="search" target>
<div class="container mt-5 card">
<h2><strong>Flights Search</strong></h2>
<hr>
<label><strong>Endpoint</strong><label>
<?=root?>search method POST
<hr>

<div class="row g-2">

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="DXB" placeholder=" " name="origin">
  <span>origin</span>
  </label>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="LHE" placeholder=" " name="destination">
  <span>destination</span>
  </label>
  </div>

  <div class="col-md-3">
  <div class="row g-2">

  <div class="col-md-6">
  <label class="filled w100">
  <input type="text" value="<?=date('d-m-Y',strtotime('+10 day'))?>" placeholder=" " name="departure_date">
  <span>departure_date</span>
  </label>
  </div>

  <div class="col-md-6">
  <label class="filled w100">
  <input type="text" value="<?=date('d-m-Y',strtotime('+12 day'))?>" placeholder=" " name="return_date">
  <span>return_date</span>
  </label>
  </div>

  </div>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="1" placeholder=" " name="adults">
  <span>adults</span>
  </label>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="1" placeholder=" " name="childrens">
  <span>childrens</span>
  </label>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="0" placeholder=" " name="infants">
  <span>infants</span>
  </label>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <input type="text" value="USD" placeholder=" " name="currency">
  <span>currency</span>
  </label>
  </div>
  
  <div class="col-md-2">
  <label class="filled w100">
  <select name="type">
  <option value="oneway">oneway</option>          
  <option value="return">return</option>          
  </select>
  <span></span>
  </label>
  </div>

  <div class="col-md-2">
  <label class="filled w100">
  <select name="class_trip">
  <option value="ECONOMY">economy</option>          
  </select>
  <span></span>
  </label>
  </div>

  <div class="col-md-1">
  <label class="filled w100">
  <select name="evn">
  <option value="dev" selected>dev</option>
  <option value="pro">pro</option>     
  </select>
  <span></span>
  </label>
  </div>

  <div class="col-md-2 df">
  <button class="btn w100" type="submit" onclick="loading()" style="height:62px">Search</button><br>
  </div>

  <div class="row g-1">
    <hr class="my-3">

  <div class="col-md-4">
  <label class="filled w100">
  <input type="text" value="duffel_test_oyyxovuEgfPWTzNWZbEzmGZuNUljz_uPjiUg8DftVxx" placeholder=" " name="c1">
  <span>c1</span>
  </label>
  </div>


<progress id="loading" style="display:none" class="linear mt-3"></progress>
<script>
function loading() { document.getElementById("loading").style.display = "block";}
</script>
</div>
</div>
</div>

</form>

<?php  });

$router->post('/search', function() {

if(isset($_POST['origin']) && trim($_POST['origin']) !== "") {} else { echo "origin : LHE - param or value missing "; die; }
if(isset($_POST['destination']) && trim($_POST['destination']) !== "") {} else { echo "destination : DXB - param or value missing "; die; }
if(isset($_POST['departure_date']) && trim($_POST['origin']) !== "") {} else { echo "departure_date : 10-10-2021 - param or value missing "; die; }
if(isset($_POST['adults']) && trim($_POST['adults']) !== "") {} else { echo "adults : 1 - param or value missing "; die; }
if(isset($_POST['childrens']) && trim($_POST['childrens']) !== "") {} else { echo "childrens : 1 - param or value missing "; die; }
if(isset($_POST['infants']) && trim($_POST['infants']) !== "") {} else { echo "infants : 1 - param or value missing "; die; }
if(isset($_POST['currency']) && trim($_POST['currency']) !== "") {} else { echo "currency : USD - param or value missing "; die; }
if(isset($_POST['type']) && trim($_POST['type']) !== "") {} else { echo "type : oneway | return - param or value missing "; die; }
if(isset($_POST['c1']) && trim($_POST['c1']) !== "") {} else { echo "c1 - param or value missing "; die; }
$type = $_POST['type'];
$c1 = $_POST['c1'];





/*flight date & time*/
$departureDate = strtoupper(date('Y-m-d',strtotime($_POST['departure_date'])));
$returnDate = strtoupper(date('Y-m-d',strtotime($_POST['return_date'])));
$destination = $_POST['destination'];
$origin = $_POST['origin'];
/*end flight date & time*/

  if($type == 'oneway'){
    $payload = json_encode(array(
      'data' => array(
        'cabin_class' => "economy",
        'slices' => [array(
          "departure_date" => $departureDate,
          "destination" => $destination,
          "origin" => $origin
        )],
        "passengers" => [array(
          "type" => "adult"
        )]
      )
    ));
  }else{
    $payload = json_encode(array(
      'data' => array(
        'cabin_class' => "economy",
        'slices' => [array(
          "departure_date" => $departureDate,
          "destination" => $destination,
          "origin" => $origin
        ),
        array(
          "departure_date" => $departureDate,
          "destination" => $origin,
          "origin" => $destination
        )
      ],
        "passengers" => [array(
          "type" => "adult"
        )]
      )
    ));
  }

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.duffel.com/air/offer_requests');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Accept-Encoding: gzip';
$headers[] = 'Duffel-Version: beta';
$headers[] = 'Authorization: Bearer '.$c1;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

$decode = json_decode($result, true);
$data = $decode['data']['offers'];
$final_array = [];
    foreach ($data as $value) {
        $booking_token = ($value['id']);
        $return_array = [];
     foreach ($value['slices'] as $segment){

         $sub_array = array();
         foreach ($segment['segments'] as $key){
             $uri = explode('/', $_SERVER['REQUEST_URI']);
             $root = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
             if ($_SERVER['HTTP_HOST'] == 'localhost') {
                 $img_code = $root . "/" . $uri[1] . '/modules/global/resources/flights/airlines/'.$key['operating_carrier']['iata_code'].'.png';
             } else {
                 $img_code = $root . '/modules/global/resources/flights/airlines/'.$key['operating_carrier']['iata_code'].'.png';
             }
             $sub_array[]  = (object)[
                 'flight_id'=> $key['id'],
                 'departure_flight_no'=> $key['operating_carrier_flight_number'],
                 'img' => $img_code,
                 'departure_time'=> date("h:i a", strtotime($key['departing_at'])),
                 'departure_date'=> date("d-m-Y", strtotime($key['departing_at'])),
                 'arrival_time'=> date("h:i a", strtotime($key['arriving_at'])),
                 'arrival_date'=> date("d-m-Y", strtotime($key['arriving_at'])),
                 'departure_code'=> $key['origin']['city_name'],
                 'departure_airport'=> $key['origin']['name'],
                 'arrival_code'=> $key['destination']['city_name'],
                 'arrival_airport'=> $key['destination']['name'],
                 'duration_time'=> $key['duration'],
                 'currency_code'=> $value['base_currency'],
                 'price'=> $value['total_amount'],
                 'adult_price'=> $value['total_amount'],
                 'child_price'=> $value['total_amount'],
                 'infant_price'=> $value['total_amount'],
                 'url'=> '',
                 'airline_name'=> $key['operating_carrier']['iata_code'],
                 'airline_code'=> $key['operating_carrier']['iata_code'],
                 'class_type'=> $key['passengers'][0]['cabin_class'],
                 'form'=> array(
                     'svc_id' => $value['owner']['id'],
                     'passenger_id' => $key['passengers'][0]['passenger_id'],
                 ),
                 'form_name'=> '',
                 'action'=> '',
                 'type'=> '',
                 'luggage'=> 'As per standard baggage policy',
                 'desc'=> '',
                 'booking_token'=> $booking_token,
             ];

         }
         $return_array["segments"][] = $sub_array;
     }
        $final_array[] = $return_array;
  }
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
if (!empty($final_array)) {
  echo json_encode($final_array);
 }else{
  echo json_encode([array('msg'=>'no_result')]);
 }

});


// Duffel Booking
$router->post('/booking', function() {

$data = $_POST['data'];
$c1 = $_POST['c1'];

$decode_res = json_decode($data);
$booking_guest_info = json_decode($decode_res->booking_guest_info);
$routes = json_decode($decode_res->routes);
$route = $routes[0][0]->form;

$pit = "pit_00009h".$decode_res->booking_id.$decode_res->booking_ref_no;

$guests = [];
foreach ($booking_guest_info as $key) {
  if($key->title == 'Mr'){$gender = 'm';}else{$gender = 'f';}
  if($key->traveller_type == "adults"){$t_type = "adult";}
    $guests = array(
        "type" => ($t_type) ? $t_type : $key->traveller_type,
        "title" => $key->title,
        "phone_number" => $decode_res->ai_mobile,
        "id" => $route->passenger_id,
        "given_name" => $key->first_name,
        "gender" => $gender,
        "family_name" => $key->last_name,
        "email" => $decode_res->accounts_email,
        "born_on" => date('Y-m-d', strtotime($key->dob_year.'-'.$key->dob_month.'-'.$key->dob_day))
      );
}

$payload = json_encode(array(
  'data' => array(
    "type" => "instant",
    "selected_offers" => [
      $routes[0][0]->booking_token
    ],
    "payments" => [
      array(
        "type" => "balance",
        "currency" => $routes[0][0]->currency_code,
        "amount" => $routes[0][0]->actual_price
      )
    ],
    "passengers" => [$guests],
    "metadata"=> array(
      "payment_intent_id" => $pit
    )
  )
));
$order_url = "https://api.duffel.com/air/orders";
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $order_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Accept-Encoding: gzip';
$headers[] = 'Duffel-Version: beta';
$headers[] = 'Authorization: Bearer '.$c1;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$decode = json_decode($result, true);
$pnr = (json_decode($result)->data->booking_reference);

$response = array('booking_pnr' => $pnr, 'result' => $result);

echo json_encode($response);

});
/*flights booking api end*/
$router->dispatchGlobal();