
	<style>
		.main{

		}
		.wrapper{
			display: grid;
			grid-template-columns: 50% 50%;
			max-width: 400px;
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
	<div class="wrapper">
		<div>
			<table>
			<thead>
				<tr>
					<td>Morning</td>
					
				</tr>
			</thead>
			<tbody>

				@foreach($individual_info[2] as $row)
				<tr>
					<td>{{$row->date_time}}</td>
					
					
				</tr>
				@endforeach
				
			</tbody>
			</table>
		</div>
		<div>
			
			<table>
			<thead>
				<tr>
					<td>Afternoon</td>
					
				</tr>
			</thead>
			<tbody>

				@foreach($individual_info[3] as $row)
				<tr>
					<td>{{$row->date_time}}</td>
				</tr>
				@endforeach
				
			</tbody>
		</table>
		</div>
	</div>
	<div>
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
