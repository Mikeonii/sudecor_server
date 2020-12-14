<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\AttendanceSummary;
use App\Models\Attendance;

use Maatwebsite\Excel\Concerns\FromCollection;

class ClientExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Client::all();
    }
}
