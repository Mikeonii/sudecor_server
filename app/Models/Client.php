<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
     protected $fillable = ['name','id'];
     // protected $primaryKey = 'account_no';
    public function attendances(){
    	return $this->hasMany('App\Models\Attendance');
    }
    public function attendance_summary(){
    	return $this->hasMany('App\Models\AttendanceSummary');
    }
    public $timestamps= false;
}
