<?php

namespace App\Models;

use App\Models\PlatformKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = ['platform','link'];

    public function platformKeys() :HasMany{
        return $this->hasMany(PlatformKey::class);
    }
}
