<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'id',
        'report_day',
        'report_time',
        'weeks_in_period',
        'current_period_end_date'
    ];

    public function Setting_Hashtag()
    {
        return $this->hasMany(Setting_Hashtag::class);
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'setting_hashtags', 'setting_id', 'hashtag_id');
    }
}
