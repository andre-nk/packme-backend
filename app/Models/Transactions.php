<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'packs',
        'type',
        'status',
        'user_id',
        'payment_url',
        'provider',
        'valuation'
    ];

    protected $casts = [
        'packs' => 'array'
    ];

    public function user(){
        return $this -> hasOne(User::class, 'id', 'user_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }
}
