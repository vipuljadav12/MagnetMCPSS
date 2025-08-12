@extends('Offers::app')

@section('formstart')
        @php
            $contract_content = getContractConfiguration();
        @endphp
@endsection

@section('content')
    <div class="container">
        <div class="">
            <div class="p-20">
                <div class="text-center font-20 b-600 mb-40">{{ $contract_content->title_text ?? '' }}</div>
                <div class="">
                    <div class="row">
                        <div class="col-12 col-lg-5 mb-10">STUDENT NAME : <strong>Kelvin Holley</strong></div>
                        <div class="col-12 col-lg-5 mb-10">SELECTED SCHOOL : <strong>Barton Academy for Advanced World Studies</strong></div>
                        <div class="col-12 col-lg-2 mb-10 text-lg-right">GRADE : <strong>6</strong></div>
                    </div>
                    <div class="mb-10 text-justify">
                        {!! $contract_content->header_text ?? '' !!}
                    </div>
                    <div class="mb-10"><strong>Check Each :</strong></div>    
                    <div class="text-center font-20 b-600 mb-10">{{ $contract_content->title_text ?? '' }}</div>
                    <div class="text-justify">
                        @if(isset($contract_content->extra) && !empty($contract_content->extra))
                            @php
                                $conditions = $contract_content->extra;
                            @endphp

                            @if(isset($conditions))
                                @foreach($conditions['options']['title'] as $key=>$title)
                                    <div class="mb-10">
                                        <input type="checkbox" class="mr-10" id="{{ $key }}">
                                        <label for="{{ $key }}">{{ $title ?? ''}}</label>
                                        {{-- {{ $title ?? ''}} --}}

                                        @if(isset($conditions['options']['content'][$key]) && $conditions['options']['content'][$key] != '')
                                            <br>
                                            <em class="font-14">
                                                <label for="{{ $key }}">{!! $conditions['options']['content'][$key] !!}</label>
                                            </em>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        @else
                            {{-- <div class="mb-10"><input type="checkbox" class="mr-10"> I understand the school my child has been selected to attend is an open-zoned school of choice. <br><em class="font-14">This means my child has a zoned school of attendance for which he/she <strong>can</strong> attend, but I am <strong>choosing</strong> to place my child at the named magnet school which has a unique set of rules, policies, and procedures to which my child and I must adhere. Therefore, I will cooperate and work collaboratively with the school staff for the benefit and success of my child.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that each magnet school has uniform and dress guidelines which are unique to magnet schools. <br><em class="font-14">We expect our students to "dress for success!" By choosing to send my child to a MCPSS magnet school, I am choosing to adhere to the dress-code of my school of choice.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that magnet schools have grading and retention policies which differ from other MCPSS schools. <br><em class="font-14">Refer to the magnet grading scale: 90-100 = A, 80-89 = B, 70-79 = C, 69 &amp; Below = Does not meet magnet standards. Students who score less than a 70 on their final yearly average in any subject area will be required to repeat the grade at the magnet location or move to his or her zoned school of attendance for promotion opportunity.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand the importance of school attendance and its impact on academic success.<br><em class="font-14">Greater than five (5) unexcused absences and fifteen (15) unexcused check-in (tardies) or check-outs is considered excessive and may result in truancy violations and/or loss of privilege to return to the magnet program.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that all students deserve to learn in a safe, caring, and orderly environment free from distractions.<br><em class="font-14"><strong>Discipline criteria:</strong> Students with 3 or more suspensions, one suspension for 5 or more days, and/or any C,  D, or E offense may be recommended for removal from the magnet program immediately. Students who incur five (5) or more Class "B" offenses within an academic period will be removed from the magnet program for at least one full academic year.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that MCPSS Choice Schools are open-zoned schools of choice which means I am responsible for the transportation of my child to and from a school which may or may not be located near my home or work.<br><em class="font-14">I will abide by all rules and guidelines set forth by my child’s choice school regarding drop off and pick up including times, locations, carpool lines, walking, bus locations, etc. I will abide by the rules of my zoned school when dropping my student for magnet bus transportation (where applicable). I understand that violating these rules and guidelines can result in my child being removed from the school of choice.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that I must complete the registration process within the time-lines provided by my school and district.<br><em class="font-14">On-line and on-site registration requirements must be met according to times provided for the school year and a re- commitment may be required.</em></div>
                            <div class="mb-10"><input type="checkbox" class="mr-10"> I understand that my child’s continued enrollment at the selected school is not final until his/her final report card has been reviewed, all entrance and discipline criteria have been met, and on-line and on-site registration have been completed. In addition, if I choose to remove my child from the magnet program my child will not be eligible to attend a magnet school for at least one academic school year.</div> --}}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer')
    <footer class="mt-20"> 
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 mb-10 d-flex">Parent Signature : <div class="ml-20" style="width: 200px;"><input type="text" class="form-control" value="" name="contract_name" id="contract_name"></div></div>
                <div class="col-12 col-lg-4 mb-10 text-lg-right">Date : <strong>{{date("M d, Y")}}</strong></div>
            </div>
            <div class="mb-10">Parent Name - Printed : <strong>Tinika Roberson-Holley</strong></div>
            <div class="mb-10">Student Name - Printed : <strong>Kelvin Holley</strong></div>
            <div class="mb-10 text-right"><button type="button" class="btn btn-success btn-lg" onclick="alert('This functionality does not work in preview mode.')">Submit Contract</button></div>
        </div>
    </footer>
@endsection
@section('formend')
@endsection