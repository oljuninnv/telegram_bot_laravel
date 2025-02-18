<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'report_day',
        'report_time',
        'weeks_in_period',
    ];

    public function Setting_Hashtag()
    {
        return $this->hasMany(Setting_Hashtag::class);
    }
}
