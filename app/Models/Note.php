<?php

namespace App\Models;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    public $timestamps = true;
    public $guarded = ['id'];
    protected $hidden = ['user_id'];
    public $fillable = ['annotable_id', 'annotable_type', 'message', 'date'];

    public function annotable()
    {
        return $this->morphTo();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
