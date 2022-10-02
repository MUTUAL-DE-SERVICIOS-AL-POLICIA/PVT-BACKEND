<?php

namespace App\Models\Affiliate;

use App\Models\Affiliate\AffiliateToken;
use App\Models\EconomicComplement\EcoComProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateDevice extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $fillable = [
        'affiliate_token_id', 
        'enrolled', 
        'liveness_actions', 
        'verified', 
        'eco_com_procedure_id'
    ];

    protected $primaryKey = 'affiliate_token_id';
    public $incrementing = false;
    protected $casts = [
        'liveness_actions' => 'array',
    ];

    public function affiliate_token() 
    {
        return $this->belongsTo(AffiliateToken::class);
    }

    public function eco_com_procedure() 
    {
        return $this->belongsTo(EcoComProcedure::class);
    }
}
