<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContributionImportPeriod extends Model
{
    use HasFactory;
    public $guarded = ['id'];
    public $timestamps = true;
}
