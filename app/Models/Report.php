<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'start_date',
        'end_date',
        'google_sheet_url',
    ];

    public function reportDetails()
    {
        return $this->hasMany(Report_Detail::class);
    }
}
