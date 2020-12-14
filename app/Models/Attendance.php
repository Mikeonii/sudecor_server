<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = ['name','client_id','date_time'];

    public function client(){
    	return $this->belongsTo('App\Models\Client');
    }
    public $timestamps= false;
}
