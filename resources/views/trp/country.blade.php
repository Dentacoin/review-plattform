@extends('trp')

@section('content')

	<div class="page-dentists page-c">
		<div class="black-overflow" style="display: none;">
		</div>
		<div class="home-search-form">
			<div class="tac" style="display: none;">
		    	<h1>
		    		{!! nl2br(trans('trp.page.search.title')) !!}
		    		
		    	</h1>
		    	<h2>
		    		{!! nl2br(trans('trp.page.search.subtitle')) !!}
		    		
		    	</h2>
		    </div>
		    @include('trp.parts.search-form')
			
		</div>

		<div class="main-top">
	    </div>

	    <div class="sort-wrapper">
	    	<h1 class="white-title">{!! nl2br(trans('trp.page.search.country-title')) !!}</h1>
	    </div>

	    <div class="countries-wrapper container">
		    <div class="countries">
		    	<div class="flex">
		    		<div class="col">
				    	@foreach($countries_groups as $key => $country)
				    		@if(is_string($country))
				    			<span class="letter">{{ $country }}</span>
				    		@else
				    			<a href="{{ getLangUrl('dentists-in-'.$country->slug) }}">{{ $country->name }}</a>
				    		@endif

				    		@if( in_array($key, $breakpoints) )
				    			</div>
				    			<div class="col">
				    		@endif

				    	@endforeach
				    </div>
			    </div>
		    </div>
		</div>
	</div>

@endsection