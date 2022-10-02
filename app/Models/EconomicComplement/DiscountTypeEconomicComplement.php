<?php

namespace App\Models\EconomicComplement;

use App\Models\DiscountType;
use App\Models\EconomicComplement\EconomicComplement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountTypeEconomicComplement extends Model
{
    use HasFactory;
    protected $table = "discount_type_economic_complement";

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'discount_type_id',
        'economic_complement_id',
        'amount',
        'message',
        'date'
    ];

    public function discount_type()
    {
        return $this->belongsTo(DiscountType::class);
    }
    public function economic_complement()
    {
        return $this->belongsTo(EconomicComplement::class);
    }
}
