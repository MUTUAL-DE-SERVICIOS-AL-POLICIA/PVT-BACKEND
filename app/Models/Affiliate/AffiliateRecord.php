<?php

namespace App\Models\Affiliate;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateRecord extends Model
{
    use HasFactory;

    protected $table = 'affiliate_records_pvt';

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
