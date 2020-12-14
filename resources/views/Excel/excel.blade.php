@extends('layouts.layout')
    @section('content')
    <br>
    <div class="container">
        <p>Upload Excel File</p>
        <form action="{{url('/import')}}" method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            @if(session('errors'))
                @foreach($errors as $error)
                    <li>{{$error}}</li>
                @endforeach
            @endif

            @if(session('success'))
                {{session('success')}}
            @endif
            <input type="file" name="file" id="file">
            <button type="submit">Upload File</button>
        </form>
        <hr>
        <h2>Client List</h2>
        <button id = "count">Count</button>
           <table id="myTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Account Number</th>
            </tr>
            
        </thead>
        <tbody>
            @foreach($clients as $client)
            <tr>
                <td>{{$client->name}}</td>
                <td>{{$client->account_no}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
 
    <script type="text/javascript">
    
    $(document).ready( function () {
     var table = $('#myTable').DataTable();
     table.on('click','tr',function(){
        $(this).toggleClass('selected');
     });
     $('#count').click(function(){
        console.log(table.rows('.selected').data()[0][1]);
     });


    } );
    </script>
    @endsection
