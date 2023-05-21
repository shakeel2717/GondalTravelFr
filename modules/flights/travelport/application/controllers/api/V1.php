<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class V1 extends REST_Controller
{
    public $final_array = [];
    public function __construct()
    {
        parent::__construct();

        $this->load->library('ApiClient');
        $this->load->model('V1_model','tm');

       // Load travelport model and populate search form with default values
		$this->load->model('TravelportModel_Conf');
		$this->travelportConfiguration = new TravelportModel_Conf();

		$this->travelportConfiguration->load();
        $this->output->set_content_type('application/json');
        $this->load->library('session');
    }

    //Travelport Search Request
    function search_post()
    {
//      $data = file_get_contents(FCPATH."application/logs/oneway.json");
        $search_query = array();
        $search_query['origin'] = $this->post('origin');
        $search_query['destination'] = $this->post('destination');
        $search_query['departure'] = date('Y-m-d',strtotime($this->post('departure_date')));
        $search_query['arrival'] = date('Y-m-d',strtotime($this->post('return_date')));
        $search_query['triptype'] = $this->post('type');
        $search_query['cabinclass'] = $this->post('class_trip');
        $search_query['passenger']['adult'] = $this->post('adults');
        $search_query['passenger']['children'] = $this->post('infants');
        $search_query['passenger']['infant'] = $this->post('childrens');
        $search_query['passenger']['total'] = 1;

        try {
            $this->data['travelportSearchFormData'] = array(
                'searchQuery' => $search_query,
                'configuration' => $this->travelportConfiguration,
                'requestType' => 'php'
            );
            $this->data['dataAdapter'] = $this->get_response($search_query);
            $segment = $this->segment_format($this->data['dataAdapter']);
        }
            //catch exception
        catch(Exception $e) {
            $this->data['error'] = $e;
        }
        return $segment;
    }

    //Travelport Search Request
    public function get_response($payload)
    {
       $currencey_code = "USD";
        $variables = array('variable_name' => 'AirLowFareSearchReq');
        $this->load->library('travelport/Airservice', $variables);
        $airservice = new Airservice($variables);
        $airLowFareSearchReq = $airservice;

        $origin_city = $airLowFareSearchReq->city_detail($payload['origin']);
        $origin_country = $airLowFareSearchReq->country_detail($origin_city['countryCode']);
        $this->data['flying_from'] = $origin_city['fullname'] .', '. $origin_country['fullname'];
        $destination_city = $airLowFareSearchReq->city_detail($payload['destination']);
        $destination_country = $airLowFareSearchReq->country_detail($destination_city['countryCode']);
        $this->data['flying_to'] = $destination_city['fullname'] .', '. $destination_country['fullname'];

        $this->data['departure_time'] = $payload['departure'];
        $this->data['arrival_time'] = ( ! empty($payload['arrival'])) ? " - " . $payload['arrival'] : "";

        $parameters['AuthorizedBy'] = 'Travelport';
        $parameters['TargetBranch'] = $airLowFareSearchReq->branch_code;

        $parameters['BillingPointOfSaleInfo']['OriginApplication'] = 'UAPI';

        // Passanger
        // $parameters['SearchPassenger']['Code'] = 'ADT';
        $parameters['AirPricingModifiers']['CurrencyType'] = $currencey_code;
        $parameters['AirPricingModifiers']['AccountCodes']['AccountCode']['Code'] = '-';

        // AirSearchModifiers
        $parameters['AirSearchModifiers']['PreferredProviders']['Provider']['Code'] = '1G';

        // Cabin Class
        $parameters['AirSearchModifiers']['PreferredCabins']['CabinClass']['Type'] = $payload['cabinclass'];

        // SearchAirLeg
        $parameters['SearchAirLeg'] = array();
        $SearchAirLeg['SearchOrigin']['CityOrAirport']['Code'] = $payload['origin'];
        $SearchAirLeg['SearchOrigin']['CityOrAirport']['PreferCity'] = 'true';
        $SearchAirLeg['SearchDestination']['CityOrAirport']['Code'] = $payload['destination'];
        $SearchAirLeg['SearchDestination']['CityOrAirport']['PreferCity'] = 'true';
        $SearchAirLeg['SearchDepTime']['PreferredTime'] = $payload['departure'];

        $parameters['SearchAirLeg'][] = $SearchAirLeg;

        if ($payload['triptype'] == 'round')
        {
            $SearchAirLeg['SearchOrigin']['CityOrAirport']['Code'] = $payload['destination'];
            $SearchAirLeg['SearchOrigin']['CityOrAirport']['PreferCity'] = 'true';
            $SearchAirLeg['SearchDestination']['CityOrAirport']['Code'] = $payload['origin'];
            $SearchAirLeg['SearchDestination']['CityOrAirport']['PreferCity'] = 'true';
            $SearchAirLeg['SearchDepTime']['PreferredTime'] = $payload['arrival'];

            $parameters['SearchAirLeg'][] = $SearchAirLeg;
        }

        $parameters['SearchPassenger'] = array();
        $passenger = array();
        // Passenger Adult
        for ($i = 0; $i < $payload['passenger']['adult']; $i++) {
            $parameters['SearchPassenger'][] = array('Code' => 'ADT');
            $passenger[] = array('Code' => 'ADT', 'Name' => 'Adult');
        }

        // Passenger: Children
        for ($i = 0; $i < $payload['passenger']['children']; $i++) {
            $parameters['SearchPassenger'][] = array('Code' => 'CNN');
            $passenger[] = array('Code' => 'CNN', 'Name' => 'Children');
        }

        // Passenger: Infant
        for ($i = 0; $i < $payload['passenger']['infant']; $i++) {
            $parameters['SearchPassenger'][] = array('Code' => 'INF');
            $passenger[] = array('Code' => 'INF', 'Name' => 'Infant');
        }

        $this->session->set_userdata(array('SearchPassenger' => $passenger));
        return $airLowFareSearchReq->service($parameters);
    }


    ///Flight Segments Fornmat
    public function segment_format($data){
                 $main_array = $data;
        foreach ($main_array as $mainindex=>$item) {
            $convert_array = (json_decode(json_encode($main_array),true));
            foreach ($convert_array[$mainindex] as $key=>$getdata) {
                $dataflight = (json_decode(json_encode($convert_array[$mainindex][$key]),true)['0']['flightItinerary']);
                foreach ($dataflight['segments']as $flightindex=>$items){
                    $sub_array = array();
                    $return_array = [];
                    foreach ($items as $idexflight=>$flight){
                        $flightdata = (object)$flight;
                        $price = $flightdata->fareDetails['totalPrice']['value'];
                        $currency_code = $flightdata->fareDetails['totalPrice']['unit'];
                        $duration_time = $getdata['0']['totalDuration']['day'] ."d".$getdata['0']['totalDuration']['hour'] ."h". $getdata['0']['totalDuration']['minute'] ."m";
                        $departure_time = $flightdata->departureTime['time']['hour'].":".$flightdata->departureTime['time']['minute'];
                        $arrival_time = $flightdata->arrivalTime['time']['hour'].":".$flightdata->arrivalTime['time']['minute'];
                        $departure_date = $flightdata->departureTime['date']['day']."-".$flightdata->departureTime['date']['month']."-".$flightdata->departureTime['date']['year'];
                        $arrival_date = $flightdata->arrivalTime['date']['day']."-".$flightdata->arrivalTime['date']['month']."-".$flightdata->arrivalTime['date']['year'];
                        $class_type = $flightdata->bookingInformation['CabinClass'];
                        $airline_name = $this->tm->get_airline_name($flightdata->aircraft['carrier']['code']);
                        $departure_airport = $flightdata->origin['code'];
                        $arrival_airport = $flightdata->destination['code'];
                        $departure_code = $flightdata->origin['fullname'];
                        $arrival_code = $flightdata->destination['fullname'];

                        if(!empty(json_decode(json_encode($convert_array[$mainindex][$key]),true)['1']['flightItinerary'])) {
                            $dataflightreturn = json_decode(json_encode($convert_array[$mainindex][$key]), true)['1']['flightItinerary'];
                            foreach ($dataflightreturn['segments'] as $returnflightindex => $return_items) {
                                $hidden_parameters = (object)[
                                    'outbound' => implode(',', array_column($items, 'key')),
                                    'inbound' => implode(',', array_column($return_items, 'key')),
                                ];
                            }
                        }else{
                            $hidden_parameters = (object)[
                                'outbound' => implode(',', array_column($items, 'key')),
                            ];
                        }

                        $form = $this->hidden_parameters($hidden_parameters);

                        $uri = explode('/', $_SERVER['REQUEST_URI']);
                        $root=(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'];
                        if($_SERVER['HTTP_HOST'] == 'localhost')
                        {
                            $img_code = $root."/".$uri[1]."/modules/global/resources/flights/airlines/".$flightdata->aircraft['carrier']['code'].".png";
                        } else {
                            $img_code = $root."/modules/global/resources/flights/airlines/".$flightdata->aircraft['carrier']['code'].".png";
                        }

                        $sub_array[] = (object)[
                            "id" => '',
                            "flight_id" => '',
                            'departure_flight_no' => '',
                            'img' => $img_code,
                            'departure_time' => $departure_time,
                            'departure_date' => date("d-m-Y", strtotime($departure_date)),
                            'arrival_date' => date("d-m-Y", strtotime($arrival_date)),
                            'arrival_time' => $arrival_time,
                            'departure_code' => $departure_code,
                            'departure_airport' => $departure_airport,
                            'arrival_code' => $arrival_code,
                            'arrival_airport' => $arrival_airport,
                            'duration_time' => $duration_time,
                            'currency_code' => $currency_code,
                            'adult_price' => $price,
                            'child_price' => '',
                            'infant_price' => '',
                            'price' => $price,
                            'url' => '',
                            'airline_name' =>$airline_name->name,
                            'class_type' => $class_type,
                            'luggage' => '',
                            'type' => '',
                            'form' => $form,
                            'form_name' => '',
                            'action' => '',
                            'supplier' => 'travelport',
                            "redirect" => '',
                            "desc" => '',
                            "booking_token" => '',
                            "refundable" => '',

                        ];
                    }
                    $return_array["segments"][] = $sub_array;

                    if(!empty(json_decode(json_encode($convert_array[$mainindex][$key]),true)['1']['flightItinerary']))
                    {
                        $dataflightreturn =  json_decode(json_encode($convert_array[$mainindex][$key]),true)['1']['flightItinerary'];

                        foreach ($dataflightreturn['segments']as $returnflightindex=>$return_items) {
                            if($returnflightindex != 0)
                            {
                                $return_array = [];
                                $return_array["segments"][] = $sub_array;
                            }else{

                            }
                            $return_sub_array = array();
                            foreach ($return_items as $returnidexflight => $returnflight) {
                                $returnflightdata = (object)$returnflight;
                                $price = $returnflightdata->fareDetails['totalPrice']['value'];
                                $currency_code = $returnflightdata->fareDetails['totalPrice']['unit'];
                                $duration_time = $returnflightdata->totalDuration['day'] . "d" . $returnflightdata->totalDuration['hour'] . "h" . $returnflightdata->totalDuration['minute'] . "m";
                                $departure_time = $returnflightdata->departureTime['time']['hour'] . ":" . $returnflightdata->departureTime['time']['minute'];
                                $arrival_time = $returnflightdata->arrivalTime['time']['hour'] . ":" . $returnflightdata->arrivalTime['time']['minute'];
                                $departure_date = $returnflightdata->departureTime['date']['day'] . "-" . $returnflightdata->departureTime['date']['month'] . "-" . $returnflightdata->departureTime['date']['year'];
                                $arrival_date = $returnflightdata->arrivalTime['date']['day'] . "-" . $returnflightdata->arrivalTime['date']['month'] . "-" . $returnflightdata->arrivalTime['date']['year'];
                                $class_type = $returnflightdata->bookingInformation['CabinClass'];
                                $airline_name = $this->tm->get_airline_name($flightdata->aircraft['carrier']['code']);
                                $departure_airport = $returnflightdata->origin['code'];
                                $arrival_airport = $returnflightdata->destination['code'];
                                $departure_code = $returnflightdata->origin['fullname'];
                                $arrival_code = $returnflightdata->destination['fullname'];


                                $uri = explode('/', $_SERVER['REQUEST_URI']);
                                $root=(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'];
                                if($_SERVER['HTTP_HOST'] == 'localhost')
                                {
                                    $img_code = $root."/".$uri[1]."/modules/global/resources/flights/airlines/".$flightdata->aircraft['carrier']['code'].".png";
                                } else {
                                    $img_code = $root."/modules/global/resources/flights/airlines/".$flightdata->aircraft['carrier']['code'].".png";
                                }
                                $return_sub_array[] = (object)[
                                    "id" => '',
                                    "flight_id" => '',
                                    'departure_flight_no' => '',
                                    'img' => $img_code,
                                    'departure_time' => $departure_time,
                                    'departure_date' => date("d-m-Y", strtotime($departure_date)),
                                    'arrival_date' => date("d-m-Y", strtotime($arrival_date)),
                                    'arrival_time' => $arrival_time,
                                    'departure_code' => $departure_code,
                                    'departure_airport' => $departure_airport,
                                    'arrival_code' => $arrival_code,
                                    'arrival_airport' => $arrival_airport,
                                    'duration_time' => $duration_time,
                                    'currency_code' => $currency_code,
                                    'adult_price' => $price,
                                    'child_price' => '',
                                    'infant_price' => '',
                                    'price' => $price,
                                    'url' => '',
                                    'airline_name' =>$airline_name->name,
                                    'class_type' => $class_type,
                                    'luggage' => '',
                                    'type' => '',
                                    'form' => $form,
                                    'form_name' => '',
                                    'action' => '',
                                    'supplier' => 'travelport',
                                    "redirect" => '',
                                    "desc" => '',
                                    "booking_token" => '',
                                    "refundable" => '',

                                ];

                            }
                            $return_array["segments"][] = $return_sub_array;


                        }
                    }
                }
            }
            $this->final_array[] = $return_array;
        }
       return $this->response($this->final_array,200);
    }
    ///Flight Segments Save Parameters
    public function hidden_parameters($hidden_parameters){

        $arr = array("outbound"=>$hidden_parameters->outbound,"inbound"=>$hidden_parameters->inbound);
        return $arr;
    }

    //Travelport Booking

    public function booking_post(){

        $payload = $this->input->post();
       // dd($payload['guest']);
        $response = $this->booking_response($payload);

        $this->session->set_userdata(array('travelportCheckoutResp' => $response));

        $passengerForm = array(
            "title"=>array("MR"),
            "firstname"=>array("John"),
            "lastname"=>array("Smith"),
            "phone"=>array("00123456789"),
            "nationality"=>array("United States"),
            "code"=>array("ADT"),
            "formsCount"=>0,
            "paymentOption"=>"no",
            "cardtype"=>"CA",
            "cardno"=>"5416144754363722",
            "expMonth"=>"12",
            "expYear"=>"2033",
            "security_code"=>"123",
            "cvv"=>"123",
            "email"=> array("usama@gmail.com"),
        );
        $response = $this->get_placeorder_response($passengerForm);
        dd($response);
    }
    ///Trvalport Booking Call Api SoapClient
    public function get_placeorder_response($passengerForm)
    {

        $notifiable_emails = array();
        $passengers = array();
        for($index = 0; $index <= $passengerForm['formsCount']; $index++)
        {
            $passenger = new StdClass();
            $passenger->title = $this->get_passenger($passengerForm['title'][$index]);
            $passenger->firstname = $this->get_passenger($passengerForm['firstname'][$index]);
            $passenger->lastname = $this->get_passenger($passengerForm['lastname'][$index]);
            $passenger->phone = $this->get_passenger($passengerForm['phone'][$index]);
            $passenger->email = $this->get_passenger($passengerForm['email'][$index]);
            $passenger->nationality = $this->get_passenger($passengerForm['nationality'][$index]);
            $passenger->code = $this->get_passenger($passengerForm['code'][$index]);

            $passengers[] = $passenger;
            $notifiable_emails[] = $passenger->email;
        }
        $variables = array('variable_name' => 'AirCreateReservationReq','type_name'=>'UNIVERSAL');
        $this->load->library('travelport/Reservation', $variables);
        $airservice = new Reservation($variables);
        $AirCreateReservationReq = $airservice;
        $parameters = $this->payload_placeorder($passengerForm, $passengers, $AirCreateReservationReq->branch_code);
        $response = $AirCreateReservationReq->service($parameters);
        dd( $response);
    }
    ///Trvalport Booking Get passenger data
    public function get_passenger($value)
    {
        if (is_array($value)) {
            return current($value);
        } else {
            return $value;
        }
    }

    ///Trvalport Booking Get Segments data
    private function payload_placeorder($passengerForm, $passengers, $branch_code)
    {
        $checkoutResp = $this->session->userdata('travelportCheckoutResp');

        $FormOfPaymentKey = $this->generateRandomString(5);
        $FormOfPaymentKey = 'FPK'.$FormOfPaymentKey;
        $PaymentKey = 'PK'.$FormOfPaymentKey;

        $parameters['AuthorizedBy'] = 'Travelport';
        $parameters['TargetBranch'] = $branch_code;
        $parameters['ProviderCode'] = '1G';
        $parameters['RetainReservation'] = 'Both';
        $parameters['BillingPointOfSaleInfo'] = array('OriginApplication' => 'UAPI');
        $BookingTraveler = $this->BookingTraveler($passengers);
        $parameters['BookingTraveler'] = $BookingTraveler;
        $parameters['FormOfPayment'] = array('Type' => 'Credit', 'Key' => $FormOfPaymentKey);
        $parameters['FormOfPayment']['CreditCard'] = array(
            'CVV' => $passengerForm['cvv'],
            'Number' => $passengerForm['cardno'],
            'Type' => $passengerForm['cardtype'],
            'ExpDate' => sprintf('%s-%s', $passengerForm['expYear'], $passengerForm['expMonth'])
        );

        $AirPricingSolution = $this->get_AirPricingSolution($BookingTraveler);
        $AirPricingSolution->AirSegment = array();
        if ( ! empty($checkoutResp->outbound->segment) )
        {
            foreach($checkoutResp->outbound->segment as $segment)
            {
                $placeorderSegment = clone $segment;
                unset($placeorderSegment->detail);
                $AirPricingSolution->AirSegment[] = $placeorderSegment;
            }
        }

        if ( ! empty($checkoutResp->inbound->segment) )
        {
            foreach($checkoutResp->inbound->segment as $segment)
            {
                $placeorderSegment = clone $segment;
                unset($placeorderSegment->detail);
                $AirPricingSolution->AirSegment[] = $placeorderSegment;
            }
        }
        $parameters['AirPricingSolution'] = $AirPricingSolution;
        $parameters['ActionStatus'] = array('Type' => 'ACTIVE', 'TicketDate' => 'T*', 'ProviderCode' => '1G');
        // Payment information - must be used in conjunction with credit card info
        $parameters['Payment'] = array('Key' => $PaymentKey, 'Type' => 'Itinerary', 'FormOfPaymentRef' => $FormOfPaymentKey, 'Amount' => $AirPricingSolution->TotalPrice);

        return $parameters;
    }


    private function BookingTraveler($passengers)
    {
        $BookingTraveler = array();
        $DeliveryAddressFlag = TRUE;
        foreach($passengers as $index => $passenger)
        {
            $key = $index;
            $key .= $this->generateRandomString(7);

            $traveler = new StdClass();
            $traveler->Key = $key;
            $traveler->TravelerType = $passenger->code;
            // Infant date of birth is required
            $year = (date('Y') - 5);
            if ($passenger->code == 'INF') {
                $year = (date('Y') - 1);
            }
            $traveler->DOB = sprintf('%s-%s-%s', $year, date('m'), date('d'));
            // $traveler->Nationality = $passenger->nationality;
            $traveler->BookingTravelerName = (Object) array(
                'Prefix' => $passenger->title,
                'First' => $passenger->firstname,
                'Last' => $passenger->lastname,
            );

            // Host only allows one Address/Delivery Address. Only one sent in request.
            if ($DeliveryAddressFlag)
            {
                $traveler->DeliveryInfo = new StdClass();
                $traveler->DeliveryInfo->ShippingAddress = new StdClass();
                $traveler->DeliveryInfo->ShippingAddress->Key = $key;
                // $traveler->DeliveryInfo->ShippingAddress->Street = new StdClass();
                // $traveler->DeliveryInfo->ShippingAddress->Street = "Street 4, HH Block DHA";
                // $traveler->DeliveryInfo->ShippingAddress->City = "Lahore";
                // $traveler->DeliveryInfo->ShippingAddress->PostalCode = "54810";
                // $traveler->DeliveryInfo->ShippingAddress->Country = "PK";
                $DeliveryAddressFlag = FALSE;
            }

            $traveler->PhoneNumber = new StdClass();
            // $traveler->PhoneNumber->Location = "LHE";
            // $traveler->PhoneNumber->CountryCode = "+92";
            // $traveler->PhoneNumber->AreaCode = "42";
            $traveler->PhoneNumber->Number = $passenger->phone;
            $traveler->Email = new StdClass();
            $traveler->Email->EmailID = $passenger->email;
            // $traveler->SSR = new StdClass();
            // $traveler->SSR->Type = "DOCS";
            // href: https://support.travelport.com/webhelp/uapi/Content/Air/Shared_Air_Topics/SSRs_(Special_Service_Requests).htm
            // $traveler->SSR->FreeText = "P/PK/S12345678/PK/01FEB91/M/01JAN21/{$passenger->lastname}/{$passenger->firstname}";
            // $traveler->SSR->Carrier = "QR";

            array_push($BookingTraveler, $traveler);
        }

        return $BookingTraveler;
    }

    private function get_AirPricingSolution($BookingTraveler)
    {
        $passengerTypeArray = array();
        foreach($BookingTraveler as $BookingTravelerObj)
        {
            if( ! array_key_exists($BookingTravelerObj->TravelerType, $passengerTypeArray) ) {
                $passengerTypeArray[$BookingTravelerObj->TravelerType] = array();
            }

            $passengerTypeArray[$BookingTravelerObj->TravelerType][] = (object)array(
                'Code' => $BookingTravelerObj->TravelerType,
                'BookingTravelerRef' => $BookingTravelerObj->Key,
            );
        }

        $travelportCartResp = $this->session->userdata('travelportCartResp');
        if (empty($travelportCartResp)) {
            throw new Exception("Response cache has been cleared, search again this trip.");
        }

        $AirPricingSolution = new StdClass();
        $AirPricingSolution->Key = $travelportCartResp->AirPriceResult->AirPricingSolution->Key;
        $AirPricingSolution->TotalPrice = $travelportCartResp->AirPriceResult->AirPricingSolution->TotalPrice;
        $AirPricingSolution->BasePrice = $travelportCartResp->AirPriceResult->AirPricingSolution->BasePrice;
        $AirPricingSolution->ApproximateTotalPrice = $travelportCartResp->AirPriceResult->AirPricingSolution->ApproximateTotalPrice;
        $AirPricingSolution->ApproximateBasePrice = $travelportCartResp->AirPriceResult->AirPricingSolution->ApproximateBasePrice;
        $AirPricingSolution->EquivalentBasePrice = @$travelportCartResp->AirPriceResult->AirPricingSolution->EquivalentBasePrice; // Missing in some search response
        $AirPricingSolution->Taxes = $travelportCartResp->AirPriceResult->AirPricingSolution->Taxes;
        $AirPricingSolution->Fees = $travelportCartResp->AirPriceResult->AirPricingSolution->Fees;
        $AirPricingSolution->AirPricingInfo = array();

        /*
         * Check Array Or Object
         *
         * Problem:
         * I am getting a warning like 3100 : INVALID INPUT and Some of the requested AirPricingInfos
         * could not be saved for the requested provider.
         *
         * Solution:
         * We have removed the PlatingCarrier from the request.
         * Please Remove the PlatingCarrier attribute from the AirPricingInfo and give it a try.
         * href: https://github.com/Travelport/travelport-uapi-tutorial/issues/232
         * href: https://github.com/Travelport/travelport-uapi-tutorial-php/issues/70
         */

        $aAirPricingInfo = $travelportCartResp->AirPriceResult->AirPricingSolution->AirPricingInfo;
        if(is_object($aAirPricingInfo))
        {
            // Deep Copy of object
            $clone_AirPricingInfo = unserialize(serialize($aAirPricingInfo));
            $passengerType = is_object($aAirPricingInfo->PassengerType) ? $aAirPricingInfo->PassengerType : current($aAirPricingInfo->PassengerType);
            $clone_AirPricingInfo->PassengerType = $passengerTypeArray[$passengerType->Code];
            unset($clone_AirPricingInfo->PlatingCarrier); // PlatingCarrier Deprecated in request
            $AirPricingSolution->AirPricingInfo = $clone_AirPricingInfo;
        }
        else
        {
            foreach($aAirPricingInfo as $AirPricingInfo)
            {
                // Deep Copy of object
                $clone_AirPricingInfo = unserialize(serialize($AirPricingInfo));
                $passengerType = is_object($AirPricingInfo->PassengerType) ? $AirPricingInfo->PassengerType : current($AirPricingInfo->PassengerType);
                $clone_AirPricingInfo->PassengerType = $passengerTypeArray[$passengerType->Code];
                unset($clone_AirPricingInfo->PlatingCarrier); // PlatingCarrier Deprecated in request
                $AirPricingSolution->AirPricingInfo[] = $clone_AirPricingInfo;
            }
        }

        $AirPricingSolution->HostToken = $travelportCartResp->AirPriceResult->AirPricingSolution->HostToken;

        return $AirPricingSolution;
    }



    // public function booking_post(){
//        $response = $this->booking_response($payload);
//        $this->session->set_userdata(array('travelportCheckoutResp' => $response));
//        $summary = new StdClass();
//        $outbound_segment_first = is_object($response->outbound->segment) ? $response->outbound->segment : current($response->outbound->segment);
//        $outbound_segment_last = is_object($response->outbound->segment) ? $response->outbound->segment : end($response->outbound->segment);
//
//        $summary->triptype = (empty($inbound)) ? "oneway" : "round";
//
//        $referenceData = new ReferenceData();
//        $segmentDetail = new StdClass();
//        $carrier = (Object) $referenceData->airline_carrier($outbound_segment_first->Carrier);
//        $carrier->image_path = sprintf($this->AIRLINE_CARRIER_LOGO, $outbound_segment_first->Carrier);
//        $equipment = (Object) $referenceData->airline_equipment($outbound_segment_first->Equipment);
//
//        $summary->carrier = $carrier;
//        $summary->equipment = $equipment;
//        $summary->outbound = (Object) array(
//            'Carrier' => $outbound_segment_first->Carrier,
//            'Origin' => $outbound_segment_first->Origin,
//            'Destination' => $outbound_segment_last->Destination,
//            'DepartureTime' => $this->parse_datetime($outbound_segment_first->DepartureTime),
//            'ArrivalTime' => $this->parse_datetime($outbound_segment_last->ArrivalTime),
//        );
//        if ($summary->triptype == 'round') {
//            $inbound_segment_first = is_object($response->inbound->segment) ? $response->inbound->segment : current($response->inbound->segment);
//            $inbound_segment_last = is_object($response->inbound->segment) ? $response->inbound->segment : end($response->inbound->segment);
//            $summary->inbound = (Object) array(
//                'Carrier' => $inbound_segment_first->Carrier,
//                'Origin' => $inbound_segment_first->Origin,
//                'Destination' => $inbound_segment_last->Destination,
//                'DepartureTime' => $this->parse_datetime($inbound_segment_first->DepartureTime),
//                'ArrivalTime' => $this->parse_datetime($inbound_segment_last->ArrivalTime),
//            );
//        }
//
//        $response->summary = $summary;
//        $response->searchPassenger = (Object) $this->session->userdata('SearchPassenger');
//        $this->data['dataAdapter'] = $response;
//        dd($this->data['dataAdapter']);
 //   }

    private function parse_datetime($timezone_stamp)
    {
        $dateTimeObj = new DateTime($timezone_stamp);
        return $dateTimeObj->format('l jS F Y \a\t g:ia');
    }

    public function booking_response($payload)
    {

        // Load travelport model and populate search form with default values
        $this->load->model('TravelportModel_Conf');
        $this->travelportConfiguration = new TravelportModel_Conf();
        $this->travelportConfiguration->load();

        $currencey_code = 'USD';

        $response = $this->session->userdata('travelportResp');

        $this->aFareInfo = $response->FareInfoList->FareInfo;

        $variables = array('variable_name' => 'AirPriceReq');
        $this->load->library('travelport/Airprice', $variables);
        $airprice = new Airprice($variables);

        $AirpriceReq = $airprice;
        $outbound = $payload['outbound'];
        $inbound = $payload['inbound'];
        $segment_key = array_merge(explode(',', $outbound), explode(',', $inbound));
        $parameters['AuthorizedBy'] = 'Travelport';
        $parameters['TargetBranch'] = $AirpriceReq->branch_code;
        $parameters['BillingPointOfSaleInfo'] = array('OriginApplication' => 'UAPI');
        $parameters['AirItinerary']['AirSegment'] = array();
        $parameters['AirPricingModifiers'] = array('CurrencyType' => $currencey_code);

        foreach(castToArray($response->AirSegmentList->AirSegment) as $segment)
        {
            // Clone segment object, so the orignal object being protected from amendments.
            $segment_temp = unserialize(serialize($segment));
            if (in_array($segment->Key, $segment_key))
            {
                $segment_temp->ProviderCode = $segment->AirAvailInfo->ProviderCode;
                unset($segment_temp->FlightDetailsRef);
                unset($segment_temp->AirAvailInfo);
                $parameters['AirItinerary']['AirSegment'][] = $segment_temp;
            }
        }

        $passengers = $this->session->userdata('SearchPassenger');
        $parameters['SearchPassenger'] = array();
        foreach ($passengers as $index => $passenger)
        {
            $unique_key = $this->generateRandomString(5);
            $unique_key = 'PT'.$index.$unique_key;
            $parameters['SearchPassenger'][] = array(
                'BookingTravelerRef' => $unique_key,
                'Key' => $unique_key,
                'Code' => $passenger['Code']
            );
        }

        $parameters['AirPricingCommand']['AirSegmentPricingModifiers'] = array();
        $response_AirPricePointList_AirPricePoint = is_object($response->AirPricePointList->AirPricePoint) ? array($response->AirPricePointList->AirPricePoint) : $response->AirPricePointList->AirPricePoint;
        $duplicateEntry = array(); // Prevent Duplicate entries
        foreach($response_AirPricePointList_AirPricePoint as $AirPricePoint)
        {
            $AirPricePoint_AirPricingInfo = is_object($AirPricePoint->AirPricingInfo) ? array($AirPricePoint->AirPricingInfo) : $AirPricePoint->AirPricingInfo;
            foreach($AirPricePoint_AirPricingInfo as $AirPricingInfo)
            {
                $FlightOptions = $AirPricingInfo->FlightOptionsList->FlightOption;
                $FlightOptions = is_object($FlightOptions) ? array($FlightOptions) : $FlightOptions;
                foreach($FlightOptions as $FlightOption)
                {
                    $aOption = is_object($FlightOption->Option) ? array($FlightOption->Option) : $FlightOption->Option;
                    foreach($aOption as $Option)
                    {
                        $Option_BookingInfo = is_object($Option->BookingInfo) ? array($Option->BookingInfo) : $Option->BookingInfo;
                        foreach($Option_BookingInfo as $BookingInfo)
                        {
                            if (in_array($BookingInfo->SegmentRef, $segment_key) && ! in_array($BookingInfo->SegmentRef, $duplicateEntry))
                            {
                                $FareInfo = $this->fareInfoList($BookingInfo->FareInfoRef);
                                $parameters['AirPricingCommand']['AirSegmentPricingModifiers'][] = array(
                                    'AirSegmentRef' => $BookingInfo->SegmentRef,
                                    'FareBasisCode' => $FareInfo->FareBasis,
                                    'PermittedBookingCodes' => array('BookingCode' => array('Code' => $BookingInfo->BookingCode))
                                );

                                $duplicateEntry[] = $BookingInfo->SegmentRef;
                            }
                        }
                    }
                }
            }
        }

        // Payment type Credit, Check
        $parameters['FormOfPayment'] = array('Type' => 'Credit');

        return $AirpriceReq->service($parameters);
    }

    private function fareInfoList($fareInfoRef)
    {
        $result = array_filter($this->aFareInfo, function($fareInfo) use ($fareInfoRef) {
            if ($fareInfo->Key == $fareInfoRef) {
                return TRUE;
            }
        });

        return (Object) current($result);
    }

    public function generateRandomString($length = 10)
    {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}


