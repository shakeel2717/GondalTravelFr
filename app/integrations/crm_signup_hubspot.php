<?php

include "app/integrations/config.php";

if (!empty($hubspot_key)) {

if (isset($_POST['email'])) { $email = $_POST['email']; } else { $email = ""; }
if (isset($_POST['first_name'])) { $first_name = $_POST['first_name']; } else { $first_name = ""; }
if (isset($_POST['last_name'])) { $last_name = $_POST['last_name']; } else { $last_name = ""; }
if (isset($_POST['phone'])) { $phone = $_POST['phone']; } else { $phone = ""; }

    // CRM DATA
    $arr = array(
        'properties' => array(
            array(
                'property' => 'email',
                'value' => $email
            ),
            array(
                'property' => 'firstname',
                'value' => $first_name
            ),
            array(
                'property' => 'lastname',
                'value' => $last_name
            ),
            array(
                'property' => 'phone',
                'value' => $phone
            ),
            array(
                'property' => 'company',
                'value' => ''
            ),
            array(
                'property' => 'website',
                'value' => ''
            )
        )
    );

    $post_json = json_encode($arr);
    $endpoint = 'https://api.hubapi.com/contacts/v1/contact/?hapikey='.$hubspot_key;
    $ch = @curl_init();
    @curl_setopt($ch, CURLOPT_POST, true);
    @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
    @curl_setopt($ch, CURLOPT_URL, $endpoint);
    @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = @curl_exec($ch);
    $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errors = curl_error($ch);
    @curl_close($ch);
    // echo "curl Errors: " . $curl_errors;
    // echo "\nStatus code: " . $status_code;
    //  echo "\nResponse: " . $response;
    //  die;
}