<?php

namespace App\Models\Admin;

use App\Models\Loan\Record;
use Laratrust\Models\LaratrustRole;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use App\Models\Workflow\WfState;

class Role extends LaratrustRole
{
    protected $hidden = ['pivot'];
    public $guarded = ['id'];
    protected $fillable = ['module_id', 'name', 'display_name', 'sequence_number'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function records()
    {
        return $this->morphMany(Record::class, 'recordable');
    }

    public function wf_states()
	{
		return $this->hasMany(WfState::class);
    }   

}
