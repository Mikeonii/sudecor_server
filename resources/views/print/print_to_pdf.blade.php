
	<style>
		.main{

		}
		.wrapper{
			display: grid;
			grid-template-columns: 50% 50%;
			max-width: 1410px;
		}
		.wrapper > div{
			background-color:#eee;
			padding:10px;
			font-family: sans-serif;
		}
		.wrapper > div:nth-child(odd){
			background-color:#ddd;
		}
		
	
	</style>

	<h2>SUDECOR Daily Attendance Record</h2>

	@foreach($full_info as $individual_info)
	<div>
		<h2>Name: {{$individual_info[0][0]->name}}</h2>
		<h3>Shift-in: {{$individual_info[0][0]->shift_in}} Shift-out: {{$individual_info[0][0]->shift_out}}</h3>
		<p>Attendance Table</p>
	
	<div>
	<table>
		<tbody>

		<tr>
			<td>Morning</td>
			<td>Afternoon</td>
		</tr>
		@foreach($individual_info[2] as $key=> $row)
			
		<tr>
			<td>
				{{$row[0]->date_time}}
			</td>
			<td>{{$row[1]->date_time}}</td>
		</tr>
				
		@endforeach

		</tbody>
	</table>

		<p>Attendance Summary</p>

		@if(sizeof($individual_info[1]) == 0)
			<p>No records found</p>
		@else
		<ul>
			<strong>First Half</strong>	
			@foreach($individual_info[1] as $key=> $row)
			@if($row->half == '1')
			<li>
				Regular Time: {{$row->regular_time}}
			</li>
			<li>
				Over Time: {{$row->over_time}}
			</li>
			<li>Sunday: {{$row->sunday}}</li>
			<li>Holiday: {{$row->holiday}}</li>
			@endif
			@endforeach
		</ul>
			<ul>
			<strong>Second Half</strong>	
			@foreach($individual_info[1] as $key=> $row)
			@if($row->half == '2')
			<li>
				Regular Time: {{$row->regular_time}}
			</li>
			<li>
				Over Time: {{$row->over_time}}
			</li>
			<li>Sunday: {{$row->sunday}}</li>
			<li>Holiday: {{$row->holiday}}</li>
			@endif
			@endforeach
		</ul>
		@endif
	</div>
	@endforeach
	</div>
