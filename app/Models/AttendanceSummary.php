<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    use HasFactory;
    protected $fillable = ['client_id','regular_time','over_time','sunday','holiday','year_and_month'];
      public function client(){
    return $this->belongsTo('App\Models\Client');
    }
    public $timestamps= false;
}
