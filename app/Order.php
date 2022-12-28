<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'status', 'user_id', 'course_id', 'metadata', 'snap_url'
    ];

   
   protected function serializeDate(DateTimeInterface $date)
   {
       return $date->format('Y-m-d H:i:s');
   }

   protected $casts = [
    'metadata' => 'array'
];
}
