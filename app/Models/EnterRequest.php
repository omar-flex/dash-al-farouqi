<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterRequest extends Model
{
    protected $guarded = [];

    public function Customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }

    public function Status()
    {
        return $this->belongsTo(EnterRequestStatus::class, 'status_id');
    }

    public function Cars()
    {
        return $this->hasMany(EnterRequestCar::class);
    }

    public function Files()
    {
        return $this->hasMany(ManifestFile::class, 'manifest_id');
    }

}
