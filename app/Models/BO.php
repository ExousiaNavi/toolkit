<?php

namespace App\Models;

use App\Models\CLickAndImprs;
use App\Models\FE;
use App\Models\FTD;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BO extends Model
{
    use HasFactory;
    protected $fillable = ['affiliate_username','currency','nsu','ftd','active_player','total_deposit','total_withdrawal','total_turnover','profit_and_loss','total_bonus', 'target_date', 'is_merged', 'brand'];

    public function fe() :HasMany{
        return $this->hasMany(FE::class, 'b_o_s_id');
    }
    public function ftds() :HasMany{
        return $this->hasMany(FTD::class, 'b_o_s_id');
    }
    public function clicks_impression() :HasMany{
        return $this->hasMany(CLickAndImprs::class, 'b_o_s_id');
    }
}
