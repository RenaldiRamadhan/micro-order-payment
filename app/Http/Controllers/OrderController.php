<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    // get list order 

    public function index(Request $request)
    {
        // filter by url
        $orders = Order::query();
        $userId = $request->query('user_id');

        // get filter from database by user id
        $orders->when($userId, function($query) use ($userId) {
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders->get()
        ]);
    }


    //create api chapter
    public function create (Request $request)
    {
    
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);

        // set transaction details midtransParams
        $transactionDetails = [
        'order_id' => $order->id.'-'.Str::random(5),
        'gross_amount' =>$course['price'],
        ];

        // set item details midtransParams
        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => 'ProgrammerAmatir',
                'category' => 'Online Course'
            ]
        ];

        // set customer details midtransParams

        $customerDetails = [
            'first_name' => $user['name'],
            'email' => $user['email']
        ];
        // request snap url midtrans
        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails
        ];


        $midtranSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        // save snap url to database

        $order->snap_url = $midtranSnapUrl;

        $order->metadata = [
            'course_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level']
        ];

        // mentimpan data yang diupdate
        $order->save();

        // update data ke frontend
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);

    }

    private function getMidtransSnapUrl($params)
    {
        // kalo mau pake env set dulu variable requirement nya
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        $snapUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;

        return $snapUrl;
    }
}
