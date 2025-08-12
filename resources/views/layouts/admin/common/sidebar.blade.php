<style>
    /* Sidebar Active States */
    #sidebar-menu .mm-active>a {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.1) !important;
    }

    #sidebar-menu .mm-active>a.active,
    #sidebar-menu a.active {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.15) !important;
        font-weight: 600;
    }

    #sidebar-menu ul.nav-second-level {
        display: none;
        /* background-color: rgba(0, 0, 0, 0.05); */
    }

    #sidebar-menu ul.nav-second-level.mm-show {
        display: block !important;
    }

    #sidebar-menu ul.nav-second-level li.mm-active>a,
    #sidebar-menu ul.nav-second-level li>a.active {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.15) !important;
        font-weight: 600;
    }

    #sidebar-menu ul.nav-second-level li.mm-active>a.active {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.25) !important;
        font-weight: 700;
    }

    /* Menu arrow rotation */
    #sidebar-menu .mm-active>a .menu-arrow {
        transform: rotate(90deg);
        transition: transform 0.2s ease;
    }

    #sidebar-menu a .menu-arrow {
        transition: transform 0.2s ease;
    }

    /* Nested submenu styling */
    #sidebar-menu ul.nav-second-level ul.nav-second-level {
        margin-left: 20px;
        border-left: 1px solid rgba(0, 0, 0, 0.1);
        /* background-color: rgba(0, 0, 0, 0.08); */
    }

    #sidebar-menu ul.nav-second-level ul.nav-second-level li a {
        padding-left: 30px;
        font-size: 13px;
    }

    #sidebar-menu ul.nav-second-level ul.nav-second-level li.mm-active>a,
    #sidebar-menu ul.nav-second-level ul.nav-second-level li>a.active {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.2) !important;
        font-weight: 600;
    }

    /* Third level nesting */
    #sidebar-menu ul.nav-second-level ul.nav-second-level ul.nav-second-level {
        margin-left: 15px;
        background-color: rgba(0, 0, 0, 0.1);
    }

    #sidebar-menu ul.nav-second-level ul.nav-second-level ul.nav-second-level li a {
        padding-left: 40px;
        font-size: 12px;
    }

    /* Hover effects */
    #sidebar-menu a:hover:not(.active) {
        background-color: rgba(0, 123, 255, 0.05) !important;
        color: #007bff !important;
    }

    #sidebar-menu ul.nav-second-level a:hover:not(.active) {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }

    #sidebar-menu ul.nav-second-level ul.nav-second-level a:hover:not(.active) {
        background-color: rgba(0, 123, 255, 0.15) !important;
    }

    /* Ensure active items maintain their styling on hover */
    #sidebar-menu a.active:hover,
    #sidebar-menu .mm-active>a.active:hover {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.2) !important;
    }

    /* Parent menu item styling when it has active children */
    #sidebar-menu li.mm-active>a:not(.active) {
        color: #495057 !important;
        background-color: rgba(0, 123, 255, 0.05) !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #sidebar-menu ul.nav-second-level {
            margin-left: 0;
        }

        #sidebar-menu ul.nav-second-level li a {
            padding-left: 20px;
        }

        #sidebar-menu ul.nav-second-level ul.nav-second-level li a {
            padding-left: 25px;
        }

        #sidebar-menu ul.nav-second-level ul.nav-second-level ul.nav-second-level li a {
            padding-left: 30px;
        }
    }

    /* Animation for submenu transitions */
    #sidebar-menu ul.nav-second-level {
        transition: all 0.2s ease-in-out;
    }

    /* Fix for menu items that should not have hover effects when active */
    #sidebar-menu li.mm-active>a.mm-active {
        color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.1) !important;
    }

    /* Visual indicators for nested menus */
    #sidebar-menu ul.nav-second-level>li>a .menu-arrow {
        font-size: 12px;
        opacity: 0.7;
    }

    #sidebar-menu ul.nav-second-level ul.nav-second-level>li>a .menu-arrow {
        font-size: 10px;
        opacity: 0.6;
    }
</style>
<div class="left side-menu" datasidebar-bg="theme05">
    <div class="">
        <div class="border-bottom d-flex align-items-center" style="height: 70px; background: #fff;">
            <div class="logo-box p-10 text-center"> <a href="{{ url('/admin/dashboard') }}" title=""
                    class="logo-dark h-100"><img class="img-fluid h-100"
                        src="{{ url('/resources/assets/admin/images/logo_mcps_1.jpg') }}" title=""
                        alt=""></a> </div>
            <div class="side-menu-btn text-primary"><i class="fa-2x fas fa-bars"></i></div>
        </div>
    </div>
    <div class="slimscroll-menu" id="remove-scroll">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul id="side-menu">
                <li class="{{ Request::is('admin/dashboard') ? 'mm-active' : '' }}">
                    <a title="Dashboard" href="{{ url('/admin/dashboard') }}"
                        class="{{ Request::is('admin/dashboard') ? 'active' : '' }}"><i
                            class="far fa-chart-bar"></i><span>Dashboard</span></a>
                </li>

                @if (checkPermission(Auth::user()->role_id, 'Submissions') == 1)
                    <li
                        class="{{ Request::is('admin/Submissions*') || Request::is('admin/CustomCommunication*') || Request::is('admin/GenerateApplicationData*') || Request::is('admin/GenerateApplicationData/contract*') || Request::is('admin/Reports/admin_review*') || Request::is('admin/Reports/missing/15/gradecdiupload*') ? 'mm-active' : '' }}">
                        <a title="Submission Workspace" href="javascript:void(0);"
                            class="{{ Request::is('admin/Submissions*') || Request::is('admin/CustomCommunication*') || Request::is('admin/GenerateApplicationData*') || Request::is('admin/GenerateApplicationData/contract*') || Request::is('admin/Reports/admin_review*') || Request::is('admin/Reports/missing/15/gradecdiupload*') ? 'mm-active' : '' }}"><i
                                class="far fa-address-card"></i><span>Submission Workspace</span> <span
                                class="menu-arrow"></span></a>
                        <ul class="nav-second-level {{ Request::is('admin/Submissions*') || Request::is('admin/CustomCommunication*') || Request::is('admin/GenerateApplicationData*') || Request::is('admin/GenerateApplicationData/contract*') || Request::is('admin/Reports/admin_review*') || Request::is('admin/Reports/missing/15/gradecdiupload*') ? 'mm-show' : '' }}"
                            aria-expanded="false">
                            <li
                                class="{{ Request::is('admin/Submissions') && !Request::is('admin/Submissions/*') ? 'mm-active' : '' }}">
                                <a title="Submissions" href="{{ url('/admin/Submissions') }}"
                                    class="{{ Request::is('admin/Submissions') && !Request::is('admin/Submissions/*') ? 'active' : '' }}">
                                    <span>Submissions</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('admin/CustomCommunication') ? 'mm-active' : '' }}"><a
                                    title="Custom Communication" href="{{ url('/admin/CustomCommunication') }}"
                                    class="{{ Request::is('admin/CustomCommunication*') ? 'active' : '' }}">
                                    <span>Custom Communications</span></a></li>
                            <li
                                class="{{ Request::is('admin/GenerateApplicationData') && !Request::is('admin/GenerateApplicationData/*') ? 'mm-active' : '' }}">
                                <a title="Generate Application Data" href="{{ url('/admin/GenerateApplicationData') }}"
                                    class="{{ Request::is('admin/GenerateApplicationData') && !Request::is('admin/GenerateApplicationData/*') ? 'active' : '' }}">
                                    <span>Generate Application Data Sheets</span></a>
                            </li>
                            <li class="{{ Request::is('admin/GenerateApplicationData/contract') ? 'mm-active' : '' }}">
                                <a title="Generate Parent Contracts"
                                    href="{{ url('/admin/GenerateApplicationData/contract') }}"
                                    class="{{ Request::is('admin/GenerateApplicationData/contract') ? 'active' : '' }}">
                                    <span>Generate Parent Contracts</span></a>
                            </li>
                            <li class="{{ Request::is('admin/Reports/admin_review*') ? 'mm-active' : '' }}"><a
                                    title="Admin Review" href="{{ url('/admin/Reports/admin_review') }}"
                                    class="{{ Request::is('admin/Reports/admin_review*') ? 'active' : '' }}">
                                    <span>Admin Review</span></a></li>
                            <li
                                class="{{ Request::is('admin/Reports/missing/15/gradecdiupload*') ? 'mm-active' : '' }}">
                                <a title="Parent Submitted Records"
                                    href="{{ url('/admin/Reports/missing/15/gradecdiupload') }}"
                                    class="{{ Request::is('admin/Reports/missing/15/gradecdiupload*') ? 'active' : '' }}">
                                    <span>Parent Submitted Records</span></a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if (checkPermission(Auth::user()->role_id, 'Enrollment') == 1)
                    <li class="{{ Request::is('admin/Enrollment*') ? 'mm-active' : '' }}"><a
                            title="Create New Enrollment Period" href="{{ url('/admin/Enrollment') }}"
                            class="{{ Request::is('admin/Enrollment*') ? 'active' : '' }}">
                            <i class="far fa-calendar-alt"></i><span>New Enrollment Period</span></a></li>
                @endif
                @if (checkPermission(Auth::user()->role_id, 'Application') == 1)
                    <li class="{{ Request::is('admin/Application*') ? 'mm-active' : '' }}"
                        class="{{ Request::is('admin/Application*') ? 'active' : '' }}"><a title="Setup Application"
                            href="{{ url('/admin/Application') }}"><i class="far fa-file-alt"></i>
                            <span>Setup Application</span></a></li>
                @endif
                @if (checkPermission(Auth::user()->role_id, 'SetEligibility') == 1)
                    <li class="{{ Request::is('admin/SetEligibility*') ? 'mm-active' : '' }}">
                        <a title="Set Eligibility Values" href="{{ url('/admin/SetEligibility') }}"
                            class="{{ Request::is('admin/SetEligibility*') ? 'active' : '' }}"><i
                                class="fa fa-tasks"></i><span>Set Eligibility Values</span></a>
                    </li>
                @endif
                @if (checkPermission(Auth::user()->role_id, 'Availability') == 1)
                    <li
                        class="{{ Request::is('admin/Availability*') || Request::is('admin/Process/Selection*') || Request::is('admin/Preliminary/Processing*') || Request::is('admin/EditCommunication*') || Request::is('admin/DistrictConfiguration/edit_text*') || Request::is('admin/DistrictConfiguration/edit_email*') ? 'mm-active' : '' }}">
                        <a title="Process Selection" href="javascript:void(0);"
                            class="{{ Request::is('admin/Availability*') || Request::is('admin/Process/Selection*') || Request::is('admin/Preliminary/Processing*') || Request::is('admin/EditCommunication*') || Request::is('admin/DistrictConfiguration/edit_text*') || Request::is('admin/DistrictConfiguration/edit_email*') ? 'mm-active' : '' }}"><i
                                class="fa fa-user-check"></i><span>Process Selection</span> <span
                                class="menu-arrow"></span></a>
                        <ul class="nav-second-level {{ Request::is('admin/Availability*') || Request::is('admin/Process/Selection*') || Request::is('admin/Preliminary/Processing*') || Request::is('admin/EditCommunication*') || Request::is('admin/DistrictConfiguration/edit_text*') || Request::is('admin/DistrictConfiguration/edit_email*') ? 'mm-show' : '' }}"
                            aria-expanded="false">
                            <li class="{{ Request::is('admin/Availability*') ? 'mm-active' : '' }}"><a
                                    title="Set Availability" href="{{ url('/admin/Availability') }}"
                                    class="{{ Request::is('admin/Availability*') ? 'active' : '' }}"><span>Set
                                        Availability</span></a></li>
                            <li class="{{ Request::is('admin/Process/Selection*') ? 'mm-active' : '' }}"><a
                                    title="Process Selection" href="{{ url('/admin/Process/Selection') }}"
                                    class="{{ Request::is('admin/Process/Selection*') ? 'active' : '' }}"><span>Run
                                        Selection</span></a></li>

                            <li class="{{ Request::is('admin/EditCommunication*') ? 'mm-active' : '' }}"><a
                                    title="Edit Communication" href="{{ url('/admin/EditCommunication') }}"
                                    class="{{ Request::is('admin/EditCommunication*') ? 'active' : '' }}"><span>Edit
                                        Communication</span></a>
                            </li>
                            <li class="{{ Request::is('admin/DistrictConfiguration/edit_text*') ? 'mm-active' : '' }}">
                                <a title="Edit Communication"
                                    href="{{ url('/admin/DistrictConfiguration/edit_text') }}"
                                    class="{{ Request::is('admin/DistrictConfiguration/edit_text*') ? 'active' : '' }}"><span>Edit
                                        Screen Text</span></a>
                            </li>
                            <li
                                class="{{ Request::is('admin/DistrictConfiguration/edit_email*') ? 'mm-active' : '' }}">
                                <a title="Edit Final Confirmation Email"
                                    href="{{ url('/admin/DistrictConfiguration/edit_email') }}"
                                    class="{{ Request::is('admin/DistrictConfiguration/edit_email*') ? 'active' : '' }}"><span>Edit
                                        Final Confirmation Email</span></a>
                            </li>

                        </ul>
                    </li>
                @endif

                <li
                    class="{{ Request::is('admin/Waitlist*') || Request::is('admin/DistrictConfiguration/edit_waitlist_text*') || Request::is('admin/DistrictConfiguration/edit_waitlist_email*') ? 'mm-active' : '' }}">
                    <a title="Process Selection" href="javascript:void(0);"
                        class="{{ Request::is('admin/Waitlist*') || Request::is('admin/DistrictConfiguration/edit_waitlist_text*') || Request::is('admin/DistrictConfiguration/edit_waitlist_email*') ? 'mm-active' : '' }}"><i
                            class="fa fa-user-cog"></i><span>Process Waitlist</span> <span
                            class="menu-arrow"></span></a>
                    <ul class="nav-second-level {{ Request::is('admin/Waitlist*') || Request::is('admin/DistrictConfiguration/edit_waitlist_text*') || Request::is('admin/DistrictConfiguration/edit_waitlist_email*') ? 'mm-show' : '' }}"
                        aria-expanded="false">
                        <li
                            class="{{ Request::is('admin/Waitlist') && !Request::is('admin/Waitlist/*') ? 'mm-active' : '' }}">
                            <a title="Process Waitlist" href="{{ url('/admin/Waitlist') }}"
                                class="{{ Request::is('admin/Waitlist') && !Request::is('admin/Waitlist/*') ? 'active' : '' }}"><span>Run
                                    Selection</span></a>
                        </li>
                        <li class="{{ Request::is('admin/Waitlist/EditCommunication*') ? 'mm-active' : '' }}"><a
                                title="Edit Communication" href="{{ url('/admin/Waitlist/EditCommunication') }}"
                                class="{{ Request::is('admin/Waitlist/EditCommunication*') ? 'active' : '' }}"><span>Edit
                                    Communication</span></a></li>
                        <li
                            class="{{ Request::is('admin/DistrictConfiguration/edit_waitlist_text*') ? 'mm-active' : '' }}">
                            <a title="Edit Screen Text"
                                href="{{ url('/admin/DistrictConfiguration/edit_waitlist_text') }}"
                                class="{{ Request::is('admin/DistrictConfiguration/edit_waitlist_text*') ? 'active' : '' }}"><span>Edit
                                    Screen
                                    Text</span></a>
                        </li>
                        <li
                            class="{{ Request::is('admin/DistrictConfiguration/edit_waitlist_email*') ? 'mm-active' : '' }}">
                            <a title="Edit Final Confirmation Email"
                                href="{{ url('/admin/DistrictConfiguration/edit_waitlist_email') }}"
                                class="{{ Request::is('admin/DistrictConfiguration/edit_waitlist_email*') ? 'active' : '' }}"><span>Edit
                                    Final
                                    Confirmation Email</span></a>
                        </li>
                        <!--<li class=""><a title="Population Changes Report" href="{{ url('/admin/Waitlist/Population/Version/0') }}"><span>Population Changes</span></a></li>
                         <li class=""><a title="Submission Results" href="{{ url('/admin/Waitlist/Submission/Result/Version/0') }}"><span>Submission Results</span></a></li>
                         <li class=""><a title="Seats Status" href="{{ url('/admin/Waitlist/Submission/SeatsStatus/Version/0') }}"><span>Seats Status</span></a></li> -->
                    </ul>
                </li>


                <li
                    class="{{ Request::is('admin/LateSubmission*') || Request::is('admin/DistrictConfiguration/edit_late_submission_text*') || Request::is('admin/DistrictConfiguration/edit_late_submission_email*') ? 'mm-active' : '' }}">
                    <a title="Process Selection" href="javascript:void(0);"
                        class="{{ Request::is('admin/LateSubmission*') || Request::is('admin/DistrictConfiguration/edit_late_submission_text*') || Request::is('admin/DistrictConfiguration/edit_late_submission_email*') ? 'mm-active' : '' }}"><i
                            class="fa fa-user-clock"></i><span>Process Late Submission</span> <span
                            class="menu-arrow"></span></a>
                    <ul class="nav-second-level {{ Request::is('admin/LateSubmission*') || Request::is('admin/DistrictConfiguration/edit_late_submission_text*') || Request::is('admin/DistrictConfiguration/edit_late_submission_email*') ? 'mm-show' : '' }}"
                        aria-expanded="false">
                        <li
                            class="{{ Request::is('admin/LateSubmission') && !Request::is('admin/LateSubmission/*') ? 'mm-active' : '' }}">
                            <a title="Process Waitlist" href="{{ url('/admin/LateSubmission') }}"
                                class="{{ Request::is('admin/LateSubmission') && !Request::is('admin/LateSubmission/*') ? 'active' : '' }}"><span>Run
                                    Selection</span></a>
                        </li>
                        <li class="{{ Request::is('admin/LateSubmission/EditCommunication*') ? 'mm-active' : '' }}"><a
                                title="Edit Communication"
                                href="{{ url('/admin/LateSubmission/EditCommunication') }}"
                                class="{{ Request::is('admin/LateSubmission/EditCommunication*') ? 'active' : '' }}"><span>Edit
                                    Communication</span></a></li>
                        <li
                            class="{{ Request::is('admin/DistrictConfiguration/edit_late_submission_text*') ? 'mm-active' : '' }}">
                            <a title="Edit Screen Text"
                                href="{{ url('/admin/DistrictConfiguration/edit_late_submission_text') }}"
                                class="{{ Request::is('admin/DistrictConfiguration/edit_late_submission_text*') ? 'active' : '' }}"><span>Edit
                                    Screen Text</span></a>
                        </li>
                        <li
                            class="{{ Request::is('admin/DistrictConfiguration/edit_late_submission_email*') ? 'mm-active' : '' }}">
                            <a title="Edit Final Confirmation Email"
                                href="{{ url('/admin/DistrictConfiguration/edit_late_submission_email') }}"
                                class="{{ Request::is('admin/DistrictConfiguration/edit_late_submission_email*') ? 'active' : '' }}"><span>Edit
                                    Final Confirmation Email</span></a>
                        </li>
                    </ul>
                </li>

                <!--<li class=""><a title="Front End" href="form.html"><i class="far fa-list-alt"></i><span>Forms</span></a></li>
                <li class=""><a title="Program" href="program.html"><i class="far fa-star"></i><span>Programs</span></a></li>
                <li class=""><a title="School" href="school.html"><i class="fas fa-school"></i><span>Schools</span></a></li>
                <li class=""><a title="Files" href="files.html"><i class="far fa-folder-open"></i><span>Files</span></a></li>
                            
                <li class=""><a title="Translation" href="translation.html"><i class="fas fa-language"></i><span>Translations</span></a></li>
                <li class=""><a title="Emails / Letters" href="javascript:void(0);"><i class="far fa-envelope"></i><span>Emails / Letters</span> <span class="menu-arrow"></span></a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a title="Applications" href="application-emails-letters.html"><span>Applications</span></a></li>
                        <li><a title="Program Processing" href="program-processing-emails-letters.html"><span>Program Processing</span></a></li>
                    </ul>
                </li>
               
                <li class=""><a title="Date" href="javascript:void(0);"><i class="far fa-calendar-alt"></i><span>Dates</span> <span class="menu-arrow"></span></a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a title="Application Dates" href="application-dates.html"><span>Application Dates</span></a></li>
                        <li><a title="Program Processing Dates" href="program-processing-dates.html"><span>Program Processing Dates</span></a></li>
                    </ul>
                </li>
                <li class=""><a title="Override" href="override.html"><i class="far fa-check-circle"></i><span>Overrides</span></a></li>
                <li class=""><a title="Report" href="report.html"><i class="fas fa-chart-pie"></i><span>Reports</span></a></li>
                -->
                @if (Auth::user()->role_id == 1)
                @endif
                <!--<li class=""><a title="Configuration" href="javascript:void(0);"><i class="fas fa-cog"></i><span>Configurations</span> <span class="menu-arrow"></span></a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li><a title="Header & Footer" href="header-footer-configuration.html"><span>Header &amp; Footer</span></a></li>
                        <li><a title="Eligibility" href="eligibility.html"><span>Eligibility</span></a></li>
                        <li><a title="Program Processing" href="program-processing-configuration.html"><span>Program Processing</span></a></li>
                    </ul>
                </li>-->
                @if (checkPermission(Auth::user()->role_id, 'GenerateApplicationData') == 1)
                    <!-- <li class=""><a title="Generate Application Data" href="{{ url('/admin/GenerateApplicationData') }}"><i class="fas fa-receipt"></i><span>Generate Application Data</span></a></li>-->
                @endif
                @if (checkPermission(Auth::user()->role_id, 'CustomCommunication') == 1)
                    <!-- <li class=""><a title="Custom Communication" href="{{ url('/admin/CustomCommunication') }}"><i class="fas fa-envelope"></i><span>Custom Communication</span></a></li> -->
                @endif

                @if (checkPermission(Auth::user()->role_id, 'Reports/missing/grade') == 1 ||
                        checkPermission(Auth::user()->role_id, 'Reports/missing/cdi') == 1)
                    <li class="{{ Request::is('admin/Reports/missing*') ? 'mm-active' : '' }}"><a title="Submissions"
                            href="{{ url('/admin/Reports/missing') }}"
                            class="{{ Request::is('admin/Reports/missing*') ? 'active' : '' }}"><i
                                class="far fa-file-alt"></i><span>Reports</span></a></li>
                @endif
                @if (checkPermission(Auth::user()->role_id, 'Configuration') == 1)
                    <li
                        class="{{ Request::is('admin/Reports/process/logs*') || Request::is('admin/Files*') || Request::is('admin/Configuration*') || Request::is('admin/AuditTrailData*') || Request::is('admin/DistrictConfiguration') || Request::is('admin/ZonedSchool/overrideAddress*') || Request::is('admin/Users*') || Request::is('admin/DistrictConfiguration/student/search*') ? 'mm-active' : '' }}">
                        <a title="Configuration" href="javascript:void(0);"
                            class="{{ Request::is('admin/Reports/process/logs*') || Request::is('admin/Files*') || Request::is('admin/Configuration*') || Request::is('admin/AuditTrailData*') || Request::is('admin/DistrictConfiguration') || Request::is('admin/ZonedSchool/overrideAddress*') || Request::is('admin/Users*') || Request::is('admin/DistrictConfiguration/student/search*') ? 'mm-active' : '' }}"><i
                                class="fas fa-cog"></i><span>Administration</span> <span
                                class="menu-arrow"></span></a>
                        <ul class="nav-second-level {{ Request::is('admin/Reports/process/logs*') || Request::is('admin/Files*') || Request::is('admin/Configuration*') || Request::is('admin/AuditTrailData*') || Request::is('admin/DistrictConfiguration') || Request::is('admin/ZonedSchool/overrideAddress*') || Request::is('admin/Users*') || Request::is('admin/DistrictConfiguration/student/search*') ? 'mm-show' : '' }}"
                            aria-expanded="false">
                            <!--<li><a title="Header & Footer" href="header-footer-configuration.html"><span>Header &amp; Footer</span></a></li>
                            <li><a title="Eligibility" href="eligibility.html"><span>Eligibility</span></a></li>
                            <li><a title="Program Processing" href="program-processing-configuration.html"><span>Program Processing</span></a></li>-->
                            <li class="{{ Request::is('admin/Reports/process/logs*') ? 'mm-active' : '' }}"><a
                                    title="Process Log Report" href="{{ url('/admin/Reports/process/logs') }}"
                                    class="{{ Request::is('admin/Reports/process/logs*') ? 'active' : '' }}"><span>Process
                                        Log
                                        Report</span></a></li>
                            <li class="{{ Request::is('admin/Files*') ? 'mm-active' : '' }}"><a
                                    title="Front Page Links" href="{{ url('/admin/Files') }}"
                                    class="{{ Request::is('admin/Files*') ? 'active' : '' }}"><span>Front Page
                                        Links</span></a></li>
                            <li class="{{ Request::is('admin/Configuration*') ? 'mm-active' : '' }}"><a
                                    title="Welcome Texts" href="{{ url('/admin/Configuration') }}"
                                    class="{{ Request::is('admin/Configuration*') ? 'active' : '' }}"><span>Texts</span></a>
                            </li>
                            <li class="{{ Request::is('admin/AuditTrailData*') ? 'mm-active' : '' }}"><a
                                    title="Audit Trails" href="{{ url('/admin/AuditTrailData') }}"
                                    class="{{ Request::is('admin/AuditTrailData*') ? 'active' : '' }}"><span>Audit
                                        Trail</span></a></li>
                            <li class="{{ Request::is('admin/DistrictConfiguration*') ? 'mm-active' : '' }}"><a
                                    title="Welcome Texts" href="{{ url('/admin/DistrictConfiguration') }}"
                                    class="{{ Request::is('admin/DistrictConfiguration*') ? 'active' : '' }}"><span>District
                                        Configuration</span></a></li>
                            <li class="{{ Request::is('admin/ZonedSchool/overrideAddress*') ? 'mm-active' : '' }}"><a
                                    title="Address Override" href="{{ url('/admin/ZonedSchool/overrideAddress') }}"
                                    class="{{ Request::is('admin/ZonedSchool/overrideAddress*') ? 'active' : '' }}"><span>Address
                                        Override</span></a></li>
                            <li
                                class="{{ Request::is('admin/DistrictConfiguration/student/search*') ? 'mm-active' : '' }}">
                                <a title="Address Override"
                                    href="{{ url('/admin/DistrictConfiguration/student/search') }}"
                                    class="{{ Request::is('admin/DistrictConfiguration/student/search*') ? 'active' : '' }}"><span>Student
                                        Search</span></a>
                            </li>
                            <li class="{{ Request::is('admin/Users*') ? 'mm-active' : '' }}"><a title="Users"
                                    href="{{ url('admin/Users') }}"
                                    class="{{ Request::is('admin/Users*') ? 'active' : '' }}"><span>Users</span></a>
                            </li>

                        </ul>
                    </li>
                @endif
                @if (Auth::user()->role_id == 1)
                    <li
                        class="master {{ Request::is('admin/District*') || Request::is('admin/Program*') || Request::is('admin/Eligibility*') || Request::is('admin/School*') || Request::is('admin/Priority*') || Request::is('admin/Reports') || Request::is('admin/Form*') || Request::is('admin/Role*') || Request::is('admin/StudentSearch*') ? 'mm-active' : '' }}">
                        <a title="" href="javascript:void(0);"
                            class="{{ Request::is('admin/District*') || Request::is('admin/Program*') || Request::is('admin/Eligibility*') || Request::is('admin/School*') || Request::is('admin/Priority*') || Request::is('admin/Reports') || Request::is('admin/Form*') || Request::is('admin/Role*') || Request::is('admin/StudentSearch*') ? 'mm-active' : '' }}"><i
                                class="far fa-gem"></i><span>Master</span> <span class="menu-arrow"></span></a>
                        <ul class="nav-second-level {{ Request::is('admin/District*') || Request::is('admin/Program*') || Request::is('admin/Eligibility*') || Request::is('admin/School*') || Request::is('admin/Priority*') || Request::is('admin/Reports') || Request::is('admin/Form*') || Request::is('admin/Role*') || Request::is('admin/StudentSearch*') ? 'mm-show' : '' }}"
                            aria-expanded="false">
                            @if (checkPermission(Auth::user()->role_id, 'District') == 1)
                                @if (Session::get('super_admin') == 'Y')
                                    <li class="{{ Request::is('admin/District*') ? 'mm-active' : '' }}"><a
                                            title="District Master" href="{{ url('/admin/District') }}"
                                            class="{{ Request::is('admin/District*') ? 'active' : '' }}"><span>District
                                                Master</span></a></li>
                                @endif
                            @endif
                            <li class="{{ Request::is('admin/Program*') ? 'mm-active' : '' }}"><a
                                    title="Program Master" href="{{ url('/admin/Program') }}"
                                    class="{{ Request::is('admin/Program*') ? 'active' : '' }}"><span>Program
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/Eligibility*') ? 'mm-active' : '' }}"><a
                                    title="Eligibility Master" href="{{ url('/admin/Eligibility') }}"
                                    class="{{ Request::is('admin/Eligibility*') ? 'active' : '' }}"><span>Eligibility
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/School*') ? 'mm-active' : '' }}"><a
                                    title="School Master" href="{{ url('/admin/School') }}"
                                    class="{{ Request::is('admin/School*') ? 'active' : '' }}"><span>School
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/Priority*') ? 'mm-active' : '' }}"><a
                                    title="Priority Master" href="{{ url('/admin/Priority') }}"
                                    class="{{ Request::is('admin/Priority*') ? 'active' : '' }}"><span>Priority
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/Form*') ? 'mm-active' : '' }}"><a title="Form Master"
                                    href="{{ url('/admin/Form') }}"
                                    class="{{ Request::is('admin/Form*') ? 'active' : '' }}"><span>Submissions Form
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/Reports*') ? 'mm-active' : '' }}"><a
                                    title="Report Master" href="{{ url('/admin/Reports') }}"
                                    class="{{ Request::is('admin/Reports*') ? 'active' : '' }}"><span>Selection
                                        Report
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/Role*') ? 'mm-active' : '' }}"><a
                                    title="Priority Master" href="{{ url('/admin/Role') }}"
                                    class="{{ Request::is('admin/Role*') ? 'active' : '' }}"><span>User Role
                                        Master</span></a></li>
                            <li class="{{ Request::is('admin/StudentSearch*') ? 'mm-active' : '' }}"><a
                                    title="Student Data Override" href="{{ url('') }}/admin/StudentSearch"
                                    class="{{ Request::is('admin/StudentSearch*') ? 'active' : '' }}"><span>Student
                                        Data
                                        Override</span></a></li>
                            <!--
                            <li><a title="Form Master" href="form-master.html"><span>Form Master</span></a></li>-->
                            <!--<li><a title="Program Master" href="program-master.html"><span>Program Master</span></a></li>
                            <li><a title="User Master" href="user-master.html"><span>User Master</span></a></li>
                           -->
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>
    </div>
    <!-- Sidebar -left -->
</div>
