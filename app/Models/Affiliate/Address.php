<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\Affiliate\Affiliate;

class Address extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "addresses";
    public $guarded = ['id'];

    protected $fillable = [
        'city_address_id', 
        'zone', 
        'street', 
        'number_address',
        'description'
    ];

    public function city()
    {
        return $this->belongsTo(City::class,'city_address_id','id');
    }
    
    public function affiliates()
    {
    	return $this->morphedByMany(Affiliate::class, 'addressable');
    }
}
