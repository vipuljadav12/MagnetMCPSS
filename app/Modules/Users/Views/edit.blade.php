@extends("layouts.admin.app")
@section('title')
	Edit Users | {{config('APP_NAME',env("APP_NAME"))}}
@endsection
@section('content')
<div class="card shadow">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="page-title mt-5 mb-5">Edit User</div>
        <div class=""><a href="{{url('admin/Users/')}}" class="btn btn-sm btn-primary" title=""><i class="fa fa-arrow-left"></i> Back</a></div>
    </div>
</div>
@include('layouts.admin.common.alerts')

<form class="" id="UserForm" action="{{url('admin/Users/update/'.$user->id)}}" method="post">
    {{csrf_field()}}
    {{-- {{ method_field('PATCH') }} --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="form-group">
                <label for="" class="control-label">First Name : </label>
                <div class="">
                    <input type="text" class="form-control" value="{{$user->first_name ?? old("first_name")}}" name="first_name">
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
                    <input type="text" class="form-control" value="{{ $user->last_name ?? old("last_name")}}" name="last_name">
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
                    <input type="email" class="form-control" value="{{ $user->email ?? old("email")}}" name="email" disabled="">
                </div>
                @if($errors->has("email"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('email')}}
                    </div>
                @endif
            </div>
            {{-- <div class="form-group">
                <label for="" class="control-label">Confirm Email : </label>
                <div class="">
                    <input type="email" class="form-control" value="{{old("email_confirmation")}}" name="email_confirmation">
                </div>
            </div> --}}
            {{-- <div class="form-group">
                <label for="" class="control-label">Plain Password : </label>
                <div class="">
                    <input type="text" class="form-control" value="{{  Crypt::decrypt($user->password) ?? old("password")}}" name="password" >
                </div>
                <div class="small">To update a user's password, provide one here</div>
                @if($errors->has("password"))
                    <div class="alert alert-danger m-t-5">
                       {{$errors->first('password')}}
                    </div>
                @endif
            </div> --}}
            <div class="form-group">
                <label for="" class="control-label">User Type : </label>
                <div class="">
                    <select class="form-control custom-select" name="role_id">
                        <option value="">Select</option>
                        @forelse($roles as $r=>$role)
                            <option value="{{$role->id}}" @if($user->role_id == $role->id) selected @endif>{{($role->name)}}</option>
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
            <div class="form-group">
              <label class="">Change Password </label>
              <div class="">
                  <input type="checkbox" class="js-switch js-switch-1 js-switch-xs" id="changePassword" data-plugin="switchery" data-size="small"  data-color="#c82333"/>
              </div>
            </div>

            <div class="form-group changePassword">
              <label for="" class="">Password <span class="required">*</span> </label>
              <div class="">
                <input type="password" class="form-control" name="password" id="id_password" value="{{old('password')}}" maxlength="20">
                @if ($errors->has('password'))
                <span class="help-block">
                  <strong>{{ $errors->first('password') ?? ''}}</strong>
                </span>
                @endif
              </div>
            </div>
            <div class="form-group changePassword">
                <label for="" class="">Confirm Password  <span class="required">*</span> </label>
                <div class="">
                  <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" value="{{old('password_confirmation')}}">
                  @if ($errors->has('password_confirmation'))
                  <span class="help-block">
                    <strong>{{ $errors->first('password_confirmation') ?? ''}}</strong>
                  </span>
                  @endif
                </div>
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
                    @foreach($user->district_id as $d=>$distric)
                        <div class="mb-10 d-flex align-items-center" id="first-school">
                            <a href="javascript:void(0);" class="remove-school mr-20" title=""><i class="fas fa-minus-circle"></i></a>
                            <a href="javascript:void(0);" class="add-school mr-20" title=""><i class="fas fa-plus-circle"></i></a>
                            <select class="form-control custom-select"  name="district[]">
                                <option value="">Select School</option>
                                @foreach($stores as $s=>$store)
                                    <option value="{{$store->id}}" @if($store->id == $distric) selected @endif>{{$store->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach                    
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
               {{--  <button type="submit" class="btn btn-warning btn-xs" >
                    <i class="fa fa-save"></i> Save
                </button>
                <button type="submit" class="btn btn-success btn-xs" name="save_edit" value="save_edit">
                    <i class="fa fa-save"></i> Save &amp; Edit
                </button>
                <a class="btn btn-danger btn-xs" href="javascript:void(0);" onclick="deletefunction({{$user->id}})"><i class="far fa-trash-alt"></i> Delete</a> --}}
                <button type="submit" class="btn btn-warning btn-xs" name="submit" value="Save"><i class="fa fa-save"></i> Save </button>
                   <button type="submit" name="save_exit" value="save_exit" class="btn btn-success btn-xs submit"><i class="fa fa-save"></i> Save &amp; Exit</button>
                   <a class="btn btn-danger btn-xs" href="{{url('/admin/Users')}}"><i class="fa fa-times"></i> Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
@section('scripts')
    {{-- <script src="{{asset('resources/assets/common/js/jquery.validate.min.js')}}"></script> --}}
    {{-- <script src="{{asset('resources/assets/common/js/additional-methods.min.js')}}"></script> --}}
<script type="text/javascript">
     $(function(){
      $('.changePassword').css("display", "none");
  });

  $(document).on('change','#changePassword',function(){
      $('.changePassword').toggle();
  });

    var deletefunction = function(id){
        swal({
            title: "Are you sure you would like to move this User to trash?",
            text: "",
            // type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes",
            closeOnConfirm: false
        }).then(function() {
            window.location.href = '{{url('/')}}/admin/Users/trash/'+id;
        });
    };
   /* $(function()
    {
        $(".submitBtn").on("click",function()
        {
            $("#UserForm").submit();
        });
    });*/
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
            role_id:{
                required:true,
            },
            password:{
                required:true,
                minlength:8
            },
            password_confirmation:{
                minlength:8,
                required: true,
                equalTo : "#id_password",

            },

        },
        messages:{
            first_name:{
                required: 'First Name is required.',
                maxlength:'The first name may not be greater than 255 characters.'
            },
            last_name:{
                required: 'Last Name is required.',
                maxlength:'The last name may not be greaterr than 255 characters.'
            },
            role_id:{
                required:'Please select User Type',
            }
        },errorPlacement: function(error, element)
        {
            error.appendTo( element.parents('.form-group'));
            error.css('color','red');
        },submitHandler:function(form){
            $("#UserForm").submit();
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