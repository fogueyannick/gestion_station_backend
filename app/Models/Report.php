<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Station;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'user_id',
        'date',
        'super1_index',
        'super2_index',
        'super3_index',
        'gazoil1_index',
        'gazoil2_index',
        'gazoil3_index',
        'super_sales',       // facultatif selon ta table
        'gazoil_sales',      // facultatif selon ta table
        'total_sales',       // facultatif selon ta table
        'stock_sup_9000',
        'stock_sup_10000',
        'stock_sup_14000',
        'stock_gaz_10000',
        'stock_gaz_6000',
        'versement',
        'depenses',          // JSON si plusieurs dépenses
        'autres_ventes',     // JSON si plusieurs ventes
        'commandes',         // JSON si plusieurs commandes
        'photos',            // JSON avec noms de fichiers
    ];

    protected $casts = [
        'date' => 'date',
        'versement' => 'float',
        'depenses' => 'array',
        'autres_ventes' => 'array',
        'commandes' => 'array',
        'photos' => 'array',
    ];

    // Relation : chaque rapport appartient à un user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation : chaque rapport appartient à une station
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}


