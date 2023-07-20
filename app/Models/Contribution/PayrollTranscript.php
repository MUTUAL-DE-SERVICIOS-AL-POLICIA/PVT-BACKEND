<?php

namespace App\Models\Contribution;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollTranscript extends Model
{
    use HasFactory;

    public static function data_period($month,$year)
    {
        $data = collect([]);
        $exists_data = true;
        $payroll = PayrollTranscript::whereMonth_p($month)->whereYear_p($year)->count('id');
        if($payroll == 0) $exists_data = false;

        $data['exist_data'] = $exists_data;
        $data['count_data'] = $payroll;

        return  $data;
    }
}
