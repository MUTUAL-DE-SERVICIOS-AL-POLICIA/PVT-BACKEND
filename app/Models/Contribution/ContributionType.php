<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContributionType extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'group_type_contribution_id',
        'name',
        'shortened',
        'created_at',
        'updated_at',
        'deleted_at',
        'description',
        'operator',
        'display_name',
        'sequence'
    ];
    
    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }
    public function group_type_contribution()
    {
        return $this->belongsTo(GroupTypeContribution::class);
    }
}
