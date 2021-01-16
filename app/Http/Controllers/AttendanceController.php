<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Imports\AttendancesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\AttendanceSummary;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
set_time_limit(300);
// resource
use App\Http\Resources\Attendance as AttendanceResource;



class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $attendance = DB::table('attendances')->select('account_no','name','Date/Time');
        $current_month = Date('m');
        $attendance = Attendance::select('account_id','name','date_time')
        ->whereMonth('date_time','10') 
        ->whereDay('date_time','8')
        ->orderBy('date_time')
        ->get();
        
        return view('attendance.index',['attendance'=>$attendance]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function import_attendance(Request $request){
        //validate file

        // $validator = Validator::make($request->all(),['file'=>'required|max:5000|mimes:xlsx,xls,csv']);
        // if($validator->fails()){
        //     return redirect()->back()->with(['error'=>$validator->errors()->all()]);
        // }
        // else{

        // access file
        $file = $request->file('file');
        // import
        if(Excel::import(new AttendancesImport, $file)){
            return "DB Import: success";
        }
        else{
            return "DB Import: failed";
        }
        // return redirect()->back()->with(['success'=>'File Uploaded Successfully']);
        // }
       
    }
    
     // create clients based on inserted attendance
    public function create_clients(){
        $attendance = Attendance::get();

        foreach($attendance as $item){ 
            $check = Client::where('name',$item->name)->get();
            if(count($check) !== 0){
                // skip
            }
            else{
                $client = new Client;
                $client->name = $item->name;
                $client->id = $item->client_id;
                if(!$client->save()){
                   echo "something is wrong"; }
  
                }
            }
            echo "completed";
        }   
        public function get_attendance(){
            $attendance = Attendance::all();
            return new AttendanceResource($attendance);

        }
         public function get_individual_attendance($client_id){
            $attendance = Attendance::where("client_id",$client_id)->get();
            return new AttendanceResource($attendance);

        }
        
        public function get_attendance_summary($client_id){
            $summary = AttendanceSummary::select('regular_time','over_time','sunday','holiday','year_and_month')->where("client_id",$client_id)->get();
            return new AttendanceResource($summary);
        }


        public function calculate_summary(Request $request){

            $month = $request->input('month');
            $year = $request->input('year');
            $half = $request->input('half');

            $response = "";
           // ->where('id','1')
            $clients = Client::select('id','is_straight','is_morning_shift')->get();
            foreach($clients as $client){
                $is_morning_shift = $client->is_morning_shift;
                if($half == '1'){
                    // if half is one, loop within the month only from days 11 - 25
                    $day_start = 11;
                    $day_end = 25;
                    // pass to a function which returns an array of total
                    $result = $this->calc($client,$year,$month,$day_start,$day_end,$half,$is_morning_shift); 
                    // if($result->sum() == '0'){
                    //     echo "Data already exist";
                    // }
                    // else{
                    //     echo $result;
                    // }
                    // insert into db
                    // $this->insert_calculations($result,$client,$year,$month,$half,$is_morning_shift); 
                   
                }
                elseif($half == '2'){
                   // if half is two, loop within the month and next month from days 26 - 10
                    $day_start = 26;
                    $day_end = 31;
                    // testing

                    // calculate summary
                    $result = $this->calc($client,$year,$month,$day_start,$day_end,$half,$is_morning_shift);
                    // for the next month 
                    $day_start = 1;
                    $day_end = 10;
                    $next_month = $month+1;
                    if($year == '12'){
                        $year = $year+=1;
                        $next_month = 1;
                    }

                    $result2 = $this->calc($client,$year,$next_month,$day_start,$day_end,$half,$is_morning_shift);
                    // add the two results
                    $x = ['total_hour','over_time','holiday','sunday'];
                    foreach($x as $i){
                        $result[$i] = $result[$i]+=$result2[$i];
                    }
                   
                    // insert into DB
                    $this->insert_calculations($result,$client,$year,$month,$half,$is_morning_shift);
                    
                }
                else{
                    
                    // $day_start = 1;
                    // $day_end = 31;
                    } 
            }
    }
        /*this function accepts $results and insert data into attendance summary db*/
        public function insert_calculations($result,$client,$year,$month,$half,$is_morning_shift){
             // if it does not exist
                    if($result != null){
                        // ignore 0 values
                        if($result->sum() != 0){
                        // insert into db
                        $insert = new AttendanceSummary;
                        $insert->client_id = $client->id;
                        $insert->regular_time = $result->get('total_hour');
                        $insert->over_time = $result->get('over_time');
                        $insert->sunday = $result->get('sunday');
                        $insert->holiday = $result->get('holiday');
                        $insert->year_and_month = $year.'-'.$month;
                        $insert->half = $half;
                        $insert->save();
                        return "success";}}
                    // if it does exist
                    else{return "row already exist";}
        }
            /* function for calculation
            this will return the sumary for each client 
            */ 
        public function calc($client,$year,$month,$day_start,$day_end,$half,$is_morning_shift){
            // check if exist 
            $check_if_exist = AttendanceSummary::select('client_id')
            ->where('client_id',$client->id)
            ->where('year_and_month',$year.'-'.$month)
            ->where('half',$half)->exists();
            $total_hour = 0;
            $total_minute = 0;
            $over_time = 0;
            $sunday = 0;
            $holiday = 0;
           
            // if it is not yet inserted into the attendance summary
            if(!$check_if_exist)
            {   // check if morning shift
                if($is_morning_shift)
                {        
                    $shift_in = '7';
                    $shift_out = '16';
                    $complete_hour = $client->is_straight ? 9 : 8;
                    // query each day 
                    for($i = $day_start; $i <= $day_end; $i++)
                    {   
                        $date = strval($year.'-'.$month.'-'.$i);

                        $attendances = Attendance::where('client_id',$client->id)
                        ->whereDate('date_time',$date)
                        ->get();

                        if($attendances->get(0) == null || $attendances->get(1) == null)
                        { // if morning or afternoon attendance is missing then pass to the next day
                            continue;
                        }
                        else
                        {
                            // get date
                            $morning_date = Carbon::parse($attendances->get(0)->date_time);
                            $afternoon_date = Carbon::parse($attendances->get(1)->date_time);

                           
                            $first_time = $morning_date;
                            $second_time = $afternoon_date;
                            $first_entry = $first_time->toTimeString();
                            /* if first time is less than the shift_in 
                            then substitute first time with shift_in
                            example: first_time = 6:39:00, shift_in: 7 
                            in this case, replace first time with 7 so the counting starts at 7
                            */
                            if($first_entry < 8 && $first_entry > 6){
                                $first_time = Carbon::parse($first_time->toDateString()." ".$shift_in.":00:00");
                            }
                            
                            // subtract second from first to get regular time for the day
                            $date_today = $first_time->toDateString();
                            $parsed_date_today = Carbon::parse($date_today);
                            
                            $hour = $second_time->diff($first_time)->format('%H');

                            // // subtract 1 if client is not straight time 
                            if(!$client->is_straight){
                                $hour-=1;
                            }
                            // echo "complete_hour: ".$complete_hour;
                            // echo "||";
                            // echo "first time: ".$first_time;
                            // echo "second time: ".$second_time;
                            // echo "||";
                            // echo "hour: ".$hour;
                            // echo " ";
                            $check = Holiday::whereDate('date_time',$date_today)->get();
                            // if sunday
                            if($parsed_date_today->dayOfWeekIso == '7'){
                                // get overtime
                                if($hour>$complete_hour){
                                // set total hour to max hour 
                                $sunday+=$complete_hour;
                                // calculate over time
                                $over_time+=$hour-$complete_hour; 
                                }
                                else{
                                    $sunday+=$hour;  
                                }
                            }
                            // if today is holiday
                            elseif(sizeof($check) > 0){
                                $holiday+=$hour;
                            }
                            // weekdays 
                            else{
                                // get overtime
                                if($hour>$complete_hour){
                                // add complete hour to total_hour
                                $total_hour+=$complete_hour;
                                // calculate over time
                                $over_time+=$hour-$complete_hour; 
                                }
                                else{
                                    $total_hour+=$hour;  
                                }        
                            }     
                        }
                    } // end loop
                    $summary = new Collection([
                    'total_hour'=> $total_hour,
                    'over_time'=> $over_time,
                    'holiday'=> $holiday,
                    'sunday'=> $sunday]);
                } //end if is morning
                else
                {

                    $shift_in = '19:00:00';
                    $shift_out = '7:00:00'; //morning of the other day 
                    $complete_hour = $client->is_straight ? 9 : 8;
                    // query each day 
                    for($i = $day_start; $i <= $day_end; $i++)
                    {   
                        $night_date = strval($year.'-'.$month.'-'.$i);
                        $early_morning_date = strval($year.'-'.$month.'-'.($i+1));

                        $night_attendance = Attendance::where('client_id',$client->id)
                        ->whereDate('date_time',$night_date)
                        ->whereTime('date_time',">=",'16:00:00')

                        ->get();

                        $early_morning_attendance = Attendance::where('client_id',$client->id)
                        ->whereDate('date_time',$early_morning_date)
                        ->whereTime('date_time',"<", $shift_in)
                        ->get();

                        // echo $night_attendance->get(0);
                        if($night_attendance->get(0) == null || $early_morning_attendance->get(0) == null)
                        { // if morning or afternoon attendance is missing then pass to the next day
                            continue;
                        }

                        else
                        {   
                          
                            // get date
                            $night_date = Carbon::parse($night_attendance->get(0)->date_time);
                            $early_morning_date = Carbon::parse($early_morning_attendance->get(0)->date_time);

                            // $first_time = $night_date;
                            // $second_time = $early_morning_date;
                            $first_entry = $night_date->toTimeString(); // convert this carbon object to time string

                            /* if first time is less than the shift_in 
                            then substitute first time with shift_in
                            example: first_time = 6:39:00, shift_in: 7 
                            in this case, replace first time with 7 so the counting starts at 7
                            // */
                            // if($first_entry < 20 && $first_entry > 18){
                            //     $first_time = Carbon::parse($first_time->toDateString()." ".$shift_in.":00:00");
                            // }
                            
                            // // subtract second from first to get regular time for the day
                            $parsed_date_today = Carbon::parse($night_date->toDateString());
                            
                            $hour = $early_morning_date->diff($night_date)->format('%H');

                            // subtract 1 if client is not straight time 
                            if(!$client->is_straight){
                                $hour-=1;
                            // }
                            // echo "complete_hour: ".$complete_hour;
                            echo "||";
                            echo "first time: ".$night_date;
                            echo "second time: ".$early_morning_date;
                            echo "||";
                            echo "hour: ".$hour;
                            echo " ";
                            $check = Holiday::whereDate('date_time',$night_date)->get();
                            // if sunday
                            if($parsed_date_today->dayOfWeekIso == '7'){
                                // get overtime
                                if($hour>$complete_hour){
                                // set total hour to max hour 
                                $sunday+=$complete_hour;
                                // calculate over time
                                $over_time+=$hour-$complete_hour; 
                                }
                                else{
                                    $sunday+=$hour;  
                                }
                            }
                            // if today is holiday
                            elseif(sizeof($check) > 0){
                                $holiday+=$hour;
                            }
                            // weekdays 
                            else{
                                // get overtime
                                if($hour>$complete_hour){
                                // add complete hour to total_hour
                                $total_hour+=$complete_hour;
                                // calculate over time
                                $over_time+=$hour-$complete_hour; 
                                }
                                else{
                                    $total_hour+=$hour;  
                                }        
                            }     
                        }
                    }
                    } // end loop
                    $summary = new Collection([
                    'total_hour'=> $total_hour,
                    'over_time'=> $over_time,
                    'holiday'=> $holiday,
                    'sunday'=> $sunday]);
                } //end if night shift 
            } //end if exist
            else{
                $summary = new Collection([
                    'total_hour'=> 0,
                    'over_time'=> 0,
                    'holiday'=> 0,
                    'sunday'=>0]);
                }
            return $summary;  
        }
                    
    public function delete_double_entry($year,$month)

        {
        /*

        DELETE DOUBLE ENTRY FOR THIS MONTH
        0. Foreach clients in clients table, loop their attendance.
        1. Make a loop 1-31
        2. For each loop, query day within this year and month using the loop's index.
        3. Get the time of the first row from the query 
        4. Query a row where the time is more than the first row time
        and less than the break time(based on shift_in and shift_out).
            Q: how to get the break time? A: add shift_in + shift_out then divide by 2.
        5. Delete this row. 
        6. Repeat step 3-4. if no rows are returned, proceed to next step. 
        6.1 Insert 1 to is_morning column in attendance table. This will be used to determine the time in and time out
        7. Get the tiem of the second row from the query
        8. Query a row where the time is more than the second row time
        9. Delete this row. 
        10. Repeat step 6-7 until there is no item returned. 
        11. Close. 
        */

        $clients = Client::select('id')->get();
        $total = 0;
        // check year and month if it exist. purpose: to lessen waiting time
        $exist = Attendance::select('id')
        ->whereYear('date_time',$year)
        ->whereMonth('date_time', $month)
        ->exists();

        // get min date
        $min_date = Attendance::select('date_time')
        ->whereYear('date_time',$year)
        ->whereMonth('date_time', $month)
        ->min('date_time');
        // get max date
        $max_date = Attendance::select('date_time')
        ->whereYear('date_time',$year)
        ->whereMonth('date_time', $month)
        ->max('date_time');

        $min_date = Carbon::parse($min_date)->format('d');
        $max_date = Carbon::parse($max_date)->format('d');

        if(!$exist){
            return "No data found";
        }
        else{
             foreach($clients as $client){
               
                $client_id = $client->id;
                $shift_in = '7';
                $shift_out = '16';
                $break = round(($shift_in+$shift_out)/2);
                $break = $break.':00:00';
                
                for($i=$min_date; $i<=$max_date; $i++){
                    $date = $year."-".$month."-".$i;

                    // get this day
                    $morning_row = Attendance::where('client_id',$client_id)
                    ->whereDate('date_time',$date)
                    ->whereTime('date_time','<',$break)
                    ->orderBy('date_time','asc')
                    ->get();

                    // get this day 
                    $afternoon_row = Attendance::where('client_id',$client_id)
                    ->whereDate('date_time',$date)
                    ->whereTime('date_time','>',$break)
                    ->orderBy('date_time','asc')
                    ->get();

                    // if there are no morning or afternoon rows delete 
                    if(sizeof($morning_row)  == '0' || sizeof($afternoon_row) == '0'){
                        // delete rows in this day
                        Attendance::where('client_id',$client_id)
                        ->whereDate('date_time',$date)
                        ->delete();
                        continue;
                    } 
                 

                    // check if there are no morning rows
                    if(sizeof($morning_row) == '0'){
                        continue;

                    }
                    else{      
                        // insert 1 to is_morning column            
                        $update = Attendance::find($morning_row[0]->id);
                        $update->is_morning = '1';
                        $update->save(); 
                    
                    }
                      
                    if(sizeof($morning_row) > 1){

                        // get first row's time
                        $first_morning_time = strval($morning_row[0]->date_time);

                         // get only the time
                        $time = explode(' ',$first_morning_time);
                        $time = $time[1];

                        //  query the same as above, -> where date_time is > the first morning time 
                        // and date_time is less than $break. and then delete.
                         
                        $del_rows = Attendance::where('client_id',$client_id)
                        ->whereDate('date_time',$date)
                        ->whereTime('date_time','>',$time)
                        ->whereTime('date_time','<',$break)
                        ->delete();

                        $total+=1;  

                        }  

                    // echo $afternoon_row;
                     if(sizeof($afternoon_row) > 1){

                        // get first row's time
                        $first_afternoon_time = strval($afternoon_row[0]->date_time);
                        // get only the time
                        $time = explode(' ',$first_afternoon_time);
                        $time = $time[1];
                       
                        /* query the same as above, -> where date_time is > the first afternoon time 
                        and date_time is greater than $break. and then delete.
                         */ 
                        $del_rows = Attendance::where('client_id',$client_id)
                        ->whereDate('date_time',$date)
                        ->whereTime('date_time','>',$time)
                        ->delete();
                     
                        $total+=1;
                    }

                }
            }

            echo "Success! Total rows affected: ".$total;
        }
            
    }
        
        // get indi client,attendance_summary and attendance based on month and year. 
        public function client_full_info(Request $request){
            
            // get all client and loop foreach
            $full_info = array();
            $clients = Client::select('id')->get();

            foreach($clients as $client){

                $client_id = $client->id;
                $date = $request->date;

                $date = explode('-',$date);
                $year = $date[0];
                $month = $date[1];

                // get client 
                $info = array();
                $date = $year.'-'.$month;
                $client = Client::where('id',$client_id)->get();

                $attendance_summary = AttendanceSummary::where('client_id',$client_id)
                ->where('year_and_month',$date)
                ->get();
       
                $attendances = Attendance::where('client_id',$client_id)
                ->whereYear('date_time',$year)
                ->whereMonth('date_time',$month)
                ->get();

                array_push($info, $client,$attendance_summary,$attendances);
                array_push($full_info,$info);

            }
            
             return $full_info;
        
        }
        public function print_to_pdf($month,$year){

            // get all client and loop foreach
            $full_info = new Collection;
            $clients = Client::select('id')->get();

            foreach($clients as $client){

                $client_id = $client->id;

                // get client 
                $info = new Collection;
                $client = Client::where('id',$client_id)->get();

                $attendance_summary = AttendanceSummary::where('client_id',$client_id)
                ->where('year_and_month',$year.'-'.$month)
                ->get();
    
                $morning_attendance = Attendance::select('date_time')->where('client_id',$client_id)
                ->whereYear('date_time',$year)
                ->whereMonth('date_time',$month)
                ->where('is_morning' ,'1')
                ->get()
                ->all();

                $afternoon_attendance = Attendance::select('date_time')->where('client_id',$client_id)
                ->whereYear('date_time',$year)
                ->whereMonth('date_time',$month)
                ->where('is_morning' ,'0')
                ->get()
                ->all();

                $attendance_result = array_map(null,$morning_attendance,$afternoon_attendance);
            
                $info->push($client,$attendance_summary,$attendance_result,$month,$year);

                $full_info->push($info);
            } 

          
            // return $full_info;
            // return view('print.print_to_pdf',compact('full_info'));
            return Excel::download(new AttendanceExport($full_info), 'attendance.xlsx');

        }

    public function reset(){
        // reset all from DB
        Attendance::truncate();
        AttendanceSummary::truncate();
        Client::truncate();
        Holiday::truncate();
        return "Successfully Deleted";
        }
    }

