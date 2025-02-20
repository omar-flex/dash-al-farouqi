<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterRequestStatus extends Model
{
    public $timestamps = false;
    const DRAFT = 1;
    const CAR_CHECK = 2;
    const WH_ENTER_PRODUCT = 3;
    const AUTHORIZATION = 4;
}
