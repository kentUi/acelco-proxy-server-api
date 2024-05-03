<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public $db;
    public $Billing;
    public $Payments;
    public function __construct()
    {
        parent::__construct();
        header("Content-type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");
    }

    public function index()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $this->load->model('Billing');

            $accessToken = '';

            $billingData = isset($data->billing) ? $data->billing : null;

            foreach ($billingData as $item) {
                $copy = [
                    'bill_account_number' => $item->AccountNumber,
                    'bill_amount' => $item->NetAmount,
                    'bill_due_date' => $item->DueDate,
                    'bill_period' => $item->ServicePeriod
                ];

                $this->Billing->saveBilling($copy);
            }

            $client_id = "2e869a44bf017f57";
            $client_secret = "06a07d98da022235935b9cbd4646a111dc831a3622bbe983edde8b452369bd7b";

            $postData = http_build_query([
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'client_credentials'
            ]);

            $tokenUrl = "https://authstage.nexitydev.com/oauth2/token/";
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch access token']);
            } else {
                $data = json_decode($response, true);

                if (isset($data['access_token'])) {
                    $accessToken = $data['access_token'];
                } else {
                    http_response_code(500);
                }
            }

            curl_close($ch);

            $billing = array(
                'token' => $accessToken
            );

            $this->load->model('Billing');
            $forward = $this->Billing->forwardBilling($billing);

            $this->db->where('bill_status', 0)->update('tbl_billing_copy', ['bill_status' => 1]);

            echo json_encode(['response' => $forward]);
        }
    }

    public function login()
    {
        $this->load->model('Payments');
        $receiver = $this->Payments->login();

        echo json_encode($receiver);
    }

    public function payments()
    {
        $this->load->model('Payments');
        $response = $this->Payments->receiver();

        return $response;
    }

    public function client_key_registration()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $key = $this->db->insert('tbl_credentials', ['ss_client_key ' => $data->key, 'ss_name' => $data->name]);
            echo json_encode($key);
            
        }
    }

    public function keys()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $keys = $this->db->get('tbl_credentials')->result();
            echo json_encode($keys);
        }
    }

    public function billing()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $this->load->model('Billing');
            $forward = $this->Billing->getBilling();

            echo json_encode($forward);
        }
    }

    public function figures()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $pending = $this->db->where('isForwarded', 0)->get('tbl_billing')->num_rows();
            $overall = $this->db->get('tbl_billing')->num_rows();
            $incomming = $this->db->get('tbl_incoming_bills')->num_rows();

            echo json_encode(['pending' => ($overall - $pending), 'overall' => $overall, 'incoming' => $incomming]);
        }
    }


    public function incoming()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $incoming = $this->db->get('tbl_incoming_bills')->result();
            echo json_encode($incoming);

        }
    }

    public function copy()
    {

        $data = json_decode(file_get_contents('php://input'));
        if ($data) {

            $incoming = $this->db->get('tbl_billing_copy')->result();
            echo json_encode($incoming);
            
        }
    }
}
