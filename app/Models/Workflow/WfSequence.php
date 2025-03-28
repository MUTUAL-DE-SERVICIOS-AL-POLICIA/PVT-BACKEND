<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfSequence extends Model
{
    use HasFactory;
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'workflow_id',
        'wf_state_current_id',
        'wf_state_next_id', 
        'action',
        'created_at',
        'updated_at'
    ];
    
    public function workflow()
    {
      return $this->belongsTo(Workflow::class); 
    }

    public function current_state()
    {
        return $this->belongsTo(WfState::class, 'wf_state_current_id');
    }

    public function next_state()
    {
        return $this->belongsTo(WfState::class, 'wf_state_next_id');
    }
}
