<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformKey extends Model
{
    use HasFactory;
    protected $fillable = ['platform_id','key'];
    public function platform() :BelongsTo{
        return $this->belongsTo(Platform::class);
    }
}
