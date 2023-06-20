<?php

namespace App\Models;

use App\Models\Admin\User;
use App\Models\Loan\Record;
use App\Models\ObservationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Observation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'observables';
    public $timestamps = true;
    protected $primaryKey = null;
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'observation_type_id',
        'observable_id',
        'observable_type',
        'message',
        'date',
        'enabled'
    ];

    public function observable()
    {
        return $this->morphTo();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function observation_type()
    {
        return $this->belongsTo(ObservationType::class);
    }
    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }
}
