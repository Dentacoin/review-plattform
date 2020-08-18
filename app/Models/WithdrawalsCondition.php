<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalsCondition extends Model {
    
    protected $fillable = [
        "min_amount",
        "min_trp_amount",
        "timerange",
    ];
    
    protected $dates = [
        'created_at', 
        'updated_at',
    ];

}