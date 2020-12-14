<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Http\Resources\Client as ClientResource;
use DB;
use Carbon\Carbon;

class ClientsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
      


        // $clients = Client::where('id','>','157')->with('attendance_summary')->get();
        $clients = DB::table('clients')->join('attendance_summaries', function($join){
              $date = Carbon::now();
              $year = $date->year;
              $month = strval($date->month);
              // $month = strval("08");
              // remove 0 in month
              if($month < 10){
                 $month = str_replace("0", "", $month);}
              $formatted_date = $year.'-'.$month;
              $join->on('clients.id','=','attendance_summaries.client_id')
              ->where('attendance_summaries.year_and_month',$formatted_date);
        })->get();

        $clients2 = DB::table('clients')->get();
        if(sizeof($clients) > 0){
            return new ClientResource($clients);
        }
        else{
            return new ClientResource($clients2);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $client =  $request->method('put') ? Client::findOrFail($request->input('client_id')) : "Error: it is not a put request";
       $client->shift_in = $request->input('shift_in');
       $client->shift_out = $request->input('shift_out');

       if($client->save()){
        return "saved";
       } 
       else{
        return "not saved";
       }

       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = Client::where('id',$id)->with('attendances')->get();
        return new ClientResource($client);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function get_clients(){
        $clients = Client::all();
        return new ClientResource($clients);
    }
}
