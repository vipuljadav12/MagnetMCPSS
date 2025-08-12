@extends('layouts.admin.app')
@section('title') District Configuration | {{config('APP_NAME',env("APP_NAME"))}} @endsection
@section('styles')
<style type="text/css">
    .error {
        color: red;
    }
</style>
@endsection
@section('content')
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">District Configuration</div>
    </div>
</div>

@include("layouts.admin.common.alerts")

<form id="frm_index" action="{{url('admin/DistrictConfiguration/store')}}" method="post" enctype= "multipart/form-data">
{{csrf_field()}}
    <div class="card shadow">
        <div class="card-body">

            <div class="form-group">
                <label class="control-label">Letter Signature : </label>
                <div class="row">
                    <div class="col-md-11">
                         <textarea class="form-control" id="editor00" name="letter_signature">
                            {!! ($old_letter_signature_value != '' ? $old_letter_signature_value : '') !!}
                        </textarea>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">Email Signature : </label>
                <div class="row">
                    <div class="col-md-11">
                         <textarea class="form-control" id="editor01" name="email_signature">
                            {!! ($old_email_signature_value ? $old_email_signature_value : '') !!}
                        </textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
            <div class="box content-header-floating" id="listFoot">
            <div class="row">
                <div class="col-lg-12 text-right hidden-xs float-right">
                    <button type="Submit" class="btn btn-warning btn-xs submit"><i class="fa fa-save"></i> Save </button>
                    <a class="btn btn-danger btn-xs" href="{{url('/admin/DistrictConfiguration')}}"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        </div>

</form>
@endsection
@section('scripts')
<script type="text/javascript" src="{{url('/')}}/resources/assets/admin/plugins/laravel-ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="{{url('/resources/assets/admin/plugins/laravel-ckeditor/adapters/jquery.js')}}"></script>

<script type="text/javascript"> 


   /* jQuery.validator.addMethod("imageDimension", function(value, element,options) {
        var myImg = document.querySelector("#email_signature_thumb");
        var realWidth = myImg.naturalWidth;
        var realHeight = myImg.naturalHeight;

        if(realWidth > 500 || realHeight > 500){
            return false;
        }else{
            return true;
        }
     }, "");


    $('input[name="signature"]').change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#signature_thumb')
                    .attr('src', e.target.result)
            };
            reader.readAsDataURL(this.files[0]);
        }
    });*/


   /* $('#frm_index').validate({
        rules: {
            letter_signature: {
                imageDimension: true,
                // required: true,
                extension: 'png,jpg,gif'
            },
            email_signature: {
                imageDimension: true,
                // required: true,
                extension: 'png,jpg,gif'
            }
        },
        messages: {
            letter_signature: {
                imageDimension: 'Maximum image dimensions are 500x500.',
                required: 'Signature Image File is required.',
                extension: 'Signature Image File is the file of type .png/.jpg/.gif'
            },
            email_signature: {
                imageDimension: 'Maximum image dimensions are 500x500.',
                required: 'Signature Image File is required.',
                extension: 'Signature Image File is the file of type .png/.jpg/.gif'
            }

        } 
    });*/
        CKEDITOR.replace('editor00',{
             filebrowserImageBrowseUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageBrowser.php?path={{url("/")}}',
            filebrowserBrowseUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?type=Files',
            filebrowserUploadUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?command=QuickUpload&type=Files',
            filebrowserWindowWidth: (screen.width/1.5),
            filebrowserWindowHeight: (screen.height/1.5),
        });
        CKEDITOR.replace('editor01', {
             filebrowserImageBrowseUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageBrowser.php?path={{url("/")}}',
            filebrowserBrowseUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?type=Files',
            filebrowserUploadUrl: '{{url("/")}}/resources/assets/admin/plugins/laravel-ckeditor/imageupload.php?command=QuickUpload&type=Files',
            filebrowserWindowWidth: (screen.width/1.5),
            filebrowserWindowHeight: (screen.height/1.5),
        });

</script> 
@endsection