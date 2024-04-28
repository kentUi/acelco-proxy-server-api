<?php 

class Billing extends CI_Model{
    
    public function forwardBilling($data){

        // Construct POST body
        
        $pData = [
            [
                'account_number' => $data['account_number'],
                'amount' => intval($data['amount']),
                'due_date' => $data['due_date'],
                'period' => $data['period']
            ]
        ];
        
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
            
            return json_encode($data);
        }
        
        //curl_close($ch);
    }
    
    public function saveBilling($data){
        
        $date = DateTime::createFromFormat('d-m-Y', $data['due_date']);

        if ($date !== false) {
            $formattedDate = $date->format('Y-m-d');
        }

        $data_sql = array(
            'bill_account_number ' => $data['account_number'],
            'bill_amount' => $data['amount'],
            'bill_due_date' => $formattedDate,
            'bill_period' => $data['period']
        );
        
        $check = $this->db->where($data_sql);
        $check = $this->db->get('t_billing');
        
        if($check->num_rows() == 0){
             $this->db->insert('t_billing', $data_sql);
        }
    }
    
}