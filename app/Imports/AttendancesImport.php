<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
class AttendancesImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {   
      
        $date = $row[2];
        $res1 = explode(' ',$date);
        $date = $res1[0];
        $time = $res1[1];

        $res1 = explode('/',$date);
        $res = $res1[2].'-'.$res1[1].'-'.$res1[0].' '.$time;

        return new Attendance([
           'name'=>$row[0],
           'client_id'=>$row[1],
           'date_time'=>$res 
        ]);
        
    }
}
