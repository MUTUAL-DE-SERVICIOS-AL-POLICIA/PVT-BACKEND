<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;

class ContributionRate extends Model
{
    use HasFactory;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'month_year',
        'retirement_fund',
        'mortuary_quota',
        'retirement_fund_commission',
        'mortuary_quota_commission',
        'mortuary_aid',
        'created_at',
        'updated_at'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
