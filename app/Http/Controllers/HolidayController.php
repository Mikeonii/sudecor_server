<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;
use App\Http\Resources\Holiday as HolidayResource;
class HolidayController extends Controller
{
   public function insert_holiday(Request $request){
   		// check if holiday exist
   		$check = Holiday::where('date_time',$request->input('date_time'))
   		->get();

   		if(sizeof($check) > 0){
   			echo "Row already existed!";
   		}
   		else{
   			$holiday = new Holiday;
   			$holiday->holiday_name = $request->input('holiday_name');
   			$holiday->date_time = $request->input('date_time');

   			$response = $holiday->save() ? "saved!" : "something is wrong";
   			return $response;

   		}
   		
    }
    public function index(){
    	$holiday = Holiday::all();
    	return new HolidayResource($holiday);
    	
    }
}
