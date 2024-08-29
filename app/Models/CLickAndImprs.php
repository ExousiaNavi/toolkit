<?php

namespace App\Models;

use App\Models\BO;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CLickAndImprs extends Model
{
    use HasFactory;

    protected $fillable = ['b_o_s_id', 'creative_id', 'imprs', 'clicks', 'spending'];

    #belongs to bo
    public function bo() :BelongsTo{
        return $this->belongsTo(BO::class, 'b_o_s_id');
    }
}
