<?php

require __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AccessTokenGenerator
{
    public static function generateToken()
    {
        // Secret key = @selco-d3vel0pment-k3y6en (SHA256)
        // URL = https://emn178.github.io/online-tools/sha256.html
        // Input Type = UTF-8

        $secret_key = "a71d14fe67c98c4ed4f47868792dee0f65ba1e4a98e46de6d6ea2be4d070830e";
        $nonce = bin2hex(openssl_random_pseudo_bytes(255));

        $payload = array(
            "user_id" => 123456,
            "username" => "example_user",
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

            return json_encode(array("response" => "Data received successfully", "users" => $decoded, "data" => $data));
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
        $access_token = AccessTokenGenerator::generateToken();
        return $access_token;
    }

    public function receiver()
    {
        $receiver = DataReceiver::receiveData();

        $response = json_decode($receiver, true);
        $client = $response['users'];
        $data = $response['data'];
        
        echo $client['username'];
        echo $response['response'];
        echo json_encode($data);
    }
}
