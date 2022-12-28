<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PaymentLog;
use App\Order;


class WebhookController extends Controller
{
    // membuat midtrans handler

    public function midtransHandler(Request $request)
    {
        // get data from body yang midtrans kirim
        $data = $request->all();

        // get data signature key
        $signatureKey = $data['signature_key'];

        // get data order id
        $orderId = $data['order_id'];

         // get data status code
         $statusCode = $data['status_code'];

         // get data gross amount
         $grossAmount = $data['gross_amount'];

        //  get data server key (.env)
        $serverKey = env('MIDTRANS_SERVER_KEY');


        // cek signature key nya bener atau engga
        $mySignatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        // get data from body for check traansaction status, payment type dll

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        // check signature key valid or not
        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'invalid signature'
            ], 400);
        }

        // 9-qwe12z misahin data ini dengan order id
        $realOrderId = explode('-', $orderId);
        // check order id ada atau engga
        $order = Order::find($realOrderId[0]);

        // cek data nya ada ga di database
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'order id not found'
            ], 404);
        }

        // pengecekan ke 2
        if ($order->status === 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'opration not premitted'
            ], 405);
        }

        // cek status transaksi kalo sukses kasih , kalo engga ya ga dikasih

        // Sample transactionStatus handling logic
 
        if ($transactionStatus == 'capture'){
            if ($fraudStatus == 'challenge'){
                // TODO set transaction status on your database to 'challenge'
                // and response with 200 OK
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept'){
                // TODO set transaction status on your database to 'success'
                // and response with 200 OK
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement'){
            // TODO set transaction status on your database to 'success'
            // and response with 200 OK
            $order->status = 'success';
        } else if ($transactionStatus == 'cancel' ||
          $transactionStatus == 'deny' ||
          $transactionStatus == 'expire'){
          // TODO set transaction status on your database to 'failure'
          // and response with 200 OK
          $order->status = 'failure';
        } else if ($transactionStatus == 'pending'){
          // TODO set transaction status on your database to 'pending' / waiting payment
          // and response with 200 OK
          $order->status = 'pending';
        }
    
        // pas udah set status di database nya sesuai dengan transactionn status, lalu disave ke database

        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId[0],
            'payment_type' => $type
        ];

        // simpen data di table payment log
        PaymentLog::create($logData);

        // data order disave
        $order->save();

        // ketika semua berhasil dikasih akses kelas premium ke orang yang bayar
        if ($order->status === 'success') {
            createPremiumAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id
            ]);
        }

        return response()->json('Ok');

    }
}
