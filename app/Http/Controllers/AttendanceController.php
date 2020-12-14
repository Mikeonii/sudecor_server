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

            $clients = Client::select('id','shift_in','shift_out')->get();
            foreach($clients as $client){
                if($half == '1'){
                    // if half is one, loop within the month only from days 11 - 25
                    $day_start = 11;
                    $day_end = 25;
                    // pass to a function which returns an array of total
                    $result = $this->calc($client,$year,$month,$day_start,$day_end,$half); 
                    // insert into db
                    $this->insert_calculations($result,$client,$year,$month,$half); 
                   
                }
                elseif($half == '2'){
                   // if half is two, loop within the month and next month from days 26 - 10
                    $day_start = 26;
                    $day_end = 31;


                    // calculate summary
                    $result = $this->calc($client,$year,$month,$day_start,$day_end,$half);
                    // for the next month 
                    $day_start = 1;
                    $day_end = 10;
                    $next_month = $month+1;

                    $result2 = $this->calc($client,$year,$next_month,$day_start,$day_end,$half);
                    // add the two results
                    $x = ['total_hour','over_time','holiday','sunday'];
                    foreach($x as $i){
                        $result[$i] = $result[$i]+=$result2[$i];
                    }
                   
                    // insert into DB
                    $this->insert_calculations($result,$client,$year,$month,$half);
                    
                }
                else{
                    
                    // $day_start = 1;
                    // $day_end = 31;
                    } 
            }
    }
        /*this function accepts $results and insert data into attendance summary db*/
        public function insert_calculations($result,$client,$year,$month,$half){
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
        public function calc($client,$year,$month,$day_start,$day_end,$half){
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
            {
                $shift_in = $client->shift_in;
                $shift_out = $client->shift_out;
                $break = round(($shift_in+$shift_out)/2);
                $break = $break.":00:00";
                $complete_hour = $shift_out-$shift_in;

                // query each day
                for($i = $day_start; $i <= $day_end; $i++){
                    $date = strval($year.'-'.$month.'-'.$i);
                    $attendances_first = Attendance::where('client_id',$client->id)
                    ->whereDate('date_time',$date)
                    ->whereTime('date_time','<',$break)
                    ->get();
                    
                    $attendances_second = Attendance::where('client_id',$client->id)
                    ->whereDate('date_time',$date)
                    ->wheretime('date_time','>',$break)
                    ->get();

                    $first_time = null;
                    $second_time = null;

                    foreach($attendances_first as $attendance_row){
                        $first_date = $attendance_row->date_time;
                        $first_time = explode(' ',$first_date);
                        $date_today = $first_time[0];
                        $first_time = $first_time[1];
                                            }
                    foreach($attendances_second as $attendance_row){
                        $second_date = $attendance_row->date_time;
                         $second_time = explode(' ',$second_date);
                         $second_time = $second_time[1];
                                            }

                    if($first_time == null){
                        // no morning rows = no attendance for the day
                        continue;
                    }
                    else{
                        // subtract second from first to get regular time for the day
                        $first_time = Carbon::parse($first_time);
                        $second_time = Carbon::parse($second_time);
                        $parsed_date_today = Carbon::parse($date_today);

                        $regular_time = $second_time->diff($first_time)->format('%H:%i:%s');
                        $hour = $second_time->diff($first_time)->format('%H');
                        $minute = $second_time->diff($first_time)->format('%i');
                        
                        $total_hour+=$hour;
                        $total_minute+=$minute;

                        // divide total minutes to 60 to get hours then add to total_hour
                        $total_hour+=intval($total_minute/60);

                        // get overtime
                        if($hour>$complete_hour){
                            // calculate over time
                            $over_time+=$hour-$complete_hour; 
                        }
                        // if today is sunday
                        if($parsed_date_today->dayOfWeekIso == '7'){
                            $sunday+=$hour;
                        }
                        // if today is holiday
                        $check = Holiday::whereDate('date_time',$date_today)->get();
                        
                        if(sizeof($check) > 0){
                            $holiday+=$hour;
                            }    
                        }
                    // end loop
                    }
                $summary = new Collection([
                    'total_hour'=> $total_hour,
                    'over_time'=> $over_time,
                    'holiday'=> $holiday,
                    'sunday'=> $sunday
                ]);
               
            // end if
            }
            else{
                $summary = new Collection([
                    'total_hour'=> 0,
                    'over_time'=> 0,
                    'holiday'=> 0,
                    'sunday'=>0]);
            }
        return $summary;
        // end function
        }
                    
        public function delete_double_entry($year,$month){
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

        $clients = Client::select('id','shift_in','shift_out')->get();
        $total = 0;
        // check year and month if it exist. purpose: to lessen waiting time
        $exist = Attendance::select('id')
        ->whereYear('date_time',$year)
        ->whereMonth('date_time', $month)
        ->exists();

        if(!$exist){
            return "No data found";
        }
        else{
             foreach($clients as $client){
               
                $client_id = $client->id;
                $shift_in = $client->shift_in;
                $shift_out = $client->shift_out;
                $break = round(($shift_in+$shift_out)/2);
                $break = $break.':00:00';
                
                for($i=1; $i<=31; $i++){
                    $date = $year."-".$month."-".$i;

                    // get this day
                    $morning_row = Attendance::where('client_id',$client_id)
                    ->whereDate('date_time',$date)
                    ->whereTime('date_time','<',$break)
                    ->orderBy('date_time','asc')
                    ->get();

                    // check if morning row returns an object
                    if(sizeof($morning_row) == '0'){
                        // pass
                        continue;
                    }
                    else{
                    
                        //  insert 1 to is_morning column
                        // check if 1 is not present
                        if($morning_row[0]->is_morning != '1'){
                            $update = Attendance::find($morning_row[0]->id);
                            $update->is_morning = '1';
                            $update->save(); 
                        }
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

                    // get this day 
                    $afternoon_row = Attendance::where('client_id',$client_id)
                    ->whereDate('date_time',$date)
                    ->whereTime('date_time','>',$break)
                    ->orderBy('date_time','asc')
                    ->get();

                    // echo $afternoon_row;
                     if(sizeof($afternoon_row) > 1){

                        // get first row's time
                        $first_afternoon_time = strval($afternoon_row[0]->date_time);
                        // get only the time
                        $time = explode(' ',$first_afternoon_time);
                        $time = $time[1];
                       
                        /* query the same as above, -> where date_time is > the first afternoon time 
                        and date_time is greater than $break. and then delete.
                        // */ 
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
                ->get();

                $afternoon_attendance = Attendance::select('date_time')->where('client_id',$client_id)
                ->whereYear('date_time',$year)
                ->whereMonth('date_time',$month)
                ->where('is_morning' ,'0')
                ->get();

                $info->push($client,$attendance_summary,$morning_attendance,$afternoon_attendance,$month,$year);

                $full_info->push($info);


          

            }
            // return view('print.print_to_pdf',compact('full_info'));
            return Excel::download(new AttendanceExport($full_info), 'attendance.xlsx');

        }

    }

