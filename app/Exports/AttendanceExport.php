<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AttendanceExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $full_info;

    	function __construct($full_info){
    		$this->full_info = $full_info;
    	}
    

    public function view(): View
    {
    	
        return view('print.print_to_pdf',['full_info'=>$this->full_info]);
        // return view('print.print_to_pdf',compact('full_info'));
    }
}
