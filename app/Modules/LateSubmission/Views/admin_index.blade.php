<!doctype html>
<html lang="en"><!-- InstanceBegin template="/Templates/grandmaster-master.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
    @include("layouts.admin.common.head")
    @yield("styles")
</head>
<body data-bg="theme01" class="dark-theme">
<div class="body-wrapper theme-customizer-page">
    <main datanavbar="sticky">
        <div class="main-wrapper">
            @include("layouts.admin.common.sidebar")
            @include("layouts.admin.common.header")
            <div class="content-wrapper">
                <div class="content-wrapper-in" style="min-height: 800px">
                        <iframe src="{{url('/Offers/'.$slug)}}" style="width: 100%; height: 500px;" frameborder="0" id="idIframe"></iframe>
                </div>
            </div>
        </div>
    </main>
</div>
<!-- InstanceBeginEditable name="Footer Extra Slot" --> <!-- InstanceEndEditable --> 
@include("layouts.admin.common.js")
@yield("scripts")

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd -->
</html>