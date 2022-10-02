<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procedure\ProcedureType;
use App\Models\Workflow\Workflow;
use App\Models\Workflow\WfState;
use App\Models\ObservationType;

class Module extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $guarded = ['id'];
    protected $fillable = ['name', 'display_name'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
    public function procedure_types()
    {
        return $this->hasMany(ProcedureType::class);
    } 
    public function workflows()
	{
		return $this->hasMany(Workflow::class);
    }
    public function wf_states()
	{
		return $this->hasMany(WfState::class);
    }    
    public function observation_types()
    {
        return $this->hasMany(ObservationType::class);
    }

}
