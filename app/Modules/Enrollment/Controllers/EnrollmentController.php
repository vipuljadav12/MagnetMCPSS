<?php

namespace App\Modules\Enrollment\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Enrollment\Models\Enrollment;
use Session;
use App\Traits\AuditTrail;
use DB;

use App\Modules\Priority\Models\Priority;
use App\Modules\Priority\Models\PriorityDetail;
use App\Modules\Enrollment\Models\ADMData;
use App\Modules\School\Models\School;
use App\Modules\Program\Models\{Program, ProgramEligibility};
use App\Modules\Application\Models\{Application, ApplicationProgram, ApplicationConfiguration};
use App\Modules\SetEligibility\Models\SetEligibility;
use App\Modules\Eligibility\Models\{Eligibility, EligibilityContent, SubjectManagement};


class EnrollmentController extends Controller
{
    use AuditTrail;
    public function index()
    {   
        if(Session('district_id')==0){
            $enrollments = Enrollment::where('status', '!=', 'T')
                ->get();
        }else{
            $enrollments = Enrollment::where('district_id', Session('district_id'))
                ->where('status', '!=', 'T')
                ->get();
        }
        return view("Enrollment::index", compact('enrollments'));
    }

    public function create()
    {
        if(Session('district_id')!=0){
            return view("Enrollment::create");
        }
        return redirect('admin/Enrollment');
    }

    public function validationRules()
    { 
        $validation = new Enrollment();
        $validation->rules = [
            'school_year' => 'required|max:10|string|regex:/(^[\d]+[\-][\d]+$)/',
            // 'school_year' => 'required|max:10|string|regex:/^[0-9-]*$/',
            'confirmation_style' => 'required|max:30|string|regex:/[0-9a-z\s]+$/i',
            'import_grades_by' => 'required',
            'begning_date' => 'required|date',
            'ending_date' => 'required|date|after_or_equal:begning_date',
            'perk_birthday_cut_off' => 'required|date',
            'kindergarten_birthday_cut_off' => 'required|date',
            'first_grade_birthday_cut_off' => 'required|date',
        ];
        $validation->messages = [
            '*.required' => ':attribute is required.',
            '*.date' => 'Enter valid date.',
            'school_year.regex' => 'Enter valid school year.',
            'confirmation_style.regex' => 'Do not enter special characters.',
        ];
        return $validation;
    }

    public function store(Request $request)
    { 
        $validation = $this->validationRules();
        $this->validate($request, $validation->rules, $validation->messages);

        switch($request->import_grades_by){
            case 'submission_date':
                $request['import_grades_by'] = 'SD';
                break;
            default:
                $request['import_grades_by'] = '';
        }
        $req = $request->all();
        $req['begning_date'] = date("Y-m-d", strtotime($req['begning_date']));
        $req['ending_date'] = date("Y-m-d", strtotime($req['ending_date']));
        $req['first_grade_birthday_cut_off'] = date("Y-m-d", strtotime($req['first_grade_birthday_cut_off']));
        $req['kindergarten_birthday_cut_off'] = date("Y-m-d", strtotime($req['kindergarten_birthday_cut_off']));
        $req['perk_birthday_cut_off'] = date("Y-m-d", strtotime($req['perk_birthday_cut_off']));

        unset($req['exit']);
        unset($req['save_exit']);
        unset($req['_token']);
        unset($req['submit']);
        $req['district_id'] = Session('district_id');

        $last_enroll_id = Enrollment::where('status','!=','T')->where('district_id', \Session::get('district_id'))->latest('ending_date')->first(['id']);
        $enroll_id = Enrollment::create($req)->id;
        $storePriority = $this->createPriority($enroll_id,$last_enroll_id->id);
        $programs = $request->program;
        $program_ids_by_key_value = $this->createProgram($enroll_id,$programs,$last_enroll_id->id);

        // always call this function after creation of new programs.
        $application_ids_by_key_value = $this->createApplication($enroll_id, $last_enroll_id->id);

        $this->createEligibility($enroll_id, $last_enroll_id->id, $application_ids_by_key_value, $program_ids_by_key_value);
        $this->setSubjectManagement($enroll_id);

        if(isset($program_ids_by_key_value)){
            $newObj = Enrollment::where('id',$enroll_id)->first();
            $this->modelCreate($newObj,"enrollment");
            Session::flash('success', "Enrollment Period added successfully.");
        }else{
            Session::flash('success', "Enrollment Period not added.");
            return redirect('admin/Enrollment/create');
        }

        if (isset($request->save_exit))
        {
            return redirect('admin/Enrollment');
        }
        else
        {
            // return redirect()->back();
            return redirect("admin/Enrollment/edit/".$enroll_id);
        }
        return redirect('admin/Enrollment');
    }

    public function createEligibility($enroll_id, $last_enroll_id, $application_ids_by_key_value, $program_ids_by_key_value)
    {
        $eligibility_create_array = $old_eligibility_id = $new_eligibility_id = $old_eligibility_id_array = $eligibility_content_create_array = $eligibility_content_array = $set_eligibility_array = $set_eligibility_create_array = $set_program_eligibility_array = $set_program_eligibility_create_array = [];
        $old_eligibility = Eligibility::where('district_id',session('district_id'))->where('enrollment_id',$last_enroll_id)->get();

        if(isset($old_eligibility) && !empty($old_eligibility)){

            // Eligibility Clone start
            foreach($old_eligibility as $key=>$eligibility){
                $eligibility_array = $eligibility->replicate()->toArray();
                $eligibility_array['enrollment_id'] = $enroll_id;

                $old_eligibility_id_array[] = $eligibility->id;
                $eligibility_create_array[] = $eligibility_array;
            }
            Eligibility::insert($eligibility_create_array);
            // Eligibility Clone end
        }

        $new_eligibility = Eligibility::where('district_id',session('district_id'))->where('enrollment_id',$enroll_id)->get();

        // Get Old and New eligibility by key(old) and value(new) pair start.
        foreach ($old_eligibility as $o_key => $value) {
            $old_eligibility_id[] = $value->id;
        }
        foreach ($new_eligibility as $n_key => $value1) {
            $new_eligibility_id[] = $value1->id;
        }
        $eligibility_ids = array_combine($old_eligibility_id, $new_eligibility_id);
        // Get Old and New eligibility by key(old) and value(new) pair end.

        // Eligibility Content clone start.
        $eligibility_content = EligibilityContent::whereIn('eligibility_id',$old_eligibility_id_array)->get();
        if(isset($eligibility_content) && !empty($eligibility_content)){
            foreach ($eligibility_content as $c_key => $c_value) {
                $eligibility_content_array = $c_value->replicate()->toArray();
                $eligibility_content_array['eligibility_id'] = $eligibility_ids[$c_value->eligibility_id] ?? null;

                $eligibility_content_create_array[] = $eligibility_content_array;
            }
            EligibilityContent::insert($eligibility_content_create_array);
        }
        // Eligibility Content clone end.

        $old_application_ids_array = Application::where('district_id',session('district_id'))->where('enrollment_id',$last_enroll_id)->pluck('id');

        // SetEligibility clone start.
        $set_eligibility = SetEligibility::where('district_id',session('district_id'))->whereIn('application_id',$old_application_ids_array)->get();
        if(isset($set_eligibility) && !empty($set_eligibility)){
            foreach($set_eligibility as $key => $set_value){
                $set_eligibility_array = $set_value->replicate()->toArray();
                $set_eligibility_array['application_id'] = $application_ids_by_key_value[$set_value->application_id] ?? null;
                $set_eligibility_array['program_id'] = $program_ids_by_key_value[$set_value->program_id] ?? null;
                $set_eligibility_array['eligibility_id'] = $eligibility_ids[$set_value->eligibility_id] ?? null;

                $set_eligibility_create_array[] = $set_eligibility_array;
            }
            SetEligibility::insert($set_eligibility_create_array);
        }
        // SetEligibility clone end.

        // Set ProgramEligibility start
        $set_program_eligibility = ProgramEligibility::whereIn('application_id',$old_application_ids_array)->get();
        if(isset($set_program_eligibility) && !empty($set_program_eligibility)){
            foreach($set_program_eligibility as $key => $set_program_value){
                $set_program_eligibility_array = $set_program_value->replicate()->toArray();
                $set_program_eligibility_array['application_id'] = $application_ids_by_key_value[$set_program_value->application_id] ?? null;
                $set_program_eligibility_array['program_id'] = $program_ids_by_key_value[$set_program_value->program_id] ?? null;
                $set_program_eligibility_array['assigned_eigibility_name'] = $eligibility_ids[$set_program_value->assigned_eigibility_name] ?? null;

                $set_program_eligibility_create_array[] = $set_program_eligibility_array;
            }
            ProgramEligibility::insert($set_program_eligibility_create_array);
        }
        // Set ProgramEligibility end

        return 'success';
    }

    public function createApplication($enroll_id, $last_enroll_id)
    {
        $oldProgram_id = $newProgram_id = $old_application_id = $new_application_id = $application_ids = [];

        $oldPrograms = Program::where('district_id',session('district_id'))->where('enrollment_id',$last_enroll_id)->get();
        $newPrograms = Program::where('district_id',session('district_id'))->where('enrollment_id',$enroll_id)->get();

        foreach($oldPrograms as $key=>$value){
            $oldProgram_id[] = $value->id;
        }

        foreach($newPrograms as $key=>$value1){
            $newProgram_id[] = $value1->id;
        }

        $program_ids = array_combine($oldProgram_id, $newProgram_id);
        $old_applications = Application::where('district_id',session('district_id'))->where('enrollment_id',$last_enroll_id)->get();

        if(isset($program_ids) && !empty($program_ids) && isset($old_applications) && !empty($old_applications)){
            foreach($old_applications as $a_key => $application){

                // Application Clone start
                $application_array = $application->replicate()->toArray();
                $application_array['enrollment_id'] = $enroll_id;
                $application_id = Application::create($application_array)->id;
                // Application Clone end

                // Application Program Clone start
                $application_program_array = [];
                $application_program = ApplicationProgram::where('application_id',$application->id)->get();
                foreach($application_program as $app_prog_key => $app_prog_value){
                    $app_prog_array = $app_prog_value->replicate()->toArray();

                    $app_prog_array['application_id'] = $application_id;
                    $app_prog_array['program_id'] = $program_ids[$app_prog_value->program_id];

                    $application_program_array[] = $app_prog_array;
                }
                ApplicationProgram::insert($application_program_array);
                // Application Program Clone end

                // Application Configuration Clone start
                $applciation_config = ApplicationConfiguration::where('application_id',$application->id)->first();
                $app_config_array = $applciation_config->replicate()->toArray();
                $app_config_array['application_id'] = $application_id;

                ApplicationConfiguration::create($app_config_array);
                // Application Configuration Clone end
            }
        }

        $new_applications = Application::where('district_id',session('district_id'))->where('enrollment_id',$enroll_id)->get();        
        foreach($old_applications as $a_key=>$a_value){
            $old_application_id[] = $a_value->id;
        }

        foreach($new_applications as $n_key=>$n_value1){
            $new_application_id[] = $n_value1->id;
        }

        $application_ids = array_combine($old_application_id, $new_application_id);

        return $application_ids;
    }

    public function createProgram($enroll_id,$programs,$last_enroll_id)
    {

        $old_program_id = $new_program_id = $program_ids = [];
        $oldpriorities = Priority::where('district_id', session('district_id'))->where('status', '!=', 'T')->where('enrollment_id', $last_enroll_id)->get();
        $newpriorities = Priority::where('district_id', session('district_id'))->where('status', '!=', 'T')->where('enrollment_id',$enroll_id)->get();
        foreach ($oldpriorities as $key => $value) {
            $oldpriority_id[] = $value->id;
        }
        foreach ($newpriorities as $key => $value1) {
            $newpriority_id[] = $value1->id;
        }
        $priority_id = array_combine($oldpriority_id, $newpriority_id);
        $old_programs = Program::where("enrollment_id", $last_enroll_id)->where("district_id", session("district_id"))->get();
        
        foreach ($old_programs as $key => $program_id) {
            $programData = $program_id;//Program::where('id',$program_id)->first();
            $priority = strtr($programData->priority,$priority_id);
            $data=[
                'district_id' => Session::get('district_id'),
                'enrollment_id' => $enroll_id,
                'name' => $programData->name,
                'applicant_filter1'=> $programData->applicant_filter1,
                'applicant_filter2'=> $programData->applicant_filter2,
                'applicant_filter3'=> $programData->applicant_filter3,
                'grade_lavel'=> $programData->grade_lavel,
                'parent_submission_form' => $programData->parent_submission_form,
                'priority'=> $priority,
                'committee_score' => $programData->committee_score,
                'audition_score' => $programData->audition_score,
                'upload_program_check' => $programData->upload_program_check,
                'rating_priority'=>$programData->rating_priority,
                'lottery_number' => $programData->lottery_number,
                'combine_score' => $programData->combine_score,
                'final_score' => $programData->final_score,
                'selection_method' => $programData->selection_method,
                'selection_by' => $programData->selection_by,
                'seat_availability_enter_by' => $programData->seat_availability_enter_by,
                'basic_method_only' => $programData->basic_method_only,
                'combined_scoring' => $programData->combined_scoring,
                'combined_eligibility' => $programData->combined_eligibility,
                'magnet_school' => $programData->magnet_school,
                'sibling_enabled' => $programData->sibling_enabled,
                'silbling_check' => $programData->silbling_check,
                'existing_magnet_program_alert' => $programData->existing_magnet_program_alert,
                'exclude_grade_lavel' => $programData->exclude_grade_lavel,
                'feeder_priorities' => $programData->feeder_priorities,
                'magnet_priorities' => $programData->magnet_priorities,
                'current_over_new' => $programData->current_over_new,
                'basic_method_only_ls' => $programData->basic_method_only_ls,
                'combined_scoring_ls' => $programData->combined_scoring_ls,
                'combined_eligibility_ls' => $programData->combined_eligibility_ls,
                'sibling_schools' => $programData->sibling_schools,
                'feeder_field' => $programData->feeder_field,
                'upload_program_check' => $programData->upload_program_check,
                'status' => $programData->status,
            ];
            $result = Program::create($data);
        }

        $new_programs = Program::where("enrollment_id", $enroll_id)->where("district_id", session("district_id"))->get();
        foreach($old_programs as $p_key=>$p_value){
            $old_program_id[] = $p_value->id;
        }

        foreach($new_programs as $n_key=>$n_value1){
            $new_program_id[] = $n_value1->id;
        }

        $program_ids = array_combine($old_program_id, $new_program_id);

        return $program_ids;
    }

    public function createPriority($enroll_id,$last_enroll_id)
    {
        $priorities = Priority::where('district_id', session('district_id'))->where('status', '!=', 'T')->where('enrollment_id', $last_enroll_id)->get();

        foreach ($priorities as $key => $value) {
            $priorityData=[
                'district_id' => Session::get('district_id'),
                'enrollment_id' => $enroll_id,
                'name' => $value->name,
            ];
            $newpriority_id = Priority::create($priorityData)->id;
            $priorityDetails = PriorityDetail::where('priority_id',$value->id)->get();

            foreach ($priorityDetails as $key => $value1) {
                $priorityDetailsdetailsData = [
                    "priority_id" => $newpriority_id,
                    "description" => $value1->description,
                    "sibling" => $value1->sibling,
                    "majority_race_in_home_zone_school" => $value1->majority_race_in_home_zone_school,
                    "current_enrollment_at_another_magnet_school" => $value1->current_enrollment_at_another_magnet_school,
                    "employee_dependent" => $value1->employee_dependent,
                    "early_submission_priority" => $value1->early_submission_priority,
                    "feeder" => $value1->feeder,
                    "magnet_employee" => $value1->magnet_employee,
                    "current_over_new" => $value1->current_over_new,
                    "magnet_student" => $value1->magnet_student,
                    "sort" => $value1->sort,
                ];
                $newpriorityDetails = PriorityDetail::create($priorityDetailsdetailsData);
            }
        }
        return $newpriorityDetails;
    }

    public function edit($id)
    {
        $enrollment = Enrollment::where('id', $id)->first();
        if(isset($enrollment)){
            return view('Enrollment::edit', compact('enrollment'));    
        }
        return redirect('admin/Enrollment');
    }

    public function update(Request $request, $id)
    {
        $validation = $this->validationRules();
        $this->validate($request, $validation->rules, $validation->messages);

        // Selecting value for import_grades_by
        switch($request->import_grades_by){
            case 'submission_date':
                $request['import_grades_by'] = 'SD';
                break;
            default:
                $request['import_grades_by'] = '';
        }

        $req = $request->all();
        $req['begning_date'] = date("Y-m-d", strtotime($req['begning_date']));
        $req['ending_date'] = date("Y-m-d", strtotime($req['ending_date']));
        $req['first_grade_birthday_cut_off'] = date("Y-m-d", strtotime($req['first_grade_birthday_cut_off']));
        $req['kindergarten_birthday_cut_off'] = date("Y-m-d", strtotime($req['kindergarten_birthday_cut_off']));
        $req['perk_birthday_cut_off'] = date("Y-m-d", strtotime($req['perk_birthday_cut_off']));

        unset($req['exit']);
        unset($req['save_exit']);
        unset($req['_token']);
        unset($req['submit']);

        $initObj = Enrollment::where('id', $id)->first();
        $update = Enrollment::where('id', $id)
            ->update($req);
        $newObj = Enrollment::where('id', $id)->first();


        if(isset($update)){
            //$this->modelChanges($initObj,$newObj,"Enrollment");
            Session::flash('success', "Enrollment Period updated successfully.");
            if($request->has('exit')){
                return redirect('admin/Enrollment');
            }
        }else{
            Session::flash('success', "Enrollment Period not updated.");
            // return redirect('admin/Enrollment/edit/'.$id);
        }
        if (isset($request->save_exit))
        {
            return redirect('admin/Enrollment');
        }
        else
        {
            // return redirect()->back();
            return redirect("admin/Enrollment/edit/".$id);
        }
        return redirect('admin/Enrollment/edit/'.$id);
        // return redirect('admin/Enrollment');
    }

    public function updateStatus(Request $request)
    {
        $update = Enrollment::where('id', $request->id)
            ->update(['status' => $request->status]);
        if(isset($update)){
            return 'true';
        }
        return  'false';
    }

    public function trash()
    {   
        if(Session('district_id')==0){
            $enrollments = Enrollment::where('status', 'T')
                ->get();
        }else{
            $enrollments = Enrollment::where('district_id', Session('district_id'))
                ->where('status', 'T')
                ->get();    
        }
        return view('Enrollment::trash', compact('enrollments'));
    }

    public function moveToTrash($id)
    {
        $delete = Enrollment::where('id', $id)
            ->update(['status' => 'T']);
        if(isset($delete)){
            Session()->flash('success', "Enrollment Period moved to trash successfully"); 
        }
        else{
            Session()->flash('error', "Enrollment Period not moved to trash.");
        }
        return redirect('admin/Enrollment');
    }

    public function delete($id)
    {
        $delete = Enrollment::where('id', $id)
            ->delete();
        if(isset($delete)){
            Session()->flash('success', "Enrollment Period deleted successfully"); 
        }
        else{
            Session()->flash('error', "Enrollment Period not deleted.");
        }
        return redirect('admin/Enrollment/trash');
    }

    public function restore($id)
    {
        $restore = Enrollment::where('id', $id)
            ->update(['status' => 'Y']);
        if(isset($restore)){
            Session()->flash('success', "Enrollment Period restored successfully."); 
        }
        else{
            Session()->flash('error', "Enrollment Period not restored.");
        }
        return redirect('admin/Enrollment/trash');
    }

    public function removeEnrollment($id)
    {
        DB::statement("DELETE FROM enrollments WHERE id=".$id);
        DB::statement("DELETE FROM subject_management WHERE application_id IN (select id from application where enrollment_id=".$id.")");
        DB::statement("DELETE FROM set_eligibility WHERE application_id IN (select id from application where enrollment_id=".$id.")");
       // DB::statement("DELETE FROM set_eligibility_configuration WHERE application_id IN (select id from application where enrollment_id=".$id.")");
        DB::statement("DELETE FROM seteligibility_extravalue WHERE application_id IN (select id from application where enrollment_id=".$id.")");

        DB::statement("DELETE FROM application WHERE enrollment_id=".$id);

        DB::statement("DELETE FROM program_eligibility WHERE program_id IN (SELECT id from program WHERE enrollment_id = ".$id.")");
        DB::statement("DELETE FROM program WHERE enrollment_id=".$id);
       // DB::statement("DELETE FROM program_grade_mapping WHERE enrollment_id=".$id);

        
        DB::statement("DELETE FROM priority_details WHERE priority_id IN (select id from priorities where enrollment_id=".$id.")");
        DB::statement("DELETE FROM priorities WHERE enrollment_id=".$id);

        
        DB::statement("DELETE FROM eligibility_content WHERE eligibility_id IN (SELECT id from eligibiility where enrollment_id = ".$id.")");
        DB::statement("DELETE FROM eligibiility WHERE enrollment_id=".$id);
        Session()->flash('success', "Enrollment Period restored successfully."); 
        return redirect('admin/Enrollment');

    }

    public function setSubjectManagement($enroll_id) {
        $new_applications = Application::where('district_id',session('district_id'))->where('enrollment_id',$enroll_id)->get();
        $insert_ary = [];
        $grades = ['PreK', 'K', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        foreach ($new_applications as $application) {
            foreach ($grades as $grade) {
                $insert_ary[] = [
                    'application_id' => $application->id,
                    'grade' => $grade,
                    'english' => 'Y',
                    'reading' => 'Y',
                    'science' => 'Y',
                    'social_studies' => 'Y',
                    'math' => 'Y',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        if (!empty($insert_ary)) {
            SubjectManagement::insert($insert_ary);
        }
        return true;
    }
}
