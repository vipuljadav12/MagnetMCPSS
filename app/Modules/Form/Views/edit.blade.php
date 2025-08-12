@extends('layouts.admin.app')
@section('title')
Edit Form | {{config('APP_NAME',env("APP_NAME"))}}
@endsection
@section('content')
<link rel="stylesheet" href="{{url('/resources/assets/admin/css/jquery-ui.css')}}">
    <style type="text/css">
        #wrapperloading{width:100%;height:100%;position:fixed;top:0%;left:0%;z-index:2000; display:none; background:url("{{url('/resources/assets/admin/images/loaderbg.png')}}") repeat;}
        #wrapperloading #loading{position:fixed;top:50%;left:50%;margin:-38px 0 0 -72px; font-weight:bold; text-align:center;}
    </style>
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Edit Form</div>
            <div class="">
                <a href="{{ url('admin/Form') }}" class="btn btn-sm btn-secondary" title="">Back</a>
            </div>
        </div>
    </div>
     <form action="{{ url('admin/Form/update/'.$page_id.'/'.$form->id)}}" method="post" name="edit_form">
        {{csrf_field()}}
        <input type="hidden" name="form_id" id='form_id' value="{{$form->id}}">
                <input type="hidden" name="form_id" id='page_id' value="{{$page_id}}">

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link " id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a></li>
            <li class="nav-item"><a class="nav-link active" id="create-tab" data-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="true">Create</a></li>
            <li class="nav-item"><a class="nav-link" id="preview-tab" href="http://{{get_district_slug().".".Request::getHost()."/previewform/".$page_id.'/'.$form->id}}" target="_blank">Preview</a></li>
        </ul>
        @include("layouts.admin.common.alerts")
        <div class="tab-content bordered" id="myTabContent">
            <div class="tab-pane fade {{-- show active --}}" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="">
                    <div class="form-group">
                        <label for="name" class="control-label">Form Name : </label>
                        <div class=""><input type="text" name="name" class="form-control" value="{{$form->name}}"></div>
                        @if($errors->first('name'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('name')}}
                             </div>
                        @endif
                    </div>
                    {{-- <div class="form-group">
                        <label for="url" class="control-label">Form URL : </label>
                        <div class=""><input type="text" name="url" class="form-control" value="{{$form->url}}"></div>
                        @if($errors->first('url'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('url')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="description" class="control-label">Description : </label>
                        <div class="">
                            <textarea name="description" class="form-control" id="editor01" style="resize: none;">{{$form->description}}</textarea>
                        </div>
                        @if($errors->first('description'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('description')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="thank_you_url" class="control-label">Thank You URL : </label>
                        <div class=""><input type="text" name="thank_you_url" class="form-control" value="{{$form->thank_you_url}}"></div>
                        @if($errors->first('thank_you_url'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('thank_you_url')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="thank_you_msg" class="control-label">Thank You Message : </label>
                        <div class="">
                            <textarea class="form-control" name="thank_you_msg" id="editor02" style="resize: none;">{{$form->thank_you_msg}}</textarea>
                        </div>
                        @if($errors->first('thank_you_msg'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('thank_you_msg')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="to_mail" class="control-label">To Mail : </label>
                        <div class=""><input type="text" name="to_mail" value="{{$form->to_mail}}" class="form-control"></div>
                        @if($errors->first('to_mail'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('to_mail')}}
                             </div>
                        @endif
                    </div> --}}
                    <div class="form-group">
                        <label for="no_of_pages" class="control-label">Number Of Pages :</label>
                        <div class=""><input type="text" name="no_of_pages" value="{{$form->no_of_pages}}" class="form-control numbersOnly"></div>
                        @if($errors->first('no_of_pages'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('no_of_pages')}}
                             </div>
                        @endif
                    </div>
                    {{-- <div class="form-group">
                        <label for="show_logo" class="control-label">Show Logo : </label>
                        <div class="">
                            <input id="chk_0" type="checkbox" name="show_logo" class="js-switch js-switch-1 js-switch-xs" data-size="Small" {{$form->show_logo=='y'?'checked':''}} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="captcha" class="control-label">Google Captcha : </label>
                        <div class=""><input id="chk_01" type="checkbox" name="captcha" class="js-switch js-switch-1 js-switch-xs" data-size="Small" {{$form->captcha=='y'?'checked':''}} /></div>
                    </div> --}}
                </div>
            </div>
            <div class="tab-pane fade show active" id="create" role="tabpanel" aria-labelledby="create-tab">
               
                @include("Form::formbuilder")

            </div>
            <div class="tab-pane fade " id="preview" role="tabpanel" aria-labelledby="preview-tab">
                <div class="preview">
                    {!! $form->form_source_code !!}
                </div>
            </div>
        </div>
        <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                    <button type="Submit" class="btn btn-warning btn-xs submit"><i class="fa fa-save"></i> Save </button>
                    <button type="Submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                    <a class="btn btn-danger btn-xs" href="{{url('/admin/Form')}}"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        </div>
</form>
@endsection
@section('scripts')
<script type="text/javascript">
    /*$(".preview div,.preview label").attr('contenteditable','false');
    $(".preview .close").remove();*/
    $(".alert").delay(2000).fadeOut(1000);
    $(".submit").click(function () {
        $("input[name='form_source_code']").val($('#form_data').html());
    });

    //Form url
            $("input[name='name']").on('input',function () {
                $("input[name='url']").val($(this).val().toLowerCase().trim().replace(/[^a-z0-9\s]/gi, '').replace(/\s{1,}/g,'-'));
            });
            $("input[name='url']").on('input',function () {
                $(this).val($(this).val().toLowerCase().trimStart().replace(/\s{1,}/g,'-').replace(/-{2,}/g,'-').replace(/[^a-z0-9-]/gi, ''));
            });
        $.validator.addMethod( "email", function( value, element ) {
                return this.optional(element) || /^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/.test(value);
            }, "The email address is not valid" );
        $("form[name='edit_form']").validate({
            rules:{
                name:{
                    required:true,
                    maxlength:255
                },
                /*url:{
                    required:true,
                    maxlength:255,
                    remote:{
                        url: "{{url("admin/Form/uniqueurl")}}",
                        type: "GET",
                        data: {    
                            id:$("input[name='form_id']").val(),
                        }
                    }
                },
                description:{
                    required:true,
                    maxlength:500
                },
                thank_you_url:{
                    required:true,
                    maxlength:255
                },
                thank_you_msg:{
                    required:true,
                    maxlength:255
                },
                to_mail:{
                    required:true,
                    email:true,
                    maxlength:255
                },*/
                no_of_pages:
                {
                    required:true,
                    maxlength:2
                }


            },messages:{
                name:{
                    required:'The Name field is required.',
                    maxlength:'The name is may not be greater than 255 characters.'
                },
                no_of_pages:{
                    required:'Please enter number of pages.',
                    maxlength:'The Number of pages is may not be greater than 2 characters.'
                },
                url:{
                    required:'The Url filed is required.',
                    maxlength:'The Url is may not be greater than 255 characters.',
                    remote:'The url is already taken.'
                },
                description:{
                    required:'The Descriptioon filed is required.',
                    maxlength:'The Description is may not be greater than 500 characters.'
                },
                thank_you_url:{
                    required:'The thank you url filed is required.',
                    maxlength:'The thank you url is may not be greater than 255 characters.'
                },
                thank_you_msg:{
                    required:'The thank you message filed is required.',
                    maxlength:'The thank you message is may not be greater than 500 characters.'
                },
                to_mail:{
                    required:'The to Mail filed is required.',
                    maxlength:'The Email address is may not be greater than 255 characters.'
                },

            },errorPlacement: function(error, element)
                {
                    error.appendTo( element.parents('.form-group'));
                    error.css('color','red');
                },
                submitHandler: function (form) {
                    form.submit();
                }

        });
        var deletefunction = function(id){
            swal({
                title: "Are you sure you would like to move this Form to trash?",
                text: "",
                // type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                closeOnConfirm: false
            }).then(function() {
                window.location.href = '{{url('/')}}/admin/Form/delete/'+id;
            });
        };
</script>
    @include("Form::builderjs")
<div id="wrapperloading"><div id="loading"><i class="fa fa-spinner fa-spin fa-5x"></i> <br> Please Wait ... </div></div>   

@endsection