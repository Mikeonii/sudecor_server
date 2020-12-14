<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Validator;
use App\Exports\UsersExport;
use App\Imports\UsersImport;
use App\Exports\ClientExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Client;
use Carbon\Carbon;

class FilesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

    public function excelFile(){
        // send client data
        // $date = "29/08/2020 16:00:11";
        // $res1 = explode(' ',$date);
        // $date = $res1[0];
        // $time = $res1[1];

        // $res1 = explode('/',$date);
        // $res = $res1[2].'-'.$res1[1].'-'.$res1[0].' '.$time;

        $clients = Client::all();
        return view('Excel.excel',['clients'=>$clients]);
    }
    
    public function extractFile(Request $request){
        $validator = Validator::make($request->all(),['file'=>'required|max:5000|mimes:xlsx,xls,csv']);


        if($validator->fails()){
           return redirect()->back()->with(['error'=>$validator->errors()->all()]);
        }
        else{
            $dataTime = date('Ymd_His');
            $file = $request->file('file');
            $fileName = $dataTime.'-'.$file->getClientOriginalName();
            $savePath = public_path('/upload/');
            $file->move($savePath, $fileName);

            return redirect()->back()->with(['success'=>'File Uploaded Successfully']);
            
        }
    }
 
    // import excel to DB
    public function import_excel(Request $request){
     
        // validate file
        // access file
        $file = $request->file('file');
        // import
        Excel::import(new UsersImport, $file);
        return redirect()->back()->with(['success'=>'File Uploaded Successfully']);
    }
    public function export_clients(){
        return Excel::download(new ClientExport, 'users.pdf');
    }
  
}
