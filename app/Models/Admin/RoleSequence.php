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

    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }

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

    public static function workflow($procedure_type_id)
    {
        return RoleSequence::whereProcedureTypeId($procedure_type_id)->leftJoin('roles as current', 'current.id', '=', 'role_sequences.role_id')->leftJoin('roles as next', 'next.id', '=', 'role_sequences.next_role_id')->orderBy('role_sequences.sequence_number_flow')->orderBy('current.name')->select('role_sequences.role_id', 'role_sequences.next_role_id','role_sequences.sequence_number_flow')->get();
    }
    //saca en poso anterior
    public static function previous_steps($procedure_type_id, $role_id)
    {
        return RoleSequence::whereProcedureTypeId($procedure_type_id)->whereNextRoleId($role_id)->pluck('role_id');
    }

    public static function next_steps($procedure_type_id, $role_id)
    {
        return RoleSequence::whereProcedureTypeId($procedure_type_id)->whereRoleId($role_id)->pluck('next_role_id');
    }

    private static function build_previous_tree($procedure_type_id, $root)
    {
        $tree = [];
        $branch = self::previous_steps($procedure_type_id, $root);
        foreach ($branch as $node) {
            $tree[] = [
                "role:" . $node => self::build_previous_tree($procedure_type_id, $node)
            ];
        }
        return $tree;
    }

    private static function get_keys($array)
    {
        $keys = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array), \RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {
            if (is_string($key)) $keys[] = intval(explode('role:', $key)[1]);
        }
        return collect($keys)->unique();
    }

    private static function previous_roles($procedure_type_id, $current_role_id)
    {
        $roles = self::build_previous_tree($procedure_type_id, $current_role_id);
        $roles = self::get_keys($roles);
       //return Role::whereIn('id', $roles)->orderBy('sequence_number', 'desc')->orderBy('name')->pluck('id');
        return Role::whereIn('id', $roles)->join('role_sequences as role_seq','role_seq.role_id','=','roles.id')->orderBy('role_seq.sequence_number_flow')->orderBy('roles.display_name')->pluck('roles.id');
    }

    public static function flow($procedure_type_id, $current_role_id)
    {
        return (object)[
            'current' => $current_role_id,
            'previous' => self::previous_roles($procedure_type_id, $current_role_id),
            'next' => self::next_steps($procedure_type_id, $current_role_id)
        ];
    }
}
