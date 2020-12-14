<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Attendance List</title>
</head>
<body>
	<h2>Attendance</h2>
	
    <table>	
    	<th>
    		<tr>
    			<td>Account Number</td>
    			<td>Name</td>
    			<td>(YYYY-MM-DD H:i:s)</td>
    		</tr>		
    	</th>
    	<tbody>	
    		@foreach($attendance as $item)
    		<tr>
    			<td>{{$item->account_no}}</td>
    			<td>{{$item->name}}</td>
    			<td>{{$item->date_time}}</td>
    		</tr>
    		@endforeach
    	</tbody>
    </table>
   
    <br>		
   
</body>
</html>