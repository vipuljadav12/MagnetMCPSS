<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Submission extends Model
{
    protected $table = 'submissions';
    public $additional = ['enrollment_id', 'application_id'];
    public $date_fields = ['birthday'];
    public $primaryKey = 'id';
    public $createField  = ['student_id', 'first_name', 'last_name', 'current_school', 'birthday', 'current_grade', 'next_grade', 'first_choice', 'second_choice'];
    public $fillable = [
        "student_id",
        "district_id",
        "enrollment_id",
        "state_id",
        "application_id",
        "form_id",
        "first_name",
        "last_name",
        "race",
        "gender",
        "birthday",
        "address",
        "city",
        "state",
        "zip",
        "current_school",
        "current_grade",
        "next_grade",
        'second_sibling',
        'first_sibling',
        "non_hsv_student",
        "special_accommodations",
        "parent_first_name",
        "parent_last_name",
        "parent_email",
        "emergency_contact",
        "emergency_contact_phone",
        "emergency_contact_relationship",
        "created_at",
        "updated_at",
        "phone_number",
        "alternate_number",
        "zoned_school",
        "lottery_number",
        "submission_status",
        "first_choice",
        "second_choice",
        "open_enrollment",
        "confirmation_no",
        "employee_id",
        "work_location",
        "mcp_employee",
        "employee_first_name",
        "employee_last_name",
        'grade_exists',
        'cdi_exists',
        'first_choice_program_id',
        'second_choice_program_id',
        'override_student',
        'cdi_override',
        'grade_override',
        'submitted_by',
        'late_submission'


    ];
}
