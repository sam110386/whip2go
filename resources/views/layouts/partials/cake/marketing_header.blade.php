{{-- Port of Cake `app/View/Elements/header.ctp` --}}
<header>
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="{{ legacy_site_url() }}/">
                            <img src="{{ legacy_asset('img/logo.png') }}" alt="WHIP2GO">
                        </a>
                    </div>
                    <div class="collapse navbar-collapse" id="main-navbar">
                        <ul class="nav navbar-nav navbar-right">
                            @if(session()->has('userid'))
                                <li><a href="/users/dashboard" title="My Account">My Account</a></li>
                            @else
                                <li><a href="/logins/index" title="Login">Login</a></li>
                            @endif
                            <li><a href="/homes/aboutus" title="About Us">About Us</a></li>
                            <li><a href="/homes/drivers" title="Drivers">Drivers</a></li>
                            <li><a href="/homes/dealers" title="Dealers">Dealers</a></li>
                            <li><a href="/homes/featured" title="Featured">Featured</a></li>
                            <li><a href="/homes/contactus" title="Contact Us">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
