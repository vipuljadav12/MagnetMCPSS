@extends('layouts.front.app')

@section('content')
        <div class="mt-20">
        <div class="card bg-light p-20">
            <div class="text-center font-20 b-600 mb-10">
            		@if(isset(getConfig()[$msg_type.'_title']))
	            		@php
	            			$msg_title = getConfig()[$msg_type.'_title'];
	            			$msg_title = str_replace("###CONFIRMATION_NO###", (isset($confirmation_no) ? $confirmation_no : ""), $msg_title);
	            			$msg_title = str_replace("###STARTOVER###", "<a hrer='".url('/')."' class='btn btn-primary'>START OVER</a>", $msg_title);

	            		@endphp
	            		{!! $msg_title !!}
	            	@endif


        		</div>
            <div class="">
            	@if(isset(getConfig()[$msg_type]))
            		@php
            			$msg = getConfig()[$msg_type];
            			$msg = str_replace("###CONFIRMATION_NO###", (isset($confirmation_no) ? $confirmation_no : ""), $msg);
            			$msg = str_replace("###STARTOVER###", "<a hrer='".url('/')."' class='btn btn-primary'>START OVER</a>", $msg);

            		@endphp
            		{!! $msg !!}
            	@endif

                @if($msg_type != "before_application_open_text" && $msg_type != "after_application_open_text" && $msg_type != "no_grade_info")
                    @if(Session::has("from_admin"))
                        <a href="{{url('/phone/submission')}}" class="btn btn-info">START OVER</a>
                    @else
                        <a href="{{url('/')}}" class="btn btn-info">START OVER</a>
                    @endif
                @elseif($msg_type == "no_grade_info")
                    <a href="{{url('/')}}" class="btn btn-info">EXIT</a>
                @endif
            </div>
        </div>
    </div>

@endsection