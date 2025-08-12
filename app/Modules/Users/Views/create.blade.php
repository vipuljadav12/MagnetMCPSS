@extends("layouts.admin.app")
@section('title')
	Add Users | {{config('APP_NAME',env("APP_NAME"))}}
@endsection
@section('content')
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Add User</div>
        <div class=""><a href="{{url('admin/Users/')}}" class="btn btn-sm btn-primary" title=""><i class="fa fa-arrow-left"></i> Back</a></div>
    </div>
</div>
<form class="" id="UserForm" action="{{url('admin/Users')}}" method="post" >
    {{csrf_field()}}
    <div class="card shadow">
        <div class="card-body">
            {{-- @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif --}}
            <div class="form-group">
                <label for="" class="control-label">First Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="{{old("first_name")}}" name="first_name">
                </div>
                @if($errors->has("first_name"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('first_name')}}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="" class="control-label">Last Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="{{old("last_name")}}" name="last_name">
                </div>
                @if($errors->has("last_name"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('last_name')}}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="" class="control-label">Email : </label>
                <div class="">
                    <input type="email" class="form-control" value="{{old("email")}}" name="email" id="email">
                </div>
                @if($errors->has("email"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('email')}}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="" class="control-label">Confirm Email : </label>
                <div class="">
                    <input type="email" class="form-control" value="{{old("email_confirmation")}}" name="email_confirmation">
                </div>
                {{-- @if($errors->has("confirm_email"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('confirm_email')}}
                    </div>
                @endif --}}
            </div>
            <div class="form-group">
                <label for="" class="control-label">Plain Password : </label>
                <div class="">
                    <input type="text" class="form-control" value="{{old("password")}}" name="password" >
                </div>
                <div class="small">To update a user's password, provide one here</div>
                @if($errors->has("password"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('password')}}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="" class="control-label">User Type : </label>
                <div class="">
                    <select class="form-control custom-select" name="role_id">
                        <option value="">Select</option>
                        @forelse($roles as $r=>$role)
                            <option value="{{$role->id}}">{{($role->name)}}</option>
                        @empty
                        @endforelse
                    </select>
                </div>
                @if($errors->has("role_id"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('role_id')}}
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- <div class="card shadow">
        <div class="card-header">School Access
            <div class="small">Restrict a user to specific schools. If this is empty, they will have access to all school.</div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="" class="control-label">Schools : </label>
                <div class="school-list">
                    <div class="mb-10 d-flex align-items-center" id="first-school">
                        <a href="javascript:void(0);" class="remove-school d-none mr-20" title=""><i class="fas fa-minus-circle"></i></a>
                        <a href="javascript:void(0);" class="add-school mr-20" title=""><i class="fas fa-plus-circle"></i></a>
                        <select class="form-control custom-select" name="district[]">
                            <option value="">Select School</option>
                            @foreach($stores as $s=>$store)
                                <option value="{{$store->id}}">{{$store->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <div class="box content-header-floating" id="listFoot">
        <div class="row">
            <div class="col-lg-12 text-right hidden-xs float-right">
                {{-- <a class="btn btn-warning btn-xs" href="javascript:void(0);"><i class="fa fa-save"></i> Save </a>  --}}
                {{-- <a class="btn btn-success btn-xs" href="user.html"><i class="fa fa-save"></i> Save &amp; Exit</a> --}}
                {{-- <a class="btn btn-danger btn-xs" href="javascript:void(0);"><i class="far fa-trash-alt"></i> Delete</a>  --}}
                <button type="submit" class="btn btn-warning btn-xs submitBtn" >
                    <i class="fa fa-save"></i> Save
                </button>
                <button type="submit" class="btn btn-success btn-xs" name="save_edit" value="save_edit">
                    <i class="fa fa-save"></i> Save &amp; Edit
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
@section('scripts')
    {{-- <script src="{{asset('resources/assets/common/js/jquery.validate.min.js')}}"></script> --}}
    {{-- <script src="{{asset('resources/assets/common/js/additional-methods.min.js')}}"></script> --}}
<script type="text/javascript">
    jQuery.validator.addMethod( "email", function( value, element ) {
        return this.optional(element) || /^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/.test(value);
    }, "The email address is not valid" );
    $("#UserForm").validate({
        rules:{
            first_name:{
                required:true,
                maxlength:100,
            },
            last_name:{
                required:true,
                maxlength:100,
            },
            email:{
                required:true,
                email:true,
                maxlength:255,
                remote:{
                    url:'{{url('admin/Users/uniqueemail')}}',
                    type:'get',
                    data:{

                    }
                }
            },
            email_confirmation:{
                required:true,
                equalTo:"#email",
            },
            password:{
                required:true,
                minlength: 8,
                maxlength:255,
            },
            role_id:{
                required:true,
            }

        },
        messages:{
            first_name:{
                required: 'First Name is required.',
                maxlength:'The first name may not be greater than 255 characters.'
            },
            last_name:{
                required: 'Last Name is required.',
                maxlength:'The last name may not be greater than 255 characters.'
            },
            email:{
                required: 'Email is required.',
                remote:'The email has already been taken.',
                maxlength:'The Email may not be greater than 255 characters.',
            },
            email_confirmation:{
                required:'Email Confirmation is required.',
                equalTo:"Email Confirmation is not match.",
            },
            password:{
                required:'Password is required.',
                minlength: "The password must be at least 8 characters long",
                maxlength:'The password may not be greater than 255 characters.',
            },
            role_id:{
                required:'Please select User Type.',
            }
        },errorPlacement: function(error, element)
        {
            error.appendTo( element.parents('.form-group'));
            error.css('color','red');
        },submitHandler:function(form){
            form.submit();
        }
    });
    $(document).on("click",".add-school",function()
    {
        var obj =  $(this).parent().clone();
        $(this).parent().after(obj);
        showHideBtn();
    });
    $(document).on("click",".remove-school",function()
    {
        $(this).parent().remove();
        showHideBtn();
    });
    function showHideBtn()
    {
        var count = $(".add-school").length;
        if(count > 1)
        {
            $(document).find(".remove-school").removeClass("d-none");
        }
        else
        {
            $(document).find(".remove-school").addClass("d-none");
        }
        // alert(count);
    }
</script>
@endsection