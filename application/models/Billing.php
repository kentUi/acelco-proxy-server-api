<?php

class Billing extends CI_Model
{

    public function getBilling()
    {
        $query = $this->db->select('tbl_billing.*')
            ->from('tbl_billing')
            ->where('isForwarded', 0)
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

        $id = $data['bill_account_number'];

        $check = $this->db->where($data_sql);
        $check = $this->db->get('tbl_billing_copy');

        if ($check->num_rows() == 0) {
            $this->db->insert('tbl_billing_copy', $data_sql);

            $rs = $this->db->where('AccountNumber', $id);
            $rs = $this->db->update('tbl_billing', [
                'isForwarded' => 1
            ]);

            return $rs;
        }
    }
}
