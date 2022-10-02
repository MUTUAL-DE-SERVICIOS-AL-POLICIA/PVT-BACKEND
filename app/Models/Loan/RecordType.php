<?php

namespace App\Models\Loan;

use App\Models\Loan\Record;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordType extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = ['name', 'display_name'];

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
