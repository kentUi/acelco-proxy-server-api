<?php

require __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AccessTokenGenerator
{
    public static function generateToken($whoIS)
    {
        // Secret key = @selco-d3vel0pment-k3y6en (SHA256)
        // URL = https://emn178.github.io/online-tools/sha256.html
        // Input Type = UTF-8

        $secret_key = "a71d14fe67c98c4ed4f47868792dee0f65ba1e4a98e46de6d6ea2be4d070830e";
        $nonce = bin2hex(openssl_random_pseudo_bytes(255));

        $payload = array(
            "source" => $whoIS,
            "nonce" => $nonce,
            "exp" => time() + (60 * 60) // Token expiration time (1 hour from now),
        );

        $token = JWT::encode($payload, $secret_key, 'HS512');

        return $token;
    }
}

class DataReceiver
{
    public static function receiveData()
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            echo json_encode(array("message" => "Authorization header is missing"));
            return;
        }

        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        $token = str_replace('Bearer ', '', $auth_header);

        $secret_key = "a71d14fe67c98c4ed4f47868792dee0f65ba1e4a98e46de6d6ea2be4d070830e";

        try {
            $decoded = JWT::decode($token, new Key($secret_key, 'HS512'));

            $data = json_decode(file_get_contents('php://input'), true);

            $user = json_encode($decoded);
            $source = json_decode($user, true);

            $incomming_payments = array(
                'Source' => $source['source'],
                'ReferenceNo' => $data['reference-number'],
                'AccountNumber' => $data['account-number'],
                'ServicePeriod' => $data['service-period'],
                'TransAmount' => $data['amount-transaction'],
                'TransDate' => date_format(date_create($data['date-transaction']), 'Y-m-d'),
                'TransTime' => date_format(date_create($data['date-transaction']), 'h:i A'),
                'DateReported' => $data['date-reported'],
                'CustomerNote' => $data['customer-note'],
                'Status' => $data['status']
            );
            
            return json_encode(array("response" => "Data received successfully", "users" => $decoded, "data" => $data, 'insert' => $incomming_payments ));
        } catch (Exception $e) {

            http_response_code(401);
            return json_encode(array("response" => "Unauthorized: " . $e->getMessage()));
        }
    }
}

class Payments extends CI_Model
{

    public function login()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $validate = $this->db->where('ss_client_key', $data->client_key);
            $validate = $this->db->get('tbl_credentials');

            if ($validate->num_rows() == 1) {
                $access_token = AccessTokenGenerator::generateToken($validate->row()->ss_name);
                return ['name' => $validate->row()->ss_name, 'access_token' => $access_token, 'status' => 200];
            } else {
                http_response_code(401);
                return ['Unauthorized' => 'Invalid Client Key. Please try again. Thank you.', 'status' => 401];
            }
        }
    }

    public function receiver()
    {
        error_reporting(0);

        $data_payment = json_decode(file_get_contents('php://input'));
        if ($data_payment) {
            $receiver = DataReceiver::receiveData();

            $response = json_decode($receiver, true);

            $client = $response['users'];
            $data = $response['data'];

            if($client['source'] != null || !empty($client['source'])){
                $this->db->insert('tbl_incoming_bills', $response['insert']);
            }

            echo json_encode(['source' => $client['source'], 'data' => $data]);
        }
    }
}
