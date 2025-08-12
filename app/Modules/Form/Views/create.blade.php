@extends('layouts.admin.app')
@section('title')
Add Form | {{config('APP_NAME',env("APP_NAME"))}}
@endsection
@section('content')
    <div class="card shadow">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div class="page-title mt-5 mb-5">Add Form</div>
            <div class="">
                <a href="{{ url('admin/Form') }}" class="btn btn-sm btn-secondary" title="">Back</a>
            </div>
        </div>
    </div>
     <form action="{{ url('admin/Form/store')}}" method="post" name="add_form">
        {{csrf_field()}}
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active " id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a></li>
            {{-- <li class="nav-item"><a class="nav-link active" id="create-tab" data-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="true">Create</a></li>
            <li class="nav-item"><a class="nav-link" id="preview-tab" data-toggle="tab" href="#preview" role="tab" aria-controls="preview" aria-selected="true">Preview</a></li> --}}
        </ul>
        <div class="tab-content bordered" id="myTabContent">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="">
                    <div class="form-group">
                        <label for="name" class="control-label">Form Name : </label>
                        <div class=""><input type="text" name="name" class="form-control" value="{{old('name')}}"></div>
                        @if($errors->first('name'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('name')}}
                             </div>
                        @endif
                    </div>
                    {{-- <div class="form-group">
                        <label for="url" class="control-label">Form URL : </label>
                        <div class=""><input type="text" id="url" name="url" class="form-control" value="{{old('url')}}"></div>
                        @if($errors->first('url'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('url')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="description" class="control-label">Description : </label>
                        <div class="">
                            <textarea name="description" class="form-control" id="editor01" style="resize: none;">{{old('description')}}</textarea>
                        </div>
                        @if($errors->first('description'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('description')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="thank_you_url" class="control-label">Thank You URL : </label>
                        <div class=""><input type="text" name="thank_you_url" class="form-control" value="{{old('thank_you_url')}}"></div>
                        @if($errors->first('thank_you_url'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('thank_you_url')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="thank_you_msg" class="control-label">Thank You Message : </label>
                        <div class="">
                            <textarea class="form-control" name="thank_you_msg" id="editor02" style="resize: none;">{{old('thank_you_msg')}}</textarea>
                        </div>
                        @if($errors->first('thank_you_msg'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('thank_you_msg')}}
                             </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="to_mail" class="control-label">To Mail : </label>
                        <div class=""><input type="text" name="to_mail" value="{{old('to_mail')}}" class="form-control"></div>
                        @if($errors->first('to_mail'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('to_mail')}}
                             </div>
                        @endif
                    </div> --}}
                    <div class="form-group">
                        <label for="no_of_pages" class="control-label">Number Of Pages : </label>
                        <div class=""><input type="text" name="no_of_pages" value="{{old('no_of_pages')}}" class="form-control numbersOnly"></div>
                        @if($errors->first('no_of_pages'))
                            <div class="mb-1 text-danger">
                                {{ $errors->first('no_of_pages')}}
                             </div>
                        @endif
                    </div>
                    {{-- <div class="form-group">
                        <label for="show_logo" class="control-label">Show Logo : </label>
                        <div class="">
                            <input id="chk_0" type="checkbox" name="show_logo" class="js-switch js-switch-1 js-switch-xs" data-size="Small" checked />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="captcha" class="control-label">Google Captcha : </label>
                        <div class=""><input id="chk_01" type="checkbox" name="captcha" class="js-switch js-switch-1 js-switch-xs" data-size="Small" checked /></div>
                    </div> --}}
                </div>
            </div>
            {{-- <div class="tab-pane fade show active" id="create" role="tabpanel" aria-labelledby="create-tab">
                @include("Form::formbuilder")

            </div>
            <div class="tab-pane fade" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                <div class="preview">
                    <div class="card">
                        <div class="card-header">Step 1 - Please enter your student's requested information</div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Student's Legal First Name : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Student's Legal Last Name : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Date of Birth : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control mydatepicker01">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Race : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <select class="form-control custom-select">
                                        <option value="">Choose an option</option>
                                        <option value="">Black/African American</option>
                                        <option value="">White</option>
                                        <option value="">Asian</option>
                                        <option value="">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Current School : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Current Year Grade : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <select class="form-control custom-select">
                                        <option value="">Choose an option</option>
                                        <option value="">1</option>
                                        <option value="">2</option>
                                        <option value="">3</option>
                                        <option value="">4</option>
                                        <option value="">5</option>
                                        <option value="">6</option>
                                        <option value="">7</option>
                                        <option value="">8</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Next Year Grade : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <select class="form-control custom-select">
                                        <option value="">Choose an option</option>
                                        <option value="">1</option>
                                        <option value="">2</option>
                                        <option value="">3</option>
                                        <option value="">4</option>
                                        <option value="">5</option>
                                        <option value="">6</option>
                                        <option value="">7</option>
                                        <option value="">8</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Home Address : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">City : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">ZIP : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Best Contact phone number : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Alternate phone number : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Parent Email address : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="email" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3">Confirm Email address : </label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <input type="email" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="control-label col-12 col-md-4 col-xl-3"></label>
                                <div class="col-12 col-md-6 col-xl-6">
                                    <a href="javascript:void(0);" class="btn btn-secondary step-2-1-btn" title="">Submit</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
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
    <!-- Custom script by Priyank -->
    <script type="text/javascript"></script>
    <script type="text/javascript">
        //create form
        // alert();
        $("#textbox").click(function () {
            $(".input_container").append("<div class='form-group row'>\n" +
                    "<label class='control-label col-12 col-md-5' contenteditable=\"true\">Label : </label>\n" +
                    "<div class='col-12 col-md-6 col-xl-6'>\n" +
                        "<input type='text' class='form-control'>\n" +
                    "</div>\n" +
                "<div class='col-md-1'><span class='close'><i class='fa fa-window-close' aria-hidden='true'></i></span></div>"+
                "</div>");
        });
        $('#textarea').click(function () {
            $(".input_container").append("<div class='form-group row'>\n" +
                    "<label class='control-label col-12 col-md-5' contenteditable=\"true\">Label : </label>\n" +
                    "<div class='col-12 col-md-6 col-xl-6'>\n" +
                        "<textarea cols='40' style='resize: none'>\n" +
                        "\n" +
                        "</textarea>\n" +
                    "</div>\n" +
                    "<div class='col-md-1'><span class='close'><i class='fa fa-window-close' aria-hidden='true'></i></span></div>\n" +
                "</div>");
        });
        $('#checkbox').click(function () {
            var id=$("input[type='checkbox']").length;
            var length=$("#input_container").children().length;
            if ($(".input_container .row:last-child").attr('id')!='checkbox')
            {
                $(".input_container").append("<div class='form-group row checkbox' id='checkbox'>\n" +
                    "<label class='control-label col-12 col-md-5' contenteditable='true'>Label : </label>\n" +
                    "<div class='col-12 col-md-6 col-xl-6 checkbox_container'>\n" +
                    "<div class='custom-control custom-checkbox d-inline'>\n" +
                    "<input  value='' type='checkbox' class='custom-control-input' id='checkbox"+id+"' name='' style='height: auto !important;'>\n" +
                    "<label for='checkbox"+id+"' class='custom-control-label' contenteditable='true'> label</label>\n" +
                    "</div>\n"+
                    "</div>\n" +
                    "<div class='col-md-1'><span class='close'><i class='fa fa-window-close' aria-hidden='true'></i></span></div>\n" +
                    "</div>");
            }
            else {
                // alert()
                $(".input_container .row:last-child").find(".checkbox_container").append(
                    "<div class='custom-control custom-checkbox d-inline'>\n" +
                    "<input  value='' type='checkbox' class='custom-control-input' id='checkbox"+id+"' name='' style='height: auto !important;'>\n" +
                    "<label for='checkbox"+id+"' class='custom-control-label' contenteditable='true'> label</label>\n" +
                    "</div>\n");
            }
        });
        $(document).on('click','.close',function () {
            $(this).parent().parent().remove();
        });
/*        $(".input_container").on('input','label',function(){
            $(this).parent().find('input').val($(this).text());
        });*/
     /*   $(".preview div").attr('contenteditable','false');*/
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
        $("form[name='add_form']").validate({
            rules:{
                name:{
                    required:true,
                    maxlength:255
                },
/*
                url:{
                    required:true,
                    maxlength:255,
                    remote:{
                        url: "{{url("admin/Form/uniqueurl")}}",
                        type: "GET",
                        data: {    
                            url: function () {
                            return $("#url").val();
                            }
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
                /*url:{
                    required:'The Url filed is required.',
                    maxlength:'The Url is may not be greater than 255 characters.',
                    remote:'The url is already taken.'
                },
                description:{
                    required:'The Description filed is required.',
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
                },*/
            },errorPlacement: function(error, element)
                {
                    error.appendTo( element.parents('.form-group'));
                    error.css('color','red');
                },
                submitHandler: function (form) {
                    form.submit();
                }
        });
    </script>
    {{-- @include("Form::builderjs") --}}
@endsection