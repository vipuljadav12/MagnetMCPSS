<div class="row editorBox">
	@include("Form::editor.title")
	<div class="col-12 m-t-5 editor-col-spaces p-10">
		<label class="m-b-5">Title Text</label>
		<input type="text" name="label" class="form-control editorInput" data-for="label" build-id="{{$build->id}}" value="{{getContentValue($build->id,"label") ?? ""}}">
	</div>
	@include("Form::editor.common")
</div>