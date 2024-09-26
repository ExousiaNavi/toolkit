<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpreadsheetId extends Model
{
    use HasFactory;
    protected $fillable = ['sid','spread_id','platform','brand','currencyType','index','is_active'];
}
