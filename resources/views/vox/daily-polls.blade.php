@extends('vox')

@section('content')

	<div class="container">
		{!! csrf_field() !!}

		<a class="back-home" href="{{ getLangUrl('/') }}">
			{!! nl2br(trans('vox.daily-polls.popup.back')) !!}
		</a>
		<h1>{!! nl2br(trans('vox.daily-polls.title')) !!}</h1>
		
		<div id="calendar" data-link="{{ getLangUrl('get-polls') }}"></div>

		@if(!empty($date_poll))
			<script type="text/javascript">
				var go_to_date = '{!! $date_poll !!}';
				var go_to_month = '{!! $poll_month !!}';
				var go_to_year = '{!! $poll_year !!}';
				var cur_date = '{!! date("Y-m-d") !!}';
				@if(!empty($poll_stats))
					var poll_stats = true;
				@endif
			</script>
		@endif

	</div>
    	
@endsection