<?php

namespace App\Models;

use App\Models\BO;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FTD extends Model
{
    use HasFactory;
    protected $fillable = ['b_o_s_id','keywords','currency','registration_time','first_deposit_time'];

    public function bo() :BelongsTo{
        return $this->belongsTo(BO::class, 'b_o_s_id');
    }
}
