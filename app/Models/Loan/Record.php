<?php

namespace App\Models\Loan;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'role_id',
        'record_type_id',
        'recordable_id',
        'recordable_type',
        'action'
    ];

    public function recordable()
    {
        return $this->morphTo();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function record_type()
    {
        return $this->belongsTo(RecordType::class);
    }
}
