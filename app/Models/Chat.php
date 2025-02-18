<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'id',
    //     'name',
    //     'chat_link',
    // ];

    protected $guarded = [];

    public function reportDetails()
    {
        return $this->hasMany(Report_Detail::class);
    }
}
