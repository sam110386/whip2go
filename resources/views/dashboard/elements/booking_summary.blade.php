{{-- Cake `Elements/dashboard/booking_summary.ctp` --}}
<div class="row">
    <div class="col-md-4">
        <div class="panel text-center">
            <div class="panel-body">
                <div class="content-group-sm svg-center position-relative" id="hours-available-progress"><svg width="76" height="76">
                        <g transform="translate(38,38)">
                            <path class="d3-progress-background" d="M0,38A38,38 0 1,1 0,-38A38,38 0 1,1 0,38M0,36A36,36 0 1,0 0,-36A36,36 0 1,0 0,36Z" style="fill: rgb(238, 238, 238);"></path>
                            <path class="d3-progress-foreground" filter="url(#blur)" style="fill: #0e175f; stroke: #0e175f;" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.38342799370878,16.179613079472677L-32.57377388877674,15.328054496342538A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                            <path class="d3-progress-front" style="fill: #0e175f; fill-opacity: 1;" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.38342799370878,16.179613079472677L-32.57377388877674,15.328054496342538A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                        </g>
                    </svg>
                    <h2 class="mt-15 mb-5">{{ (int)($activeBooking ?? 0) }}</h2><i class="icon-car2 text-theme counter-icon" style="top: 22px"></i>
                    <div>Active Orders(s)</div>
                </div>
                <div id="hours-available-bars"><svg width="151.33334350585938" height="40">
                        <g width="251.33334350585938">
                            <rect class="d3-random-bars" width="3.783265039263439" x="1.6213993025414737" style="fill: #0e175f;" height="38" y="2"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="7.026063644346387" style="fill: #0e175f;" height="20" y="20"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="12.430727986151298" style="fill: #0e175f;" height="22" y="18"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="17.83539232795621" style="fill: #0e175f;" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="23.240056669761124" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="28.644721011566038" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="34.04938535337095" style="fill: #0e175f;" height="40" y="0"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="39.45404969517587" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="44.85871403698078" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="50.26337837878569" style="fill: #0e175f;" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="55.668042720590606" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="61.072707062395516" style="fill: #0e175f;" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="66.47737140420043" style="fill: #0e175f;" height="24" y="16"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="71.88203574600534" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="77.28670008781026" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="82.69136442961516" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="88.09602877142008" style="fill: #0e175f;" height="32" y="8"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="93.500693113225" style="fill: #0e175f;" height="32" y="8"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="98.9053574550299" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="104.31002179683482" style="fill: #0e175f;" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="109.71468613863973" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="115.11935048044464" style="fill: #0e175f;" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="120.52401482224955" style="fill: #0e175f;" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783265039263439" x="125.92867916405447" style="fill: #0e175f;" height="22" y="18"></rect>
                        </g>
                    </svg></div>
                <a class="heading-elements-toggle"><i class="icon-menu"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel text-center">
            <div class="panel-body">
                <div class="content-group-sm svg-center position-relative" id="goal-progress">
                    <svg width="76" height="76">
                        <g transform="translate(38,38)">
                            <path class="d3-progress-background" d="M0,38A38,38 0 1,1 0,-38A38,38 0 1,1 0,38M0,36A36,36 0 1,0 0,-36A36,36 0 1,0 0,36Z" style="fill: rgb(238, 238, 238);"></path>
                            <path class="d3-progress-foreground" filter="url(#blur)" style="fill: rgb(92, 107, 192); stroke: rgb(92, 107, 192);" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.3834279937087,-16.179613079472855L-32.573773888776664,-15.328054496342704A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                            <path class="d3-progress-front" style="fill: rgb(92, 107, 192); fill-opacity: 1;" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.3834279937087,-16.179613079472855L-32.573773888776664,-15.328054496342704A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                        </g>
                    </svg>
                    <h2 class="mt-15 mb-5">{{ (int)($pendingBooking ?? 0) }}</h2><i class="icon-car text-indigo-400 counter-icon" style="top: 22px"></i>
                    <div>Pending Orders(s)</div>
                </div>
                <div id="goal-bars"><svg width="131.3333282470703" height="40">
                        <g width="131.3333282470703">
                            <rect class="d3-random-bars" width="3.783264599709844" x="1.6213991141613617" style="fill: rgb(92, 107, 192);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="7.026062828032567" style="fill: rgb(92, 107, 192);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="12.430726541903773" style="fill: rgb(92, 107, 192);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="17.835390255774982" style="fill: rgb(92, 107, 192);" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="23.240053969646187" style="fill: rgb(92, 107, 192);" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="28.644717683517392" style="fill: rgb(92, 107, 192);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="34.0493813973886" style="fill: rgb(92, 107, 192);" height="20" y="20"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="39.4540451112598" style="fill: rgb(92, 107, 192);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="44.85870882513101" style="fill: rgb(92, 107, 192);" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="50.26337253900222" style="fill: rgb(92, 107, 192);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="55.66803625287342" style="fill: rgb(92, 107, 192);" height="38" y="2"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="61.07269996674463" style="fill: rgb(92, 107, 192);" height="40" y="0"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="66.47736368061584" style="fill: rgb(92, 107, 192);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="71.88202739448704" style="fill: rgb(92, 107, 192);" height="22" y="18"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="77.28669110835824" style="fill: rgb(92, 107, 192);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="82.69135482222946" style="fill: rgb(92, 107, 192);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="88.09601853610066" style="fill: rgb(92, 107, 192);" height="24" y="16"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="93.50068224997186" style="fill: rgb(92, 107, 192);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="98.90534596384308" style="fill: rgb(92, 107, 192);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="104.31000967771428" style="fill: rgb(92, 107, 192);" height="38" y="2"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="109.71467339158548" style="fill: rgb(92, 107, 192);" height="22" y="18"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="115.1193371054567" style="fill: rgb(92, 107, 192);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="120.5240008193279" style="fill: rgb(92, 107, 192);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="125.9286645331991" style="fill: rgb(92, 107, 192);" height="22" y="18"></rect>
                        </g>
                    </svg></div>
                <a class="heading-elements-toggle"><i class="icon-menu"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel text-center">
            <div class="panel-body">
                <div class="content-group-sm svg-center position-relative" id="goal-progress">
                    <svg width="76" height="76">
                        <g transform="translate(38,38)">
                            <path class="d3-progress-background" d="M0,38A38,38 0 1,1 0,-38A38,38 0 1,1 0,38M0,36A36,36 0 1,0 0,-36A36,36 0 1,0 0,36Z" style="fill: rgb(238, 238, 238);"></path>
                            <path class="d3-progress-foreground" filter="url(#blur)" style="fill: rgb(213, 9, 38); stroke: rgb(213, 9, 38);" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.3834279937087,-16.179613079472855L-32.573773888776664,-15.328054496342704A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                            <path class="d3-progress-front" style="fill: rgb(213, 9, 38); fill-opacity: 1;" d="M2.326828918379971e-15,-38A38,38 0 1,1 -34.3834279937087,-16.179613079472855L-32.573773888776664,-15.328054496342704A36,36 0 1,0 2.204364238465236e-15,-36Z"></path>
                        </g>
                    </svg>
                    <h2 class="mt-15 mb-5">{{ (int)($completed ?? 0) }}</h2><i class="icon-steering-wheel text-danger counter-icon" style="top: 22px"></i>
                    <div>Completed Bookings(s)</div>
                </div>
                <div id="goal-bars"><svg width="131.3333282470703" height="40">
                        <g width="131.3333282470703">
                            <rect class="d3-random-bars" width="3.783264599709844" x="1.6213991141613617" style="fill: rgb(213, 9, 38);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="7.026062828032567" style="fill: rgb(213, 9, 38);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="12.430726541903773" style="fill: rgb(213, 9, 38);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="17.835390255774982" style="fill: rgb(213, 9, 38);" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="23.240053969646187" style="fill: rgb(213, 9, 38);" height="28" y="12"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="28.644717683517392" style="fill: rgb(213, 9, 38);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="34.0493813973886" style="fill: rgb(213, 9, 38);" height="20" y="20"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="39.4540451112598" style="fill: rgb(213, 9, 38);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="44.85870882513101" style="fill: rgb(213, 9, 38);" height="34" y="6"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="50.26337253900222" style="fill: rgb(213, 9, 38);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="55.66803625287342" style="fill: rgb(213, 9, 38);" height="38" y="2"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="61.07269996674463" style="fill: rgb(213, 9, 38);" height="40" y="0"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="66.47736368061584" style="fill: rgb(213, 9, 38);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="71.88202739448704" style="fill: rgb(213, 9, 38);" height="22" y="18"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="77.28669110835824" style="fill: rgb(213, 9, 38);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="82.69135482222946" style="fill: rgb(213, 9, 38);" height="30" y="10"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="88.09601853610066" style="fill: rgb(213, 9, 38);" height="24" y="16"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="93.50068224997186" style="fill: rgb(213, 9, 38);" height="36" y="4"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="98.90534596384308" style="fill: rgb(213, 9, 38);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="104.31000967771428" style="fill: rgb(213, 9, 38);" height="38" y="2"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="109.71467339158548" style="fill: rgb(213, 9, 38);" height="22" y="18"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="115.1193371054567" style="fill: rgb(213, 9, 38);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="120.5240008193279" style="fill: rgb(213, 9, 38);" height="26" y="14"></rect>
                            <rect class="d3-random-bars" width="3.783264599709844" x="125.9286645331991" style="fill: rgb(213, 9, 38);" height="22" y="18"></rect>
                        </g>
                    </svg></div>
                <a class="heading-elements-toggle"><i class="icon-menu"></i></a>
            </div>
        </div>
    </div>
</div>
