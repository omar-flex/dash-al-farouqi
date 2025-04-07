<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundStatus extends Model
{
    public $guarded = [];

    public $timestamps = false;
    const DRAFT = 1;
    const CAR_CHECK = 2;
    const WH_EXTRACT_PRODUCT = 3;
    const VALIDATION = 4;
    const AUTHORIZATION = 5;
}
