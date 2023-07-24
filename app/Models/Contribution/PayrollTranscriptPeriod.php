<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollTranscriptPeriod extends Model
{
    use HasFactory;
    protected $connection = 'db_aux';
    protected $table = 'payroll_transcript_periods';
    public $timestamps = true;
}