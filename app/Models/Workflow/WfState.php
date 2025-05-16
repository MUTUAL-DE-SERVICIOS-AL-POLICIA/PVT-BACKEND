<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procedure\ProcedureRecord;
use App\Models\Admin\Module;
use App\Models\Admin\Role;
use App\Models\Contribution\ContributionProcess;

class WfState extends Model
{
    use HasFactory;
    public $timestamps = false;
    public $guarded = ['id'];
    protected $fillable = [
        'module_id',
        'role_id',
        'name', 
        'first_shortened',
        'sequence_number'
    ];
    
  public function sequences_records()
	{
		return $this->hasMany(SequencesRecord::class);
  }

  public function wf_records()
	{
		return $this->hasMany(WfRecord::class);
  }

  public function procedure_records()
	{
		return $this->hasMany(ProcedureRecord::class);
  }

  public function module()
  {
    return $this->belongsTo(Module::class); 
  } 

  public function role()
  {
    return $this->belongsTo(Role::class); 
  } 

  public function contribution_processes()                 
  {
    return $this->hasMany(ContributionProcess::class,'wf_state_current_id','id');
  }

  public function currentSequences()
  {
      return $this->hasMany(WfSequence::class, 'wf_state_current_id', 'id');
  }

  public function nextSequences()
  {
      return $this->hasMany(WfSequence::class, 'wf_state_next_id', 'id');
  }
}
