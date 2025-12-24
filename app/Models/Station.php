<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DailyReport; // nécessaire pour la relation

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
    ];

    // Relation : une station a plusieurs rapports
    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }
}
