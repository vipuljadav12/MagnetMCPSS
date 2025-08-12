
<div class="card-body">
    <div class=" mb-10">
        <div id="submission_filters" class="pull-left col-md-6 pl-0" style="float: left !important;"></div> 
    </div>
   
    @if(!empty($data['cdi_data']))
    <div class="table-responsive">
        <table class="table table-striped mb-0 {{-- w-100 --}}" id="datatable">
            <thead>
                <tr>
                    <th class="align-middle">Submission ID</th>
                    <th class="align-middle">State ID</th>
                    <th class="align-middle notexport">Student Type</th>
                    <th class="align-middle">Last Name</th>
                    <th class="align-middle">First Name</th>
                    <th class="align-middle">Enroll Status</th>
                    <th class="align-middle">Grade Level</th>
                    <th class="align-middle">Incident-Incident Title</th>
                    <th class="align-middle w-25">Incident-Incident Detail Desc</th>
                    <th class="align-middle">Incident-Location Details</th>
                    <th class="align-middle">Incident-Incident TS</th>
                    <th class="align-middle">Incident Detail-Lookup Code Desc</th>
                    <th class="align-middle">Incident LU Code-Code Type</th>
                    <th class="align-middle">Incident LU Code-Incident Category</th>
                    <th class="align-middle">Incident LU Code-State Aggregate Rpt Code</th>
                    <th class="align-middle">Incident LU Sub Code-Long Desc</th>
                    <th class="align-middle">Incident LU Sub Code-Short Desc</th>
                    <th class="align-middle">Incident LU Sub Code-Sub Category</th>
                    <th class="align-middle">Incident Object-Object Desc</th>
                    <th class="align-middle">Incident Object-Object Quantity</th>
                    <th class="align-middle">Incident Action-Action Actual Resolved Dt</th>
                    <th class="align-middle">Incident Action-Action Change Reason</th>
                    <th class="align-middle">Incident Action-Action Plan Begin Dt</th>
                    <th class="align-middle">Incident-Action-Action Plan End Dt</th>
                    <th class="align-middle">Incident Action-Action Resolved Desc</th>
                    <th class="align-middle">Incident Action Attribute-Text Attribute</th>

                </tr>
            </thead>
            <tbody>
                @foreach($data['cdi_data'] as $key=>$value)
                    <tr id="row">
                        <td>{{($value->submission_id ?? '')}}</td>
                        <td>{{($value->StateID ?? '')}}</td>
                        <td class="notexport">{{($value->StateID != "" ? "Current" : "Non-Current")}}</td>
                        <td>{{($value->first_name ?? '')}}</td>
                        <td>{{($value->last_name ?? '')}}</td>
                        <td>{{($value->enroll_status ?? '')}}</td>
                        <td>{{($value->grade_level ?? '')}}</td>
                        <td>{{($value->incident_title ?? '')}}</td>
                        <td class="text-justify">{{($value->incident_detail_desc ?? '')}}</td>
                        <td>{{($value->incident_location_details ?? '')}}</td>
                        <td>
                            @if($value->incident_datetime != '')
                                {{getDateTimeFormat($value->incident_datetime)}}
                            @endif
                        </td>
                        <td>{{($value->incident_detail_lookup_code_desc ?? '')}}</td>
                        <td>{{($value->incident_lu_code_code_type ?? '')}}</td>
                        <td>{{($value->incident_lu_code_incident_category ?? '')}}</td>
                        <td>{{($value->incident_lu_code_state_aggregate_rpt_code ?? '')}}</td>
                        <td>{{($value->incident_lu_sub_code_long_desc ?? '')}}</td>
                        <td>{{($value->incident_lu_sub_code_short_desc ?? '')}}</td>
                        <td>{{($value->incident_lu_sub_code_sub_category ?? '')}}</td>
                        <td>{{($value->incident_object_object_desc ?? '')}}</td>
                        <td>{{($value->incident_object_object_quantity ?? '')}}</td>
                        <td>
                            @if($value->incident_action_action_actual_resolved_dt!='')
                                {{getDateFormat($value->incident_action_action_actual_resolved_dt)}}
                            @endif
                        </td>
                        <td>{{($value->incident_action_action_change_reason ?? '')}}</td>
                        <td>
                            @if($value->incident_action_action_plan_begin_dt!='')
                                {{getDateFormat($value->incident_action_action_plan_begin_dt)}}
                            @endif
                        </td>
                        <td>
                            @if($value->incident_action_action_plan_end_dt!='')
                                {{getDateFormat($value->incident_action_action_plan_end_dt)}}
                            @endif
                        </td>
                        <td>{{($value->incident_action_action_resolved_desc ?? '')}}</td>
                        <td>{{($value->incident_action_attribute_text_attribute ?? '')}}</td>
                    </tr>
                @endforeach
                
            </tbody>
        </table>
    </div>
    @else
        <div class="table-responsive text-center"><p>No Records found.</div>
    @endif
</div>
