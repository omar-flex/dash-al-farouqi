<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EnterRequestFile extends Model
{
    protected $guarded = ['id'];

    public function getUrl()
    {
        return Storage::url($this->path);
    }

    public function getIcon()
    {
        switch ($this->extension) {
            case 'pdf':
                return asset('/assets/media/svg/files/pdf.svg');
            default:
                return Storage::url($this->path);
        }
    }
}
