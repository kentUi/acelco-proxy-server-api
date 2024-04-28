<?php

class Billing extends CI_Model
{

    public function getBilling()
    {
        $query = $this->db->select('tbl_billing.*')
            ->from('tbl_billing')
            ->join('tbl_billing_copy', 'tbl_billing.AccountNumber = tbl_billing_copy.bill_account_number', 'left')
            ->where('tbl_billing_copy.bill_account_number IS NULL')
            ->or_where('tbl_billing_copy.bill_amount != tbl_billing.NetAmount')
            ->get();

        return $query->result();
    }

    public function forwardBilling($data)
    {
        $check = $this->db->where('bill_status', 0)->get('tbl_billing_copy');

        if ($check->num_rows() > 0) {
            $pData = array();

            foreach ($check->result_array() as $row) {
                $pData[] = array(
                    'account_number' => $row['bill_account_number'],
                    'amount' => intval($row['bill_amount']),
                    'due_date' => date_format(date_create($row['bill_due_date']), 'd-m-Y'),
                    'period' => $row['bill_period']
                );
            }

            $postData = json_encode($pData);

            $tokenUrl = 'https://utility-stage-e6jg27ou7a-as.a.run.app/bills';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $data['token'],
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
                return json_encode(['error' => 'Failed to fetch access token']);
            } else {

                $data = json_decode($response, true);                

                return json_encode($pData);
            }
            
        } else {
            echo "No data found..";
        }
        
    }

    public function saveBilling($data)
    {

        $formattedDate = date('Y-m-d', strtotime($data['bill_due_date']));

        $data_sql = array(
            'bill_account_number ' => $data['bill_account_number'],
            'bill_amount' => $data['bill_amount'],
            'bill_due_date' => $formattedDate,
            'bill_period' => $data['bill_period']
        );

        $check = $this->db->where($data_sql);
        $check = $this->db->get('tbl_billing_copy');

        if ($check->num_rows() == 0) {
            $this->db->insert('tbl_billing_copy', $data_sql);
        }
    }
    
    // <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;"> <h4>A PHP Error was encountered</h4> <p>Severity: Warning</p> <p>Message: Undefined property: stdClass::$account_number</p> <p>Filename: controllers/Api.php</p> <p>Line Number: 40</p> <p>Backtrace:</p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\application\controllers\Api.php<br /> Line: 40<br /> Function: _error_handler </p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\index.php<br /> Line: 315<br /> Function: require_once </p> </div> <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;"> <h4>A PHP Error was encountered</h4> <p>Severity: Warning</p> <p>Message: Undefined property: stdClass::$amount</p> <p>Filename: controllers/Api.php</p> <p>Line Number: 41</p> <p>Backtrace:</p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\application\controllers\Api.php<br /> Line: 41<br /> Function: _error_handler </p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\index.php<br /> Line: 315<br /> Function: require_once </p> </div> <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;"> <h4>A PHP Error was encountered</h4> <p>Severity: Warning</p> <p>Message: Undefined property: stdClass::$due_date</p> <p>Filename: controllers/Api.php</p> <p>Line Number: 42</p> <p>Backtrace:</p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\application\controllers\Api.php<br /> Line: 42<br /> Function: _error_handler </p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\index.php<br /> Line: 315<br /> Function: require_once </p> </div> <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;"> <h4>A PHP Error was encountered</h4> <p>Severity: Warning</p> <p>Message: Undefined property: stdClass::$period</p> <p>Filename: controllers/Api.php</p> <p>Line Number: 43</p> <p>Backtrace:</p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\application\controllers\Api.php<br /> Line: 43<br /> Function: _error_handler </p> <p style="margin-left:10px"> File: D:\Software Development\xampp\htdocs\acelco-proxy-server-api\index.php<br /> Line: 315<br /> Function: require_once </p> </div>{"billing":"[]","access_token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjczMTdhNmZlLTAxMTAtNDI0Mi04MmMyLTE1ZjlkZjk3OGJkNiIsImF1ZCI6ImFjY2VzcyIsImV4cCI6MTcxNDMwNDEwMSwianRpIjoiOTc3M2YxZGItNTE4ZS00YTg3LWI5YzctNjRkZjVlYzU2OGZhIiwiaXNzIjoiQmxhZ2dvIC0gU3RhZ2luZyJ9.f296z1G7KR2EZImr1CTtxGd7BtKUYt3FdG4Hs5-8JAs","details":{"account_number":"","amount":"","due_date":"","period":"","token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjczMTdhNmZlLTAxMTAtNDI0Mi04MmMyLTE1ZjlkZjk3OGJkNiIsImF1ZCI6ImFjY2VzcyIsImV4cCI6MTcxNDMwNDEwMSwianRpIjoiOTc3M2YxZGItNTE4ZS00YTg3LWI5YzctNjRkZjVlYzU2OGZhIiwiaXNzIjoiQmxhZ2dvIC0gU3RhZ2luZyJ9.f296z1G7KR2EZImr1CTtxGd7BtKUYt3FdG4Hs5-8JAs"}}
}
