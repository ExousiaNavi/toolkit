<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IP extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ip_address', 'status'];

    #belongs to user
    public function user() :BelongsTo{
        return $this->belongsTo(User::class);
    }
}
