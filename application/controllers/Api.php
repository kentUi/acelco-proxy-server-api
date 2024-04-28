<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public $Billing;
    public $Payments;

    public function index()
    {
        header("Content-type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $accessToken = '';

            $account_number = filter_var($data->account_number, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $amount = filter_var($data->amount, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $due_date = filter_var($data->due_date, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $period = filter_var($data->period, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            $client_id = "2e869a44bf017f57";
            $client_secret = "06a07d98da022235935b9cbd4646a111dc831a3622bbe983edde8b452369bd7b";

            // Construct POST body
            $postData = http_build_query([
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'client_credentials'
            ]);

            // Set URL and headers for token request
            $tokenUrl = "https://authstage.nexitydev.com/oauth2/token/";
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
            ];

            // Initialize cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute cURL request
            $response = curl_exec($ch);

            // Check for errors
            if (curl_errno($ch)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch access token']);
            } else {
                // Decode JSON response
                $data = json_decode($response, true);

                // Check if response is valid
                if (isset($data['access_token'])) {
                    $accessToken = $data['access_token'];
                } else {
                    http_response_code(500);
                }
            }

            // Close cURL session
            curl_close($ch);

            $billing = array(
                'account_number' => $account_number,
                'amount' => $amount,
                'due_date' => $due_date,
                'period' => $period,
                'token' => $accessToken
            );

            $this->load->model('Billing');
            $forward = $this->Billing->forwardBilling($billing);

            echo json_encode(['billing' => $forward, 'access_token' => $accessToken, 'details' => $billing]);
        }
    }

    public function login()
    {
        header("Content-type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");

        $this->load->model('Payments');
        $receiver = $this->Payments->login();

        echo json_encode(['access_token' => $receiver]);
    }

    public function payments()
    {
        header("Content-type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");

        $this->load->model('Payments');
        $response = $this->Payments->receiver();

        return $response;
    }
}
