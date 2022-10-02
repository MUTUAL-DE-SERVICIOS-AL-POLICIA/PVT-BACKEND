<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleSequence extends Model
{
    use HasFactory;
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['procedure_type_id', 'role_id', 'next_role_id','sequence_number_flow'];

    public function procedure_type()
    {
        return $this->belongsTo(ProcedureType::class);
    }

    public function current_role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function next_role()
    {
        return $this->belongsTo(Role::class, 'next_role_id', 'id');
    }


}
