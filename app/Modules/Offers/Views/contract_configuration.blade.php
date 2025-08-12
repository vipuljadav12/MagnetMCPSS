@extends('layouts.admin.app')
@section('title')
	Contract Configuration
@endsection
@section('content')
	<div class="card shadow">
		<div class="card-body d-flex align-items-center justify-content-between flex-wrap">
			<div class="page-title mt-5 mb-5">Contract Configuration</div>
			<div class=""><a href="{{url('admin/Offers/Contract/Configuration/Preview')}}" target="_blank" class="btn btn-sm btn-success" title="Go Back">Preview</a></div>
		</div>
	</div>
	@include('layouts.admin.common.alerts')
	<form action="{{ url('admin/Offers/Contract/Configuration/store')}}" method="post" id="contract_configuration">
    {{csrf_field()}}
		<div class="card shadow">
	        {{-- <div class="card-header">Open Enrollment</div> --}}
	        <div class="card-body">
	        	<div class="row">
	        		<div class="col-12">
	                    <div class="form-group">
	                        <label for="">Header Text :</label>
	                        <div class="">
	                        	<textarea name="header_text" id="editor00" class="form-control simple-editor">{!! $data['header_text'] ?? '' !!}</textarea>
	                        </div>
	                    </div>
	                </div>
	                <div class="col-12">
	                    <div class="form-group">
	                        <label for="">Title Text :</label>
	                        <div class="">
	                        	<input type="text" name="title_text" class="form-control" value="{{ $data['title_text'] ?? '' }}">
	                        </div>
	                    </div>
	                </div>
	                <div class="col-12">
	                    <div class="form-group">
	                        <label for="">Footer Text :</label>
	                        <div class="">
	                        	<textarea name="footer_text" id="editor01" class="form-control simple-editor">{!! $data['footer_text'] ?? '' !!}</textarea>
	                        </div>
	                    </div>
	                </div>
	        	</div>
	        </div>
	    </div>

	    <div class="card shadow">
	        <div class="card-header">
	        	Contract Options
	        	<a href="javascript:void(0);" class="btn btn-secondary btn-sm add-option" title="" style="float: right;">Add Option</a>
	        </div>
	        <div class="card-body">
	        	<div class="option-list mt-10" id="option-sortable">
	        		@php
	        			$contractOptions = $data['extra'];
	        		@endphp

	        		@if(isset($contractOptions['options']['title']) && !empty($contractOptions['options']['title']))
		        		@foreach($contractOptions['options']['title'] as $key=>$title)
		        			<div class="form-group border p-10 sortable">
		        				<div class="row">
		        					<div class="col-12 col-md-6 d-flex flex-wrap align-items-center">
		        						<a href="javascript:void(0);" class="mr-10 handle2" title=""><i class="fas fa-arrows-alt"></i></a>
		        						<label for="" class="mr-10">Option {{ $loop->iteration }} : </label>
		        						<div class="flex-grow-1">
		        							<input type="text" class="form-control" name="extra[options][title][]" value="{{ $title ?? '' }}">
		        						</div>
		        					</div>
		        					<div class="col-10 col-md-6 d-flex flex-wrap align-items-center">
		        						<label for="" class="mr-10">Content : </label>
		        						<div class="flex-grow-1">

		        							 <textarea class="form-control content-simple-editor" style="width: 300px; height: 50px;" name="extra[options][content][]">{!! $contractOptions['options']['content'][$key] ?? '' !!}</textarea> 
		        						</div>
		        					</div>
		        				</div>
		        			</div>
		        		@endforeach
		        	@endif
	        	</div>
	        </div>
	    </div>

	    <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                    <input type="hidden" name="submit-from" id="submit-from-btn" value="general">
                    <button type="submit" class="btn btn-warning btn-xs" name="submit" value="Save" title="Save"><i class="fa fa-save"></i> Save </button>
                    <button type="submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit" title="Save & Exit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                    <a class="btn btn-danger btn-xs" href="javascript:void(0)" title="Cancel"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        </div>
	</form>
@endsection
@section('scripts')
<script type="text/javascript" src="{{url('/')}}/resources/assets/admin/plugins/laravel-ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="{{url('/resources/assets/admin/plugins/laravel-ckeditor/adapters/jquery.js')}}"></script>
<script type="text/javascript">
	// CKEDITOR.disableAutoInline = false;
	assignInlineCKeditor();
	$(document).find('.simple-editor').ckeditor({
		toolbar : 'Basic',
        toolbarGroups: [
            { name: 'document',    groups: [ 'mode', 'document' ] },            // Displays document group with its two subgroups.
            { name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },           // Group's name will be used to create voice label.
            { name: 'basicstyles', groups: [ 'cleanup', 'basicstyles'] },
        
            '/',                                                                // Line break - next group will be placed in new line.
            { name: 'links' }
        ]
	});

    $(document).on("click", ".add-option" , function(){
        var i = $(this).parent().parent().find(".option-list").children(".form-group").length + 1;
        // console.log(i);
        var uniqid = Math.random();
        var option =    '<div class="form-group border p-10 sortable">'+
                            '<div class="row">'+
                                '<div class="col-12 col-md-6 d-flex flex-wrap align-items-center">'+
                                    '<a href="javascript:void(0);" class="mr-10 handle2" title=""><i class="fas fa-arrows-alt"></i></a>'+
                                    '<label for="" class="mr-10">Option '+i+' : </label>'+
                                    '<div class="flex-grow-1"><input type="text" class="form-control" name="extra[options][title][]"></div>'+
                                '</div>'+
                                '<div class="col-10 col-md-6 d-flex flex-wrap align-items-center">'+
                                    '<label for="" class="mr-10">Content : </label>'+
                                    '<div class="flex-grow-1"><textarea name="contenteditor'+i+'" class="form-control content-simple-editor" id="contenteditor'+i+'" name="extra[options][content][]"></textarea></div>'+
                                '</div>'+
                            '</div>'+
                        '</div>';

    	$(this).parent().parent().find(".option-list").append(option);

    	
    	custsort2();

    	var elem = $(this).find('#contenteditor'+i);
		CKEDITOR.replace('contenteditor'+i);
		for (instance in CKEDITOR.instances) {
            //update element
            CKEDITOR.instances[instance].updateElement();
        }
//    	CKEDITOR.inline();
		// for(name in CKEDITOR.instances) {
		//  	console.log(name);
	 //    }
    	//assignInlineCKeditor();
    });
	function assignInlineCKeditor(){

		// $(document).find(".content-simple-editor").each(function(){
		// 	let id = $(this).attr('id');
		// 	console.log(id);
		// 	$(document).find('#'.id).ckeditor({
		// 		toolbar : 'Basic',
		// 	    toolbarGroups: [
		// 	        { name: 'basicstyles', groups: [ 'cleanup', 'basicstyles'] },
		// 	    ]
		// 	});
		// });
		$(document).find('.content-simple-editor').each(function(i, editableElement)
		{
		    CKEDITOR.inline(editableElement, {
				toolbar : 'Basic',
		        toolbarGroups: [
		            { name: 'basicstyles', groups: [ 'cleanup', 'basicstyles'] },
		        ]
			});
		});
		// for(name in CKEDITOR.instances) {
		//  	console.log(name);
	 //    }
	}


    custsort2();
    function custsort2() {
        $("#option-sortable").sortable({
            handle: ".handle2"
        });
        // $(".option-list").disableSelection();
    };

    var form = $('#contract_configuration').submit(function (e) {
    	// e.preventDefault();
	   /* $.each($('.content-simple-editor'),function(i, obj){
	    	value = obj.innerHTML.split('<p>')[1].split('</p>')[0];
	    	console.log(value);
	        $("<textarea>").attr({
	            'name':'extra[options][content][]'
	        }).val(value).appendTo(form);
	    });*/
	});
    
</script>
@endsection