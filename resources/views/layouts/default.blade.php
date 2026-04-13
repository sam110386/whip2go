{{-- Cake `app/View/Layouts/default.ctp` — marketing site with header + footer. --}}
<!DOCTYPE html>
<html>
<head>
    @include('layouts.partials.cake.head_marketing_default')
</head>
<body>
@include('layouts.partials.cake.marketing_header')

@yield('content')

@include('layouts.partials.cake.marketing_footer')
<script type="text/javascript">
    $(function () {
        $("header nav.navbar").addClass("nav-dark");
        $("header nav.navbar").removeClass("navbar-fixed-top");
        $(window).on('scroll', function () {
            var scroll = $(window).scrollTop();
            if (scroll > 70) {
                $("header nav.navbar").addClass("navbar-fixed-top");
            } else {
                $("header nav.navbar").removeClass("navbar-fixed-top");
            }
        });
    });
</script>
@stack('scripts')
</body>
</html>
