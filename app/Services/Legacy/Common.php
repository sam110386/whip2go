<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use App\Models\Legacy\Admin;
use App\Models\Legacy\AdminRole;
use App\Models\Legacy\CsEavSetting;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Role;
use App\Models\Legacy\User;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsWorkingHour;
use App\Models\Legacy\CsTrackVehicle;
use App\Helpers\Legacy\Number as CakeNumber;
use App\Services\Legacy\TinnyUrlService;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Port of CakePHP app/Lib/Common.php
 */
class Common
{
    public function isUserExists($email, $userId = 0)
    {
        return User::where('id', '!=', $userId)
            ->where('email', $email)
            ->exists();
    }

    public function getRoleList()
    {
        return Role::orderBy('title', 'asc')->pluck('title', 'id')->toArray();
    }

    public function getAdminRoleList(): array
    {
        return AdminRole::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
    }

    public function getAdminEmail($adminId = null)
    {
        return Admin::where('id', $adminId ?: 1)
            ->first(['id', 'first_name', 'last_name', 'email']);
    }

    public function getAdminList()
    {
        return Admin::where('id', 1)
            ->get()
            ->mapWithKeys(function ($admin) {
                $name = ucfirst($admin->first_name) . ' ' . ucfirst($admin->last_name) . " ({$admin->email})";
                return [$admin->id => $name];
            })
            ->toArray();
    }

    public function generatePassword($length = 10)
    {
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $cons = [
            'b',
            'c',
            'd',
            'g',
            'h',
            'j',
            'k',
            'l',
            'm',
            'n',
            'p',
            'r',
            's',
            't',
            'u',
            'v',
            'w',
            'tr',
            'cr',
            'br',
            'fr',
            'th',
            'dr',
            'ch',
            'ph',
            'wr',
            'st',
            'sp',
            'sw',
            'pr',
            'sl',
            'cl'
        ];

        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $cons[array_rand($cons)];
            $password .= $vowels[array_rand($vowels)];

            if (strlen($password) >= $length) {
                break;
            }
        }

        return Str::substr($password, 0, $length);
    }

    public function getDifference($startDate, $endDate, $format = 6)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return match ($format) {
            1 => $start->diffInMinutes($end),
            2 => $start->diffInHours($end),
            3 => $start->diffInDays($end),
            4 => $start->diffInWeeks($end),
            5 => $start->diffInMonths($end),
            default => $start->diffInYears($end),
        };
    }

    public function get_time_zone()
    {
        return [
            "America/Eirunepe" => "Acre Standard Time (America/Eirunepe)",
            "America/Rio_Branco" => "Acre Standard Time (America/Rio_Branco)",
            "Asia/Kabul" => "Afghanistan Time (Asia/Kabul)",
            "America/Anchorage" => "Alaska Standard Time (America/Anchorage)",
            "America/Juneau" => "Alaska Standard Time (America/Juneau)",
            "America/Nome" => "Alaska Standard Time (America/Nome)",
            "America/Sitka" => "Alaska Standard Time (America/Sitka)",
            "America/Yakutat" => "Alaska Standard Time (America/Yakutat)",
            "America/Boa_Vista" => "Amazon Standard Time (America/Boa_Vista)",
            "America/Campo_Grande" => "Amazon Standard Time (America/Campo_Grande)",
            "America/Cuiaba" => "Amazon Standard Time (America/Cuiaba)",
            "America/Manaus" => "Amazon Standard Time (America/Manaus)",
            "America/Porto_Velho" => "Amazon Standard Time (America/Porto_Velho)",
            "Pacific/Apia" => "Apia Standard Time (Pacific/Apia)",
            "Asia/Aden" => "Arabian Standard Time (Asia/Aden)",
            "Asia/Baghdad" => "Arabian Standard Time (Asia/Baghdad)",
            "Asia/Bahrain" => "Arabian Standard Time (Asia/Bahrain)",
            "Asia/Kuwait" => "Arabian Standard Time (Asia/Kuwait)",
            "Asia/Qatar" => "Arabian Standard Time (Asia/Qatar)",
            "Asia/Riyadh" => "Arabian Standard Time (Asia/Riyadh)",
            "America/Argentina/Buenos_Aires" => "Argentina Standard Time (America/Argentina/Buenos_Aires)",
            "America/Argentina/Catamarca" => "Argentina Standard Time (America/Argentina/Catamarca)",
            "America/Argentina/Cordoba" => "Argentina Standard Time (America/Argentina/Cordoba)",
            "America/Argentina/Jujuy" => "Argentina Standard Time (America/Argentina/Jujuy)",
            "America/Argentina/La_Rioja" => "Argentina Standard Time (America/Argentina/La_Rioja)",
            "America/Argentina/Mendoza" => "Argentina Standard Time (America/Argentina/Mendoza)",
            "America/Argentina/Rio_Gallegos" => "Argentina Standard Time (America/Argentina/Rio_Gallegos)",
            "America/Argentina/Salta" => "Argentina Standard Time (America/Argentina/Salta)",
            "America/Argentina/San_Juan" => "Argentina Standard Time (America/Argentina/San_Juan)",
            "America/Argentina/Tucuman" => "Argentina Standard Time (America/Argentina/Tucuman)",
            "America/Argentina/Ushuaia" => "Argentina Standard Time (America/Argentina/Ushuaia)",
            "Asia/Yerevan" => "Armenia Standard Time (Asia/Yerevan)",
            "America/Anguilla" => "Atlantic Standard Time (America/Anguilla)",
            "America/Antigua" => "Atlantic Standard Time (America/Antigua)",
            "America/Aruba" => "Atlantic Standard Time (America/Aruba)",
            "America/Barbados" => "Atlantic Standard Time (America/Barbados)",
            "America/Blanc-Sablon" => "Atlantic Standard Time (America/Blanc-Sablon)",
            "America/Curacao" => "Atlantic Standard Time (America/Curacao)",
            "America/Dominica" => "Atlantic Standard Time (America/Dominica)",
            "America/Glace_Bay" => "Atlantic Standard Time (America/Glace_Bay)",
            "America/Goose_Bay" => "Atlantic Standard Time (America/Goose_Bay)",
            "America/Grand_Turk" => "Atlantic Standard Time (America/Grand_Turk)",
            "America/Grenada" => "Atlantic Standard Time (America/Grenada)",
            "America/Guadeloupe" => "Atlantic Standard Time (America/Guadeloupe)",
            "America/Halifax" => "Atlantic Standard Time (America/Halifax)",
            "America/Kralendijk" => "Atlantic Standard Time (America/Kralendijk)",
            "America/Lower_Princes" => "Atlantic Standard Time (America/Lower_Princes)",
            "America/Marigot" => "Atlantic Standard Time (America/Marigot)",
            "America/Martinique" => "Atlantic Standard Time (America/Martinique)",
            "America/Moncton" => "Atlantic Standard Time (America/Moncton)",
            "America/Montserrat" => "Atlantic Standard Time (America/Montserrat)",
            "America/Port_of_Spain" => "Atlantic Standard Time (America/Port_of_Spain)",
            "America/Puerto_Rico" => "Atlantic Standard Time (America/Puerto_Rico)",
            "America/Santo_Domingo" => "Atlantic Standard Time (America/Santo_Domingo)",
            "America/St_Barthelemy" => "Atlantic Standard Time (America/St_Barthelemy)",
            "America/St_Kitts" => "Atlantic Standard Time (America/St_Kitts)",
            "America/St_Lucia" => "Atlantic Standard Time (America/St_Lucia)",
            "America/St_Thomas" => "Atlantic Standard Time (America/St_Thomas)",
            "America/St_Vincent" => "Atlantic Standard Time (America/St_Vincent)",
            "America/Thule" => "Atlantic Standard Time (America/Thule)",
            "America/Tortola" => "Atlantic Standard Time (America/Tortola)",
            "Atlantic/Bermuda" => "Atlantic Standard Time (Atlantic/Bermuda)",
            "Australia/Adelaide" => "Australian Central Standard Time (Australia/Adelaide)",
            "Australia/Broken_Hill" => "Australian Central Standard Time (Australia/Broken_Hill)",
            "Australia/Darwin" => "Australian Central Standard Time (Australia/Darwin)",
            "Australia/Eucla" => "Australian Central Western Standard Time (Australia/Eucla)",
            "Australia/Brisbane" => "Australian Eastern Standard Time (Australia/Brisbane)",
            "Australia/Currie" => "Australian Eastern Standard Time (Australia/Currie)",
            "Australia/Hobart" => "Australian Eastern Standard Time (Australia/Hobart)",
            "Australia/Lindeman" => "Australian Eastern Standard Time (Australia/Lindeman)",
            "Australia/Melbourne" => "Australian Eastern Standard Time (Australia/Melbourne)",
            "Australia/Sydney" => "Australian Eastern Standard Time (Australia/Sydney)",
            "Antarctica/Casey" => "Australian Western Standard Time (Antarctica/Casey)",
            "Australia/Perth" => "Australian Western Standard Time (Australia/Perth)",
            "Asia/Baku" => "Azerbaijan Standard Time (Asia/Baku)",
            "Atlantic/Azores" => "Azores Standard Time (Atlantic/Azores)",
            "Asia/Dhaka" => "Bangladesh Standard Time (Asia/Dhaka)",
            "Asia/Thimphu" => "Bhutan Time (Asia/Thimphu)",
            "America/La_Paz" => "Bolivia Time (America/La_Paz)",
            "America/Araguaina" => "Brasilia Standard Time (America/Araguaina)",
            "America/Bahia" => "Brasilia Standard Time (America/Bahia)",
            "America/Belem" => "Brasilia Standard Time (America/Belem)",
            "America/Fortaleza" => "Brasilia Standard Time (America/Fortaleza)",
            "America/Maceio" => "Brasilia Standard Time (America/Maceio)",
            "America/Recife" => "Brasilia Standard Time (America/Recife)",
            "America/Santarem" => "Brasilia Standard Time (America/Santarem)",
            "America/Sao_Paulo" => "Brasilia Standard Time (America/Sao_Paulo)",
            "Asia/Brunei" => "Brunei Darussalam Time (Asia/Brunei)",
            "Atlantic/Cape_Verde" => "Cape Verde Standard Time (Atlantic/Cape_Verde)",
            "Africa/Blantyre" => "Central Africa Time (Africa/Blantyre)",
            "Africa/Bujumbura" => "Central Africa Time (Africa/Bujumbura)",
            "Africa/Gaborone" => "Central Africa Time (Africa/Gaborone)",
            "Africa/Harare" => "Central Africa Time (Africa/Harare)",
            "Africa/Kigali" => "Central Africa Time (Africa/Kigali)",
            "Africa/Lubumbashi" => "Central Africa Time (Africa/Lubumbashi)",
            "Africa/Lusaka" => "Central Africa Time (Africa/Lusaka)",
            "Africa/Maputo" => "Central Africa Time (Africa/Maputo)",
            "Africa/Algiers" => "Central European Standard Time (Africa/Algiers)",
            "Africa/Ceuta" => "Central European Standard Time (Africa/Ceuta)",
            "Africa/Tunis" => "Central European Standard Time (Africa/Tunis)",
            "Arctic/Longyearbyen" => "Central European Standard Time (Arctic/Longyearbyen)",
            "Europe/Amsterdam" => "Central European Standard Time (Europe/Amsterdam)",
            "Europe/Andorra" => "Central European Standard Time (Europe/Andorra)",
            "Europe/Belgrade" => "Central European Standard Time (Europe/Belgrade)",
            "Europe/Berlin" => "Central European Standard Time (Europe/Berlin)",
            "Europe/Bratislava" => "Central European Standard Time (Europe/Bratislava)",
            "Europe/Brussels" => "Central European Standard Time (Europe/Brussels)",
            "Europe/Budapest" => "Central European Standard Time (Europe/Budapest)",
            "Europe/Busingen" => "Central European Standard Time (Europe/Busingen)",
            "Europe/Copenhagen" => "Central European Standard Time (Europe/Copenhagen)",
            "Europe/Gibraltar" => "Central European Standard Time (Europe/Gibraltar)",
            "Europe/Ljubljana" => "Central European Standard Time (Europe/Ljubljana)",
            "Europe/Luxembourg" => "Central European Standard Time (Europe/Luxembourg)",
            "Europe/Madrid" => "Central European Standard Time (Europe/Madrid)",
            "Europe/Malta" => "Central European Standard Time (Europe/Malta)",
            "Europe/Monaco" => "Central European Standard Time (Europe/Monaco)",
            "Europe/Oslo" => "Central European Standard Time (Europe/Oslo)",
            "Europe/Paris" => "Central European Standard Time (Europe/Paris)",
            "Europe/Podgorica" => "Central European Standard Time (Europe/Podgorica)",
            "Europe/Prague" => "Central European Standard Time (Europe/Prague)",
            "Europe/Rome" => "Central European Standard Time (Europe/Rome)",
            "Europe/San_Marino" => "Central European Standard Time (Europe/San_Marino)",
            "Europe/Sarajevo" => "Central European Standard Time (Europe/Sarajevo)",
            "Europe/Skopje" => "Central European Standard Time (Europe/Skopje)",
            "Europe/Stockholm" => "Central European Standard Time (Europe/Stockholm)",
            "Europe/Tirane" => "Central European Standard Time (Europe/Tirane)",
            "Europe/Vaduz" => "Central European Standard Time (Europe/Vaduz)",
            "Europe/Vatican" => "Central European Standard Time (Europe/Vatican)",
            "Europe/Vienna" => "Central European Standard Time (Europe/Vienna)",
            "Europe/Warsaw" => "Central European Standard Time (Europe/Warsaw)",
            "Europe/Zagreb" => "Central European Standard Time (Europe/Zagreb)",
            "Europe/Zurich" => "Central European Standard Time (Europe/Zurich)",
            "Asia/Makassar" => "Central Indonesia Time (Asia/Makassar)",
            "America/Bahia_Banderas" => "Central Standard Time (America/Bahia_Banderas)",
            "America/Belize" => "Central Standard Time (America/Belize)",
            "America/Chicago" => "Central Standard Time (America/Chicago)",
            "America/Costa_Rica" => "Central Standard Time (America/Costa_Rica)",
            "America/El_Salvador" => "Central Standard Time (America/El_Salvador)",
            "America/Guatemala" => "Central Standard Time (America/Guatemala)",
            "America/Indiana/Knox" => "Central Standard Time (America/Indiana/Knox)",
            "America/Indiana/Tell_City" => "Central Standard Time (America/Indiana/Tell_City)",
            "America/Managua" => "Central Standard Time (America/Managua)",
            "America/Matamoros" => "Central Standard Time (America/Matamoros)",
            "America/Menominee" => "Central Standard Time (America/Menominee)",
            "America/Merida" => "Central Standard Time (America/Merida)",
            "America/Mexico_City" => "Central Standard Time (America/Mexico_City)",
            "America/Monterrey" => "Central Standard Time (America/Monterrey)",
            "America/North_Dakota/Beulah" => "Central Standard Time (America/North_Dakota/Beulah)",
            "America/North_Dakota/Center" => "Central Standard Time (America/North_Dakota/Center)",
            "America/North_Dakota/New_Salem" => "Central Standard Time (America/North_Dakota/New_Salem)",
            "America/Rainy_River" => "Central Standard Time (America/Rainy_River)",
            "America/Rankin_Inlet" => "Central Standard Time (America/Rankin_Inlet)",
            "America/Regina" => "Central Standard Time (America/Regina)",
            "America/Resolute" => "Central Standard Time (America/Resolute)",
            "America/Swift_Current" => "Central Standard Time (America/Swift_Current)",
            "America/Tegucigalpa" => "Central Standard Time (America/Tegucigalpa)",
            "America/Winnipeg" => "Central Standard Time (America/Winnipeg)",
            "Pacific/Guam" => "Chamorro Standard Time (Pacific/Guam)",
            "Pacific/Saipan" => "Chamorro Standard Time (Pacific/Saipan)",
            "Pacific/Chatham" => "Chatham Standard Time (Pacific/Chatham)",
            "America/Santiago" => "Chile Standard Time (America/Santiago)",
            "Antarctica/Palmer" => "Chile Standard Time (Antarctica/Palmer)",
            "Asia/Macau" => "China Standard Time (Asia/Macau)",
            "Asia/Shanghai" => "China Standard Time (Asia/Shanghai)",
            "Asia/Choibalsan" => "Choibalsan Standard Time (Asia/Choibalsan)",
            "Indian/Christmas" => "Christmas Island Time (Indian/Christmas)",
            "Pacific/Chuuk" => "Chuuk Time (Pacific/Chuuk)",
            "Indian/Cocos" => "Cocos Islands Time (Indian/Cocos)",
            "America/Bogota" => "Colombia Standard Time (America/Bogota)",
            "Pacific/Rarotonga" => "Cook Islands Standard Time (Pacific/Rarotonga)",
            "America/Havana" => "Cuba Standard Time (America/Havana)",
            "Antarctica/Davis" => "Davis Time (Antarctica/Davis)",
            "Antarctica/DumontDUrville" => "Dumont-d’Urville Time (Antarctica/DumontDUrville)",
            "Africa/Addis_Ababa" => "East Africa Time (Africa/Addis_Ababa)",
            "Africa/Asmara" => "East Africa Time (Africa/Asmara)",
            "Africa/Dar_es_Salaam" => "East Africa Time (Africa/Dar_es_Salaam)",
            "Africa/Djibouti" => "East Africa Time (Africa/Djibouti)",
            "Africa/Juba" => "East Africa Time (Africa/Juba)",
            "Africa/Kampala" => "East Africa Time (Africa/Kampala)",
            "Africa/Khartoum" => "East Africa Time (Africa/Khartoum)",
            "Africa/Mogadishu" => "East Africa Time (Africa/Mogadishu)",
            "Africa/Nairobi" => "East Africa Time (Africa/Nairobi)",
            "Indian/Antananarivo" => "East Africa Time (Indian/Antananarivo)",
            "Indian/Comoro" => "East Africa Time (Indian/Comoro)",
            "Indian/Mayotte" => "East Africa Time (Indian/Mayotte)",
            "America/Scoresbysund" => "East Greenland Standard Time (America/Scoresbysund)",
            "Asia/Almaty" => "East Kazakhstan Time (Asia/Almaty)",
            "Asia/Qyzylorda" => "East Kazakhstan Time (Asia/Qyzylorda)",
            "Asia/Dili" => "East Timor Time (Asia/Dili)",
            "Pacific/Easter" => "Easter Island Standard Time (Pacific/Easter)",
            "Africa/Cairo" => "Eastern European Standard Time (Africa/Cairo)",
            "Africa/Tripoli" => "Eastern European Standard Time (Africa/Tripoli)",
            "Asia/Amman" => "Eastern European Standard Time (Asia/Amman)",
            "Asia/Beirut" => "Eastern European Standard Time (Asia/Beirut)",
            "Asia/Damascus" => "Eastern European Standard Time (Asia/Damascus)",
            "Asia/Gaza" => "Eastern European Standard Time (Asia/Gaza)",
            "Asia/Hebron" => "Eastern European Standard Time (Asia/Hebron)",
            "Asia/Nicosia" => "Eastern European Standard Time (Asia/Nicosia)",
            "Europe/Athens" => "Eastern European Standard Time (Europe/Athens)",
            "Europe/Bucharest" => "Eastern European Standard Time (Europe/Bucharest)",
            "Europe/Chisinau" => "Eastern European Standard Time (Europe/Chisinau)",
            "Europe/Helsinki" => "Eastern European Standard Time (Europe/Helsinki)",
            "Europe/Istanbul" => "Eastern European Standard Time (Europe/Istanbul)",
            "Europe/Kaliningrad" => "Eastern European Standard Time (Europe/Kaliningrad)",
            "Europe/Kiev" => "Eastern European Standard Time (Europe/Kiev)",
            "Europe/Mariehamn" => "Eastern European Standard Time (Europe/Mariehamn)",
            "Europe/Riga" => "Eastern European Standard Time (Europe/Riga)",
            "Europe/Sofia" => "Eastern European Standard Time (Europe/Sofia)",
            "Europe/Tallinn" => "Eastern European Standard Time (Europe/Tallinn)",
            "Europe/Uzhgorod" => "Eastern European Standard Time (Europe/Uzhgorod)",
            "Europe/Vilnius" => "Eastern European Standard Time (Europe/Vilnius)",
            "Europe/Zaporozhye" => "Eastern European Standard Time (Europe/Zaporozhye)",
            "Asia/Jayapura" => "Eastern Indonesia Time (Asia/Jayapura)",
            "America/Atikokan" => "Eastern Standard Time (America/Atikokan)",
            "America/Cancun" => "Eastern Standard Time (America/Cancun)",
            "America/Cayman" => "Eastern Standard Time (America/Cayman)",
            "America/Detroit" => "Eastern Standard Time (America/Detroit)",
            "America/Indiana/Indianapolis" => "Eastern Standard Time (America/Indiana/Indianapolis)",
            "America/Indiana/Marengo" => "Eastern Standard Time (America/Indiana/Marengo)",
            "America/Indiana/Petersburg" => "Eastern Standard Time (America/Indiana/Petersburg)",
            "America/Indiana/Vevay" => "Eastern Standard Time (America/Indiana/Vevay)",
            "America/Indiana/Vincennes" => "Eastern Standard Time (America/Indiana/Vincennes)",
            "America/Indiana/Winamac" => "Eastern Standard Time (America/Indiana/Winamac)",
            "America/Iqaluit" => "Eastern Standard Time (America/Iqaluit)",
            "America/Jamaica" => "Eastern Standard Time (America/Jamaica)",
            "America/Kentucky/Louisville" => "Eastern Standard Time (America/Kentucky/Louisville)",
            "America/Kentucky/Monticello" => "Eastern Standard Time (America/Kentucky/Monticello)",
            "America/Nassau" => "Eastern Standard Time (America/Nassau)",
            "America/New_York" => "Eastern Standard Time (America/New_York)",
            "America/Nipigon" => "Eastern Standard Time (America/Nipigon)",
            "America/Panama" => "Eastern Standard Time (America/Panama)",
            "America/Pangnirtung" => "Eastern Standard Time (America/Pangnirtung)",
            "America/Port-au-Prince" => "Eastern Standard Time (America/Port-au-Prince)",
            "America/Thunder_Bay" => "Eastern Standard Time (America/Thunder_Bay)",
            "America/Toronto" => "Eastern Standard Time (America/Toronto)",
            "America/Guayaquil" => "Ecuador Time (America/Guayaquil)",
            "Atlantic/Stanley" => "Falkland Islands Standard Time (Atlantic/Stanley)",
            "America/Noronha" => "Fernando de Noronha Standard Time (America/Noronha)",
            "Pacific/Fiji" => "Fiji Standard Time (Pacific/Fiji)",
            "America/Cayenne" => "French Guiana Time (America/Cayenne)",
            "Indian/Kerguelen" => "French Southern &amp; Antarctic Time (Indian/Kerguelen)",
            "Europe/Minsk" => "Further-eastern European Time (Europe/Minsk)",
            "America/Fort_Nelson" => "GMT (America/Fort_Nelson)",
            "America/Punta_Arenas" => "GMT (America/Punta_Arenas)",
            "Asia/Atyrau" => "GMT (Asia/Atyrau)",
            "Asia/Barnaul" => "GMT (Asia/Barnaul)",
            "Asia/Famagusta" => "GMT (Asia/Famagusta)",
            "Asia/Qostanay" => "GMT (Asia/Qostanay)",
            "Asia/Tomsk" => "GMT (Asia/Tomsk)",
            "Asia/Yangon" => "GMT (Asia/Yangon)",
            "Europe/Astrakhan" => "GMT (Europe/Astrakhan)",
            "Europe/Kirov" => "GMT (Europe/Kirov)",
            "Europe/Saratov" => "GMT (Europe/Saratov)",
            "Europe/Ulyanovsk" => "GMT (Europe/Ulyanovsk)",
            "UTC" => "GMT (UTC)",
            "Asia/Urumqi" => "GMT+06:00 (Asia/Urumqi)",
            "Pacific/Galapagos" => "Galapagos Time (Pacific/Galapagos)",
            "Pacific/Gambier" => "Gambier Time (Pacific/Gambier)",
            "Asia/Tbilisi" => "Georgia Standard Time (Asia/Tbilisi)",
            "Pacific/Tarawa" => "Gilbert Islands Time (Pacific/Tarawa)",
            "Africa/Abidjan" => "Greenwich Mean Time (Africa/Abidjan)",
            "Africa/Accra" => "Greenwich Mean Time (Africa/Accra)",
            "Africa/Bamako" => "Greenwich Mean Time (Africa/Bamako)",
            "Africa/Banjul" => "Greenwich Mean Time (Africa/Banjul)",
            "Africa/Bissau" => "Greenwich Mean Time (Africa/Bissau)",
            "Africa/Conakry" => "Greenwich Mean Time (Africa/Conakry)",
            "Africa/Dakar" => "Greenwich Mean Time (Africa/Dakar)",
            "Africa/Freetown" => "Greenwich Mean Time (Africa/Freetown)",
            "Africa/Lome" => "Greenwich Mean Time (Africa/Lome)",
            "Africa/Monrovia" => "Greenwich Mean Time (Africa/Monrovia)",
            "Africa/Nouakchott" => "Greenwich Mean Time (Africa/Nouakchott)",
            "Africa/Ouagadougou" => "Greenwich Mean Time (Africa/Ouagadougou)",
            "Africa/Sao_Tome" => "Greenwich Mean Time (Africa/Sao_Tome)",
            "America/Danmarkshavn" => "Greenwich Mean Time (America/Danmarkshavn)",
            "Atlantic/Reykjavik" => "Greenwich Mean Time (Atlantic/Reykjavik)",
            "Atlantic/St_Helena" => "Greenwich Mean Time (Atlantic/St_Helena)",
            "Europe/Dublin" => "Greenwich Mean Time (Europe/Dublin)",
            "Europe/Guernsey" => "Greenwich Mean Time (Europe/Guernsey)",
            "Europe/Isle_of_Man" => "Greenwich Mean Time (Europe/Isle_of_Man)",
            "Europe/Jersey" => "Greenwich Mean Time (Europe/Jersey)",
            "Europe/London" => "Greenwich Mean Time (Europe/London)",
            "Asia/Dubai" => "Gulf Standard Time (Asia/Dubai)",
            "Asia/Muscat" => "Gulf Standard Time (Asia/Muscat)",
            "America/Guyana" => "Guyana Time (America/Guyana)",
            "America/Adak" => "Hawaii-Aleutian Standard Time (America/Adak)",
            "Pacific/Honolulu" => "Hawaii-Aleutian Standard Time (Pacific/Honolulu)",
            "Asia/Hong_Kong" => "Hong Kong Standard Time (Asia/Hong_Kong)",
            "Asia/Hovd" => "Hovd Standard Time (Asia/Hovd)",
            "Asia/Colombo" => "India Standard Time (Asia/Colombo)",
            "Asia/Kolkata" => "India Standard Time (Asia/Kolkata)",
            "Indian/Chagos" => "Indian Ocean Time (Indian/Chagos)",
            "Asia/Bangkok" => "Indochina Time (Asia/Bangkok)",
            "Asia/Ho_Chi_Minh" => "Indochina Time (Asia/Ho_Chi_Minh)",
            "Asia/Phnom_Penh" => "Indochina Time (Asia/Phnom_Penh)",
            "Asia/Vientiane" => "Indochina Time (Asia/Vientiane)",
            "Asia/Tehran" => "Iran Standard Time (Asia/Tehran)",
            "Asia/Irkutsk" => "Irkutsk Standard Time (Asia/Irkutsk)",
            "Asia/Jerusalem" => "Israel Standard Time (Asia/Jerusalem)",
            "Asia/Tokyo" => "Japan Standard Time (Asia/Tokyo)",
            "Asia/Pyongyang" => "Korean Standard Time (Asia/Pyongyang)",
            "Asia/Seoul" => "Korean Standard Time (Asia/Seoul)",
            "Pacific/Kosrae" => "Kosrae Time (Pacific/Kosrae)",
            "Asia/Krasnoyarsk" => "Krasnoyarsk Standard Time (Asia/Krasnoyarsk)",
            "Asia/Novokuznetsk" => "Krasnoyarsk Standard Time (Asia/Novokuznetsk)",
            "Asia/Bishkek" => "Kyrgystan Time (Asia/Bishkek)",
            "Pacific/Kiritimati" => "Line Islands Time (Pacific/Kiritimati)",
            "Australia/Lord_Howe" => "Lord Howe Standard Time (Australia/Lord_Howe)",
            "Antarctica/Macquarie" => "Macquarie Island Time (Antarctica/Macquarie)",
            "Asia/Anadyr" => "Magadan Standard Time (Asia/Anadyr)",
            "Asia/Magadan" => "Magadan Standard Time (Asia/Magadan)",
            "Asia/Kuala_Lumpur" => "Malaysia Time (Asia/Kuala_Lumpur)",
            "Asia/Kuching" => "Malaysia Time (Asia/Kuching)",
            "Indian/Maldives" => "Maldives Time (Indian/Maldives)",
            "Pacific/Marquesas" => "Marquesas Time (Pacific/Marquesas)",
            "Pacific/Kwajalein" => "Marshall Islands Time (Pacific/Kwajalein)",
            "Pacific/Majuro" => "Marshall Islands Time (Pacific/Majuro)",
            "Indian/Mauritius" => "Mauritius Standard Time (Indian/Mauritius)",
            "Antarctica/Mawson" => "Mawson Time (Antarctica/Mawson)",
            "America/Chihuahua" => "Mexican Pacific Standard Time (America/Chihuahua)",
            "America/Hermosillo" => "Mexican Pacific Standard Time (America/Hermosillo)",
            "America/Mazatlan" => "Mexican Pacific Standard Time (America/Mazatlan)",
            "Europe/Moscow" => "Moscow Standard Time (Europe/Moscow)",
            "Europe/Simferopol" => "Moscow Standard Time (Europe/Simferopol)",
            "Europe/Volgograd" => "Moscow Standard Time (Europe/Volgograd)",
            "America/Boise" => "Mountain Standard Time (America/Boise)",
            "America/Cambridge_Bay" => "Mountain Standard Time (America/Cambridge_Bay)",
            "America/Creston" => "Mountain Standard Time (America/Creston)",
            "America/Dawson_Creek" => "Mountain Standard Time (America/Dawson_Creek)",
            "America/Denver" => "Mountain Standard Time (America/Denver)",
            "America/Edmonton" => "Mountain Standard Time (America/Edmonton)",
            "America/Inuvik" => "Mountain Standard Time (America/Inuvik)",
            "America/Ojinaga" => "Mountain Standard Time (America/Ojinaga)",
            "America/Phoenix" => "Mountain Standard Time (America/Phoenix)",
            "America/Yellowknife" => "Mountain Standard Time (America/Yellowknife)",
            "Pacific/Nauru" => "Nauru Time (Pacific/Nauru)",
            "Asia/Kathmandu" => "Nepal Time (Asia/Kathmandu)",
            "Pacific/Noumea" => "New Caledonia Standard Time (Pacific/Noumea)",
            "Antarctica/McMurdo" => "New Zealand Standard Time (Antarctica/McMurdo)",
            "Pacific/Auckland" => "New Zealand Standard Time (Pacific/Auckland)",
            "America/St_Johns" => "Newfoundland Standard Time (America/St_Johns)",
            "Pacific/Niue" => "Niue Time (Pacific/Niue)",
            "Pacific/Norfolk" => "Norfolk Island Time (Pacific/Norfolk)",
            "Asia/Novosibirsk" => "Novosibirsk Standard Time (Asia/Novosibirsk)",
            "Asia/Omsk" => "Omsk Standard Time (Asia/Omsk)",
            "America/Dawson" => "Pacific Standard Time (America/Dawson)",
            "America/Los_Angeles" => "Pacific Standard Time (America/Los_Angeles)",
            "America/Metlakatla" => "Pacific Standard Time (America/Metlakatla)",
            "America/Tijuana" => "Pacific Standard Time (America/Tijuana)",
            "America/Vancouver" => "Pacific Standard Time (America/Vancouver)",
            "America/Whitehorse" => "Pacific Standard Time (America/Whitehorse)",
            "Asia/Karachi" => "Pakistan Standard Time (Asia/Karachi)",
            "Pacific/Palau" => "Palau Time (Pacific/Palau)",
            "Pacific/Port_Moresby" => "Papua New Guinea Time (Pacific/Port_Moresby)",
            "America/Asuncion" => "Paraguay Standard Time (America/Asuncion)",
            "America/Lima" => "Peru Standard Time (America/Lima)",
            "Asia/Kamchatka" => "Petropavlovsk-Kamchatski Standard Time (Asia/Kamchatka)",
            "Asia/Manila" => "Philippine Standard Time (Asia/Manila)",
            "Pacific/Enderbury" => "Phoenix Islands Time (Pacific/Enderbury)",
            "Pacific/Pitcairn" => "Pitcairn Time (Pacific/Pitcairn)",
            "Pacific/Pohnpei" => "Ponape Time (Pacific/Pohnpei)",
            "Indian/Reunion" => "Reunion Time (Indian/Reunion)",
            "Antarctica/Rothera" => "Rothera Time (Antarctica/Rothera)",
            "Asia/Sakhalin" => "Sakhalin Standard Time (Asia/Sakhalin)",
            "Europe/Samara" => "Samara Standard Time (Europe/Samara)",
            "Pacific/Midway" => "Samoa Standard Time (Pacific/Midway)",
            "Pacific/Pago_Pago" => "Samoa Standard Time (Pacific/Pago_Pago)",
            "Indian/Mahe" => "Seychelles Time (Indian/Mahe)",
            "Asia/Singapore" => "Singapore Standard Time (Asia/Singapore)",
            "Pacific/Guadalcanal" => "Solomon Islands Time (Pacific/Guadalcanal)",
            "Africa/Johannesburg" => "South Africa Standard Time (Africa/Johannesburg)",
            "Africa/Maseru" => "South Africa Standard Time (Africa/Maseru)",
            "Africa/Mbabane" => "South Africa Standard Time (Africa/Mbabane)",
            "Atlantic/South_Georgia" => "South Georgia Time (Atlantic/South_Georgia)",
            "America/Miquelon" => "St. Pierre &amp; Miquelon Standard Time (America/Miquelon)",
            "America/Paramaribo" => "Suriname Time (America/Paramaribo)",
            "Antarctica/Syowa" => "Syowa Time (Antarctica/Syowa)",
            "Pacific/Tahiti" => "Tahiti Time (Pacific/Tahiti)",
            "Asia/Taipei" => "Taipei Standard Time (Asia/Taipei)",
            "Asia/Dushanbe" => "Tajikistan Time (Asia/Dushanbe)",
            "Pacific/Fakaofo" => "Tokelau Time (Pacific/Fakaofo)",
            "Pacific/Tongatapu" => "Tonga Standard Time (Pacific/Tongatapu)",
            "Asia/Ashgabat" => "Turkmenistan Standard Time (Asia/Ashgabat)",
            "Pacific/Funafuti" => "Tuvalu Time (Pacific/Funafuti)",
            "Asia/Ulaanbaatar" => "Ulan Bator Standard Time (Asia/Ulaanbaatar)",
            "America/Montevideo" => "Uruguay Standard Time (America/Montevideo)",
            "Asia/Samarkand" => "Uzbekistan Standard Time (Asia/Samarkand)",
            "Asia/Tashkent" => "Uzbekistan Standard Time (Asia/Tashkent)",
            "Pacific/Efate" => "Vanuatu Standard Time (Pacific/Efate)",
            "America/Caracas" => "Venezuela Time (America/Caracas)",
            "Asia/Ust-Nera" => "Vladivostok Standard Time (Asia/Ust-Nera)",
            "Asia/Vladivostok" => "Vladivostok Standard Time (Asia/Vladivostok)",
            "Antarctica/Vostok" => "Vostok Time (Antarctica/Vostok)",
            "Pacific/Wake" => "Wake Island Time (Pacific/Wake)",
            "Pacific/Wallis" => "Wallis &amp; Futuna Time (Pacific/Wallis)",
            "Africa/Bangui" => "West Africa Standard Time (Africa/Bangui)",
            "Africa/Brazzaville" => "West Africa Standard Time (Africa/Brazzaville)",
            "Africa/Douala" => "West Africa Standard Time (Africa/Douala)",
            "Africa/Kinshasa" => "West Africa Standard Time (Africa/Kinshasa)",
            "Africa/Lagos" => "West Africa Standard Time (Africa/Lagos)",
            "Africa/Libreville" => "West Africa Standard Time (Africa/Libreville)",
            "Africa/Luanda" => "West Africa Standard Time (Africa/Luanda)",
            "Africa/Malabo" => "West Africa Standard Time (Africa/Malabo)",
            "Africa/Ndjamena" => "West Africa Standard Time (Africa/Ndjamena)",
            "Africa/Niamey" => "West Africa Standard Time (Africa/Niamey)",
            "Africa/Porto-Novo" => "West Africa Standard Time (Africa/Porto-Novo)",
            "Africa/Windhoek" => "West Africa Standard Time (Africa/Windhoek)",
            "America/Godthab" => "West Greenland Standard Time (America/Godthab)",
            "Asia/Aqtau" => "West Kazakhstan Time (Asia/Aqtau)",
            "Asia/Aqtobe" => "West Kazakhstan Time (Asia/Aqtobe)",
            "Asia/Oral" => "West Kazakhstan Time (Asia/Oral)",
            "America/Argentina/San_Luis" => "Western Argentina Standard Time (America/Argentina/San_Luis)",
            "Africa/Casablanca" => "Western European Standard Time (Africa/Casablanca)",
            "Africa/El_Aaiun" => "Western European Standard Time (Africa/El_Aaiun)",
            "Atlantic/Canary" => "Western European Standard Time (Atlantic/Canary)",
            "Atlantic/Faroe" => "Western European Standard Time (Atlantic/Faroe)",
            "Atlantic/Madeira" => "Western European Standard Time (Atlantic/Madeira)",
            "Europe/Lisbon" => "Western European Standard Time (Europe/Lisbon)",
            "Asia/Jakarta" => "Western Indonesia Time (Asia/Jakarta)",
            "Asia/Pontianak" => "Western Indonesia Time (Asia/Pontianak)",
            "Asia/Khandyga" => "Yakutsk Standard Time (Asia/Khandyga)",
            "Asia/Yakutsk" => "Yakutsk Standard Time (Asia/Yakutsk)",
            "Asia/Yekaterinburg" => "Yekaterinburg Standard Time (Asia/Yekaterinburg)"
        ];
    }

    public function getVinDetails($vin)
    {
        $vininfo = [
            'make' => "",
            'year' => "",
            'model' => "",
            'transmission' => "M",
            'cab_type' => "Sedan"
        ];

        if (!empty($vin) && strlen($vin) == 17) {
            $apiUrl = config('legacy.vindecoder.apiUrl', 'https://api.vindecoder.eu/3.2');
            $apiKey = config('legacy.vindecoder.apiKey');
            $apiSecret = config('legacy.vindecoder.apiSecret');
            $id = "decode";
            $controlsum = substr(sha1("{$vin}|{$id}|{$apiKey}|{$apiSecret}"), 0, 10);
            $url = "{$apiUrl}/{$apiKey}/{$controlsum}/decode/{$vin}.json";

            $response = Http::withoutVerifying()->get($url);

            if ($response->successful() && isset($response['decode'])) {
                foreach ($response['decode'] as $val) {
                    switch ($val['label']) {
                        case 'Make':
                            $vininfo['make'] = $val['value'];
                            break;
                        case 'Model Year':
                            $vininfo['year'] = $val['value'];
                            break;
                        case 'Model':
                            $vininfo['model'] = $val['value'];
                            break;
                        case 'Transmission':
                            $vininfo['transmission'] = strtoupper(substr($val['value'], 0, 1));
                            break;
                        case 'Body':
                            $vininfo['cab_type'] = $this->getVinBody($val['value']);
                            break;
                    }
                }
            }

        }

        return $vininfo;
    }

    public function getVinBody($body)
    {
        $body = trim($body);
        switch ($body) {
            case "Bus with front engine":
            case "Bus with rear engine":
            case "City ??bus":
            case "Suburban bus":
            case "Intercity bus for short paths":
            case "Bus for long journeys":
            case "Trolley bus":
            case "Bus":
                return "Bus";
            case "Truck Extended Cab 4x2":
            case "Truck Extended Cab 4x4":
            case "Truck cab 4x2":
            case "Truck cab 4x4":
            case "Truck Extended Cab 6x4 or 6x2":
            case "Truck Extended Cab 6x6":
            case "Truck cab 6x4 or 6x2":
            case "Truck cab 6x6":
            case "Truck Extended Cab 8x2 or 8x4 or 8x6 or 8x8":
            case "Semi-trailer or tractor cab- 4x2":
            case "Semi-trailer or tractor cab- 4x4":
            case "Semi-trailer or tractor cab 4x2":
            case "Semi-trailer or tractor cab 4x4":
            case "Semi-trailer or tractor cab- 6x4 or 6x2":
            case "Semi-trailer or tractor cab- 6x6":
            case "Semi-trailer or tractor cab 6x4 or 6x2":
            case "Semi-trailer or tractor cab 6x6":
            case "Semi-trailer or tractor cab 4 axes":
            case "Reserved for experimental vehicles":
            case "Special Vehicles (amphibious, armored vehicle)":
            case "Armored":
            case "Pickup":
            case "Truck":
            case "Multi Purpose Vehicle (MPV)":
            case "Recreational vehicle (RV)":
            case "Trailer":
            case "Trailer < 750 kg":
            case "Liftback":
            case "Roadster":
            case "Trailer > 750 kg":
            case "Truck - Tractor":
                return "Pickup Truck";
            case "Cabrio":
            case "Cabriolet/Convertible":
                return "Convertible";
            case "Coupe":
                return "Coupe";
            case "XC90":
                return "Van";
            case "Sedan":
            case "Sedan/Saloon":
            case "Streetcar":
                return "Sedan";
            case "Hatchback":
                return "Hatchback";
            case "XC/CC (V40), XC90 7 seats":
            case "Van":
            case "Cargo Van":
            case "Tipper":
            case "Refrigerated Cargo Van":
            case "Tow Truck":
            case "Camper":
            case "Step Van":
                return "Van";
            case "Sport Utility Vehicle (SUV)":
            case "Wagon":
                return "SUV";
            case "Minivan":
                return "Minivan";
            case "Motorcycle - Street":
            case "Motorcycle - Chopper":
            case "Motorcycle - Scooter":
            case "Motorcycle - Dual Sport / Adventure / Supermoto / On/Off-Road":
            case "Motorcycle - Custom":
            case "Quad - All Terrain Vehicle (ATV)":
            case "Motorcycle - Touring / Sport Touring":
            case "Motorcycle - Trike":
            case "Motorcycle - Motocross":
            case "Snowmobile":
            case "Motorcycle - Cruiser":
            case "Motorcycle - Sport":
            case "Motorcycle - Enduro (Off-road long distance racing)":
            case "Motorcycle - Naked":
            case "Motorcycle - Dirt Bike / Off-Road":
            case "Electro-moto":
            case "Motorcycle - All Terrain Cycle (ATV)":
            case "Crossover Utility Vehicle (CUV)":
            case "Motorcycle - Small / Minibike":
            case "Motorcycle - Standard":
            case "Motorcycle - Side Car":
            case "Motorcycle - Competition":
            case "Motorcycle - Cross Country":
            case "Motorcycle - Enclosed Three Wheeled / Enclosed Autocycle":
            case "Motorcycle - Go Kart":
            case "Motorcycle - Moped":
            case "Motorcycle - Motocross (Off-road short distance, closed track racing)":
            case "Motorcycle - Underbone":
            case "Motorcycle - Unenclosed Three Wheeled / Open Autocycle":
            case "Motorhome":
                return "Motorcycle";
            case "Hatchback/Liftback/Notchback":
                return "Hatchback";
            case "Racing Car":
            case "Racing":
            case "Recreational Off-Highway Vehicles (ROV)":
            case "Sport Utility Vehicle (SUV)/Multi Purpose Vehicle (MPV)":
            case "Grand Tourer":
            case "Megacity Vehicle (MCV)":
            case "Off-road vehicle":
                return "SUV";
            default:
                return "Sedan";
        }
    }

    public function getOrderIncrementId()
    {
        $current = CsEavSetting::where('entity', 'cs_order_id')->first();
        $incrementid = $current->val;
        $current->increment('val');
        return $incrementid;
    }

    public function getpaymentTypeValue($all = false, $key = false, $val = false)
    {
        $return = [
            1 => "Deposit Payment",
            2 => "Deposit Retry",
            3 => "Deposit Refund",
            4 => "Deposit Partial Refund",
            5 => "Rental Payment",
            6 => "Rental Retry",
            7 => "Rental Refund",
            8 => "Rental Partial Refund",
            9 => "Rental Partial Charge",
            10 => "Insurance Payment",
            11 => "Insurance Refund",
            12 => "Insurance Retry",
            13 => "Insurance Partial Refund",
            14 => "Insurance Partial Charge",
            15 => "Initial Fee Payment",
            16 => "Initial Fee Refund",
            17 => "Initial Fee Retry",
            18 => "Initial Fee Partial Refund",
            19 => "Initial Fee Partial Charge",
            20 => "Deposit Partial Charge",
            21 => "Deposit Dealer Transfer",
            22 => "Toll Payment",
            23 => "Toll Retry",
            24 => "Customer Balance Charge",
            25 => "TDK Violation charges",
            26 => "Insurance EMF",
            27 => "Extra Mile fee",
            28 => "Extra Mile fee Retry",
            29 => "Partial Payment",
            30 => "Extenion Request Payment",
            31 => "Refund EMF",
            32 => "Refund Insurance EMF",
            33 => "Wallet Refund",
            34 => 'DIA Telematics Charge',
            35 => 'Uber Booking Charge',
            36 => 'Uber Booking Refund',
            37 => 'Toll Refund'
        ];

        if ($all) {
            return $return;
        }
        if ($key) {
            return isset($return[$key]) ? $return[$key] : "Unknown Payment Type";
        }
        if ($val) {
            $return = array_flip($return);
            return $return[$val];
        }
    }

    public function getRefundType()
    {
        return [3, 4, 7, 8, 11, 13, 16, 18, 31, 32, 33, 36];
    }

    public function getPayoutTypeValue($all = false, $key = false, $val = false)
    {
        $return = [
            '1' => 'Deposit',
            '2' => 'Usage Transaction',
            '3' => 'Initial Fee',
            '4' => 'Insurance Fee',
            '5' => 'Cancelation fee',
            '6' => "Toll Fee",
            '7' => "Customer Balance Charge",
            "8" => "Toll Violation",
            "9" => "Red Light Violation",
            "10" => "Parking Violation",
            '11' => 'Refund Balance',
            '12' => 'Driver Credit,',
            '13' => 'Geotab Monthly Fee',
            '14' => "DIA Insurance Fee",
            '15' => "Credit Card Chargebacks",
            '16' => "Extra Usage Fee",
            "17" => "Car Damage Fee",
            "18" => "Hazardous Driving Fee",
            "19" => "Ext/Late Fee",
            "20" => "Vehicle Insurance Penalty",
            "21" => "Credit Deposit to Virtual Card",
        ];

        if ($all) {
            return $return;
        }
        if ($key) {
            return $return[$key] ?? 'Unknown';
        }
        if ($val) {
            $return = array_flip($return);
            return $return[$val];
        }

    }

    public function getTollSmartTolls()
    {
        return [
            "BreezeBy" => "BreezeBy",
            "Camino Colombia Day Pass" => "Camino Colombia Day Pass",
            "Commercial ExpressPass" => "Commercial ExpressPass",
            "Delaware E-ZPass®" => "Delaware E-ZPass®",
            "Downbeach Express Pass" => "Downbeach Express Pass",
            "E-PASS" => "E-PASS",
            "EPTOLL" => "EPTOLL",
            "ExpressCard" => "ExpressCard",
            "ExpressPass" => "ExpressPass",
            "Express Pass" => "Express Pass",
            "ExpressToll" => "ExpressToll",
            "EZ Cross" => "EZ Cross",
            "EZTAG" => "EZTAG",
            "FasTrak" => "FasTrak",
            "Freedom Pass" => "Freedom Pass",
            "GeauxPass" => "GeauxPass",
            "GIBA TOLL PASS" => "GIBA TOLL PASS",
            "GO-PASS" => "GO-PASS",
            "Good To Go!" => "Good To Go!",
            "Grosse Ile Toll Bridge Pass Tag" => "Grosse Ile Toll Bridge Pass Tag",
            "I-PASS" => "I-PASS",
            "Illinois E-ZPass®" => "Illinois E-ZPass®",
            "Indiana E-ZPass®" => "Indiana E-ZPass®",
            "IQ Prox Card" => "IQ Prox Card",
            "K-TAG" => "K-TAG",
            "Laredo Trade Tag" => "Laredo Trade Tag",
            "LeeWay" => "LeeWay",
            "Mackinac Bridge Mac Pass" => "Mackinac Bridge Mac Pass",
            "Maine E-ZPass®" => "Maine E-ZPass®",
            "Maryland E-ZPass®" => "Maryland E-ZPass®",
            "Massachusetts E-ZPass®" => "Massachusetts E-ZPass®",
            "MnPass" => "MnPass",
            "NC Quick Pass" => "NC Quick Pass",
            "New Hampshire E-ZPass®" => "New Hampshire E-ZPass®",
            "New Jersey E-ZPass®" => "New Jersey E-ZPass®",
            "New York E-ZPass®" => "New York E-ZPass®",
            "NEXPRESS ® TOLL" => "NEXPRESS ® TOLL",
            "NEXPRESSTM Commercial TOLL" => "NEXPRESSTM Commercial TOLL",
            "NEXUS Card" => "NEXUS Card",
            "North Carolina E-ZPass®" => "North Carolina E-ZPass®",
            "Ohio E-ZPass®" => "Ohio E-ZPass®",
            "PalPass (Palmetto Pass)" => "PalPass (Palmetto Pass)",
            "Peach Pass" => "Peach Pass",
            "Pennsylvania E-ZPass®" => "Pennsylvania E-ZPass®",
            "PIKEPASS" => "PIKEPASS",
            "Rhode Island E-ZPass®" => "Rhode Island E-ZPass®",
            "RiverLink" => "RiverLink",
            "SEAWAY CORPORATE CARD" => "SEAWAY CORPORATE CARD",
            "SEAWAY TRANSIT CARD" => "SEAWAY TRANSIT CARD",
            "SunPass" => "SunPass",
            "Toll Tag" => "Toll Tag",
            "TollTag" => "TollTag",
            "TxTag" => "TxTag",
            "Virginia E-ZPass®" => "Virginia E-ZPass®",
            "West Virginia E-ZPass®" => "West Virginia E-ZPass®",
            "XPRESS CARD" => "XPRESS CARD"
        ];
    }

    public function getCabNameList()
    {
        return [
            "Acura" => [
                "ILX" => ['Sedan' => "Sedan"],
                "ILX Hybrid" => ['Sedan' => "Sedan"],
                "MDX" => ['XL Luxury' => "XL Luxury"],
                "RDX" => ['Regular Sedan' => "Regular Sedan"],
                "RL" => ['Luxury Car' => "Luxury Car"],
                "RLX" => ['Luxury Car' => "Luxury Car"],
                "TL" => ['Regular Sedan' => "Regular Sedan"],
                "TLX" => ['Regular Sedan' => "Regular Sedan"],
                "TSX" => ['Regular Sedan' => "Regular Sedan"],
                "TSX Sport Wagon" => ['Sedan' => "Sedan"],
                "ZDX" => ['Sedan' => "Sedan"],
            ],
            "Audi" => [
                "A3 Sedan" => ['Regular Sedan' => "Regular Sedan"],
                "A3 Hatchback" => ['Sedan' => "Sedan"],
                "A3 e-tron" => ['Sedan' => "Sedan"],
                "A4" => ['Regular Sedan' => "Regular Sedan"],
                "A6" => ['Luxury Car' => "Luxury Car"],
                "A7" => ['Luxury Car' => "Luxury Car"],
                "A8" => ['Luxury Car' => "Luxury Car"],
                "Allroad" => ['Luxury Car' => "Luxury Car"],
                "Q3" => ['Sedan' => "Sedan"],
                "Q5" => ['Luxury Car' => "Luxury Car"],
                "Q7" => ['XL Luxury' => "XL Luxury"],
                "RS 7" => ['Luxury Car' => "Luxury Car"],
                "S3" => ['Sedan' => "Sedan"],
                "S4" => ['Regular Sedan' => "Regular Sedan"],
                "S6" => ['Luxury Car' => "Luxury Car"],
                "S7" => ['Luxury Car' => "Luxury Car"],
                "S8" => ['Luxury Car' => "Luxury Car"],
                "SQ5" => ['Luxury Car' => "Luxury Car"],
                "TT" => ['Sedan' => "Sedan"],
                "TTS" => ['Sedan' => "Sedan"]
            ],
            "BMW" => [
                "3 Series" => ['Regular Sedan' => "Regular Sedan"],
                "3 Series Gran Turismo" => ['Luxury Car' => "Luxury Car"],
                "4 Series Gran Coupe" => ['Sedan' => "Sedan"],
                "5 Series" => ['Luxury Car' => "Luxury Car"],
                "5 Series Gran Turismo" => ['Luxury Car' => "Luxury Car"],
                "6 Series Gran Coupe" => ['Luxury Car' => "Luxury Car"],
                "7 Series" => ['Luxury Car' => "Luxury Car"],
                "ActiveHybrid 5" => ['Sedan' => "Sedan"],
                "ActiveHybrid 7" => ['Luxury Car' => "Luxury Car"],
                "ActiveHybrid X6" => ['Luxury Car' => "Luxury Car"],
                "ALPINA B6 Gran Coupe" => ['Sedan' => "Sedan"],
                "ALPINA B7" => ['Luxury Car' => "Luxury Car"],
                "i3" => ['Sedan' => "Sedan"],
                "M3" => ['Sedan' => "Sedan"],
                "M5" => ['Luxury Car' => "Luxury Car"],
                "M6 Gran Coupe" => ['Luxury Car' => "Luxury Car"],
                "X1" => ['Regular Sedan' => "Regular Sedan"],
                "X3" => ['Luxury Car' => "Luxury Car"],
                "X4" => ['Luxury Car' => "Luxury Car"],
                "X5" => ['Luxury Car' => "Luxury Car"],
                "X5 M" => ['Luxury Car' => "Luxury Car"],
                "X6" => ['Luxury Car' => "Luxury Car"],
                "X6 M" => ['Luxury Car' => "Luxury Car"]
            ],
            "Buick" => [
                "Anthem Envision" => ['Regular Sedan' => "Regular Sedan"],
                "Enclave" => ['XL Luxury' => "XL Luxury"],
                "Encore" => ['Regular Sedan' => "Regular Sedan"],
                "LaCrosse" => ['Luxury Car' => "Luxury Car"],
                "Lucerne" => ['Regular Sedan' => "Regular Sedan"],
                "Regal" => ['Regular Sedan' => "Regular Sedan"],
                "Verano" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Cadillac" => [
                "ATS" => ['Sedan' => "Sedan"],
                "ATS-V" => ['Sedan' => "Sedan"],
                "CTS" => ['Luxury Car' => "Luxury Car"],
                "CTS Coupe" => ['Sedan' => "Sedan"],
                "CTS Wagon" => ['Luxury Car' => "Luxury Car"],
                "CTS-V" => ['Luxury Car' => "Luxury Car"],
                "CTS-V Coupe" => ['Sedan' => "Sedan"],
                "CTS-V Wagon" => ['Luxury Car' => "Luxury Car"],
                "DTS" => ['Luxury Car' => "Luxury Car"],
                "Escalade" => ['SUV' => "SUV"],
                "Escalade ESV" => ['SUV' => "SUV"],
                "Escalade Hybrid" => ['SUV' => "SUV"],
                "SRX" => ['Luxury Car' => "Luxury Car"],
                "STS" => ['Luxury Car' => "Luxury Car"],
                "XT5" => ['Luxury Car' => "Luxury Car"],
                "XTS" => ['Luxury Car' => "Luxury Car"]
            ],
            "Chevrolet" => [
                "Aveo" => ['Sedan' => "Sedan"],
                "Captiva Sport" => ['Regular Sedan' => "Regular Sedan"],
                "City Express" => ['Sedan' => "Sedan"],
                "Cobalt" => ['Sedan' => "Sedan"],
                "Colorado" => ['Sedan' => "Sedan"],
                "Cruze" => ['Regular Sedan' => "Regular Sedan"],
                "Cruze Limited Sedan" => ['Regular Sedan' => "Regular Sedan"],
                "Equinox" => ['Regular Sedan' => "Regular Sedan"],
                "HHR" => ['Regular Sedan' => "Regular Sedan"],
                "Impala" => ['Regular Sedan' => "Regular Sedan"],
                "Impala Limited" => ['Regular Sedan' => "Regular Sedan"],
                "Malibu" => ['Regular Sedan' => "Regular Sedan"],
                "Malibu Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Silverado 1500" => ['Sedan' => "Sedan"],
                "Silverado 2500HD" => ['Sedan' => "Sedan"],
                "Silverado 3500HD" => ['Sedan' => "Sedan"],
                "Sonic" => ['Sedan' => "Sedan"],
                "Spark" => ['Sedan' => "Sedan"],
                "Spark EV" => ['Sedan' => "Sedan"],
                "SS" => ['Regular Sedan' => "Regular Sedan"],
                "Suburban" => ['SUV' => "SUV"],
                "Tahoe" => ['SUV' => "SUV"],
                "Tahoe Hybrid" => ['SUV' => "SUV"],
                "Traverse" => ['Minivan' => "Minivan"],
                "Trax" => ['Regular Sedan' => "Regular Sedan"],
                "Volt" => ['Sedan' => "Sedan"]
            ],
            "Chrysler" => [
                "200" => ['Regular Sedan' => "Regular Sedan"],
                "300" => ['Regular Sedan' => "Regular Sedan"],
                "Pacifica" => ['Minivan' => "Minivan"],
                "PT Cruiser" => ['Sedan' => "Sedan"],
                "Sebring" => ['Regular Sedan' => "Regular Sedan"],
                "Town and Country" => ['Minivan' => "Minivan"]
            ],
            "Dodge" => [
                "Avenger" => ['Regular Sedan' => "Regular Sedan"],
                "Caliber" => ['Regular Sedan' => "Regular Sedan"],
                "Charger" => ['Regular Sedan' => "Regular Sedan"],
                "Dakota" => ['Sedan' => "Sedan"],
                "Dart" => ['Regular Sedan' => "Regular Sedan"],
                "Durango" => ['XL' => "XL"],
                "Grand Caravan" => ['Minivan' => "Minivan"],
                "Journey" => ['Minivan' => "Minivan"],
                "Nitro" => ['Regular Sedan' => "Regular Sedan"],
            ],
            "FIAT" => [
                "500" => ['Sedan' => "Sedan"],
                "500e" => ['Sedan' => "Sedan"],
                "500L" => ['Sedan' => "Sedan"]
            ],
            "Ford" => [
                "C-Max Energi" => ['Regular Sedan' => "Regular Sedan"],
                "C-Max Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Crown Victoria" => ['Regular Sedan' => "Regular Sedan"],
                "Edge" => ['Regular Sedan' => "Regular Sedan"],
                "Escape" => ['Regular Sedan' => "Regular Sedan"],
                "Escape Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Expedition" => ['SUV' => "SUV"],
                "Explorer" => ['XL' => "XL"],
                "Fiesta" => ['Sedan' => "Sedan"],
                "Flex" => ['XL' => "XL"],
                "Focus" => ['Regular Sedan' => "Regular Sedan"],
                "Focus ST" => ['Sedan' => "Sedan"],
                "Fusion" => ['Regular Sedan' => "Regular Sedan"],
                "Fusion Energi" => ['Regular Sedan' => "Regular Sedan"],
                "Fusion Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Taurus" => ['Regular Sedan' => "Regular Sedan"],
                "Transit Connect" => ['Sedan' => "Sedan"]
            ],
            "GMC" => [
                "Acadia" => ['XL' => "XL"],
                "Canyon" => ['Sedan' => "Sedan"],
                "Envoy" => ['Regular Sedan' => "Regular Sedan"],
                "Sierra 1500" => ['Sedan' => "Sedan"],
                "Sierra 2500HD" => ['Sedan' => "Sedan"],
                "Sierra 3500HD" => ['Sedan' => "Sedan"],
                "Terrain" => ['Regular Sedan' => "Regular Sedan"],
                "Yukon" => ['SUV' => "SUV"],
                "Yukon Hybrid" => ['SUV' => "SUV"],
                "Yukon XL" => ['SUV' => "SUV"]
            ],
            "Honda" => [
                "Accord" => ['Regular Sedan' => "Regular Sedan"],
                "Accord Crosstour" => ['Regular Sedan' => "Regular Sedan"],
                "Accord Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Accord Plug-In Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Civic" => ['Regular Sedan' => "Regular Sedan"],
                "CR-V" => ['Regular Sedan' => "Regular Sedan"],
                "CR-Z" => ['Sedan' => "Sedan"],
                "Crosstour" => ['Regular Sedan' => "Regular Sedan"],
                "Element" => ['Regular Sedan' => "Regular Sedan"],
                "Fit" => ['Sedan' => "Sedan"],
                "Fit EV" => ['Sedan' => "Sedan"],
                "HR-V" => ['Regular Sedan' => "Regular Sedan"],
                "Insight" => ['Sedan' => "Sedan"],
                "Odyssey" => ['Minivan' => "Minivan"],
                "Pilot" => ['XL' => "XL"]
            ],
            "HUMMER" => [
                "H3" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Hyundai" => [
                "Accent" => ['Sedan' => "Sedan"],
                "Azera" => ['Regular Sedan' => "Regular Sedan"],
                "Elantra" => ['Regular Sedan' => "Regular Sedan"],
                "Elantra GT" => ['Sedan' => "Sedan"],
                "Elantra Touring" => ['Regular Sedan' => "Regular Sedan"],
                "Equus" => ['Luxury Car' => "Luxury Car"],
                "Genesis" => ['Luxury Car' => "Luxury Car"],
                "Santa Fe" => ['Minivan' => "Minivan"],
                "Santa Fe Sport" => ['Regular Sedan' => "Regular Sedan"],
                "Sonata" => ['Regular Sedan' => "Regular Sedan"],
                "Sonata Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Tucson" => ['Regular Sedan' => "Regular Sedan"],
                "Veloster" => ['Sedan' => "Sedan"],
                "Veracruz" => ['XL Luxury' => "XL Luxury"]
            ],
            "Infiniti" => [
                "EX" => ['Sedan' => "Sedan"],
                "EX35" => ['Sedan' => "Sedan"],
                "FX" => ['Sedan' => "Sedan"],
                "FX35" => ['Sedan' => "Sedan"],
                "FX50" => ['Sedan' => "Sedan"],
                "G Sedan" => ['Sedan' => "Sedan"],
                "G37 Sedan" => ['Sedan' => "Sedan"],
                "JX" => ['XL Luxury' => "XL Luxury"],
                "M" => ['Luxury Car' => "Luxury Car"],
                "M35" => ['Luxury Car' => "Luxury Car"],
                "M37" => ['Luxury Car' => "Luxury Car"],
                "M45" => ['Luxury Car' => "Luxury Car"],
                "M56" => ['Luxury Car' => "Luxury Car"],
                "Q40" => ['Sedan' => "Sedan"],
                "Q50" => ['Regular Sedan' => "Regular Sedan"],
                "Q70" => ['Luxury Car' => "Luxury Car"],
                "QX" => ['SUV' => "SUV"],
                "QX50" => ['Regular Sedan' => "Regular Sedan"],
                "QX56" => ['SUV' => "SUV"],
                "QX60" => ['SUV' => "SUV"],
                "QX70" => ['Sedan' => "Sedan"],
                "QX80" => ['SUV' => "SUV"]
            ],
            "Jaguar" => [
                "F-Pace" => ['Luxury Car' => "Luxury Car"],
                "XF" => ['Luxury Car' => "Luxury Car"],
                "XJ" => ['Luxury Car' => "Luxury Car"]
            ],
            "Jeep" => [
                "Cherokee" => ['Regular Sedan' => "Regular Sedan"],
                "Commander" => ['XL' => "XL"],
                "Compass" => ['Regular Sedan' => "Regular Sedan"],
                "Grand Cherokee" => ['Regular Sedan' => "Regular Sedan"],
                "Grand Cherokee SRT" => ['Regular Sedan' => "Regular Sedan"],
                "Liberty" => ['Regular Sedan' => "Regular Sedan"],
                "Patriot" => ['Regular Sedan' => "Regular Sedan"],
                "Renegade" => ['Regular Sedan' => "Regular Sedan"],
                "Wrangler" => ['Sedan' => "Sedan"]
            ],
            "Kia" => [
                "Amanti" => ['Regular Sedan' => "Regular Sedan"],
                "Cadenza" => ['Regular Sedan' => "Regular Sedan"],
                "Forte" => ['Regular Sedan' => "Regular Sedan"],
                "K900" => ['Luxury Car' => "Luxury Car"],
                "Optima" => ['Regular Sedan' => "Regular Sedan"],
                "Optima Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Rio" => ['Sedan' => "Sedan"],
                "Sedona" => ['XL' => "XL"],
                "Sorento" => ['Regular Sedan' => "Regular Sedan"],
                "Soul" => ['Sedan' => "Sedan"],
                "Soul EV" => ['Sedan' => "Sedan"],
                "Sportage" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Land Rover" => [
                "Discovery Sport" => ['Regular Sedan' => "Regular Sedan"],
                "LR2" => ['Sedan' => "Sedan"],
                "LR3" => ['Regular Sedan' => "Regular Sedan"],
                "LR4" => ['Luxury Car' => "Luxury Car"],
                "Range Rover" => ['Luxury Car' => "Luxury Car"],
                "Range Rover Evoque" => ['Sedan' => "Sedan"],
                "Range Rover Sport" => ['Luxury Car' => "Luxury Car"]
            ],
            "Lexus" => [
                "CT 200h" => ['Sedan' => "Sedan"],
                "ES 300h" => ['Luxury Car' => "Luxury Car"],
                "GS 350" => ['Luxury Car' => "Luxury Car"],
                "GS 450h" => ['Luxury Car' => "Luxury Car"],
                "GS 460" => ['Luxury Car' => "Luxury Car"],
                "GS F" => ['Regular Sedan' => "Regular Sedan"],
                "GX 460" => ['Luxury Car' => "Luxury Car"],
                "HS 250h" => ['Sedan' => "Sedan"],
                "IS 250" => ['Sedan' => "Sedan"],
                "IS 350" => ['Sedan' => "Sedan"],
                "IS F" => ['Sedan' => "Sedan"],
                "LS 460" => ['Luxury Car' => "Luxury Car"],
                "LS 600h L" => ['Luxury Car' => "Luxury Car"],
                "LX 570" => ['Luxury Car' => "Luxury Car"],
                "NX 200t" => ['Regular Sedan' => "Regular Sedan"],
                "NX 300h" => ['Sedan' => "Sedan"],
                "RX 350" => ['Luxury Car' => "Luxury Car"],
                "RX 450h" => ['Luxury Car' => "Luxury Car"]
            ],
            "Lincoln" => [
                "MKC" => ['Regular Sedan' => "Regular Sedan"],
                "MKS" => ['Regular Sedan' => "Regular Sedan"],
                "MKT" => ['XL Luxury' => "XL Luxury"],
                "MKX" => ['Luxury Car' => "Luxury Car"],
                "MKZ" => ['Regular Sedan' => "Regular Sedan"],
                "MKZ Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Navigator" => ['SUV' => "SUV"],
                "Town Car" => ['Regular Sedan' => "Regular Sedan"],
            ],
            "Maserati" => [
                "Ghibli" => ['Regular Sedan' => "Regular Sedan"],
                "Quattroporte" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Mazda" => [
                "CX-5" => ['Regular Sedan' => "Regular Sedan"],
                "CX-7" => ['Regular Sedan' => "Regular Sedan"],
                "CX-9" => ['XL' => "XL"],
                "Mazda2" => ['Sedan' => "Sedan"],
                "Mazda3" => ['Sedan' => "Sedan"],
                "Mazda5" => ['Regular Sedan' => "Regular Sedan"],
                "Mazda6" => ['Regular Sedan' => "Regular Sedan"],
                "Mazdaspeed3" => ['Regular Sedan' => "Regular Sedan"],
                "Tribute" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Mercedes-Benz" => [
                "B-Class Electric Drive" => ['Sedan' => "Sedan"],
                "C-Class" => ['Regular Sedan' => "Regular Sedan"],
                "CLA-Class" => ['Sedan' => "Sedan"],
                "CLS-Class" => ['Regular Sedan' => "Regular Sedan"],
                "E-Class Sedan" => ['Luxury Car' => "Luxury Car"],
                "E-Class Wagon" => ['XL Luxury' => "XL Luxury"],
                "G-Class" => ['Luxury Car' => "Luxury Car"],
                "GL-Class" => ['SUV' => "SUV"],
                "GLA-Class" => ['Sedan' => "Sedan"],
                "GLC-Class" => ['Luxury Car' => "Luxury Car"],
                "GLE-Class" => ['Luxury Car' => "Luxury Car"],
                "GLK-Class" => ['Luxury Car' => "Luxury Car"],
                "M-Class" => ['Luxury Car' => "Luxury Car"],
                "Metris Passenger Van" => ["XL" => "XL"],
                "R-Class" => ['Luxury Car' => "Luxury Car"],
                "S-Class" => ['Luxury Car' => "Luxury Car"]
            ],
            "Mercury" => [
                "Grand Marquis" => ['Regular Sedan' => "Regular Sedan"],
                "Mariner" => ['Regular Sedan' => "Regular Sedan"],
                "Mariner Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Milan" => ['Regular Sedan' => "Regular Sedan"],
                "Milan Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Mountaineer" => ["XL" => "XL"],
                "Sable" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "MINI" => [
                "Cooper" => ['Sedan' => "Sedan"],
                "Cooper Clubman" => ['Sedan' => "Sedan"],
                "Cooper Countryman" => ['Sedan' => "Sedan"],
                "Cooper Coupe" => ['Sedan' => "Sedan"],
                "Cooper Paceman" => ['Sedan' => "Sedan"],
            ],
            "Mitsubishi" => [
                "Eclipse" => ['Sedan' => "Sedan"],
                "Endeavor" => ['Regular Sedan' => "Regular Sedan"],
                "Galant" => ['Regular Sedan' => "Regular Sedan"],
                "i-MiEV" => ['Sedan' => "Sedan"],
                "Lancer" => ['Regular Sedan' => "Regular Sedan"],
                "Lancer Evolution" => ['Sedan' => "Sedan"],
                "Lancer Sportback" => ['Sedan' => "Sedan"],
                "Mirage" => ['Sedan' => "Sedan"],
                "Outlander" => ["XL" => "XL"],
                "Outlander Sport" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Nissan" => [
                "Altima" => ['Regular Sedan' => "Regular Sedan"],
                "Altima Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Armada" => ['SUV' => "SUV"],
                "Cube" => ['Sedan' => "Sedan"],
                "Juke" => ['Sedan' => "Sedan"],
                "Leaf" => ['Sedan' => "Sedan"],
                "Maxima" => ['Regular Sedan' => "Regular Sedan"],
                "Murano" => ['Regular Sedan' => "Regular Sedan"],
                "Murano CrossCabriolet" => ['Sedan' => "Sedan"],
                "NV200" => ["Minivan" => "Minivan"],
                "Pathfinder" => ["XL" => "XL"],
                "Quest" => ["XL" => "XL"],
                "Rogue" => ['Regular Sedan' => "Regular Sedan"],
                "Rogue Select" => ['Regular Sedan' => "Regular Sedan"],
                "Sentra" => ['Regular Sedan' => "Regular Sedan"],
                "Versa" => ['Sedan' => "Sedan"],
                "Versa Note" => ['Sedan' => "Sedan"],
                "Xterra" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Pontiac" => [
                "G6" => ['Regular Sedan' => "Regular Sedan"],
                "G8" => ['Regular Sedan' => "Regular Sedan"],
                "Torrent" => ['Regular Sedan' => "Regular Sedan"],
                "Vibe" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Porsche" => [
                "Cayenne" => ['Luxury Car' => "Luxury Car"],
                "Macan" => ['Luxury Car' => "Luxury Car"],
                "Panamera" => ['Luxury Car' => "Luxury Car"]
            ],
            "Ram" => [
                "Cargo Van" => ['Sedan' => "Sedan"],
                "C/V Tradesman" => ['Sedan' => "Sedan"],
                "CV Tradesman" => ['Sedan' => "Sedan"],
                "Dakota" => ['Sedan' => "Sedan"],
                "ProMaster City" => ['Sedan' => "Sedan"]
            ],
            "Rolls-Royce" => [
                "Ghost" => ['Luxury Car' => "Luxury Car"],
                "Phantom" => ['Luxury Car' => "Luxury Car"]
            ],
            "Saab" => [
                "9-3 Griffin" => ['Sedan' => "Sedan"],
                "9-4X" => ['Regular Sedan' => "Regular Sedan"],
                "9-7X" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Saturn" => [
                "Vue" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Scion" => [
                "iQ" => ['Sedan' => "Sedan"],
                "tC" => ['Sedan' => "Sedan"],
                "xB" => ['Sedan' => "Sedan"],
                "xD" => ['Sedan' => "Sedan"]
            ],
            "Smart" => [
                "Fortwo" => ['Sedan' => "Sedan"]
            ],
            "Subaru" => [
                "Forester" => ['Regular Sedan' => "Regular Sedan"],
                "Impreza" => ['Regular Sedan' => "Regular Sedan"],
                "Impreza WRX" => ['Sedan' => "Sedan"],
                "Legacy" => ['Regular Sedan' => "Regular Sedan"],
                "Outback" => ['Regular Sedan' => "Regular Sedan"],
                "Tribeca" => ["XL" => "XL"],
                "WRX" => ['Regular Sedan' => "Regular Sedan"],
                "XV Crosstrek" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Suzuki" => [
                "Equator" => ['Sedan' => "Sedan"],
                "Grand Vitara" => ['Regular Sedan' => "Regular Sedan"],
                "Kizashi" => ['Regular Sedan' => "Regular Sedan"],
                "SX4" => ['Sedan' => "Sedan"]
            ],
            "Tesla" => [
                "Model S" => ['Luxury Car' => "Luxury Car"],
                "Model X" => ['XL Luxury' => "XL Luxury"]
            ],
            "Toyota" => [
                "4Runner" => ['Regular Sedan' => "Regular Sedan"],
                "Avalon" => ['Regular Sedan' => "Regular Sedan"],
                "Avalon Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Camry" => ['Regular Sedan' => "Regular Sedan"],
                "Camry Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Corolla" => ['Regular Sedan' => "Regular Sedan"],
                "FJ Cruiser" => ['Sedan' => "Sedan"],
                "Highlander" => ['XL' => "XL"],
                "Highlander Hybrid" => ['XL' => "XL"],
                "Land Cruiser" => ['XL Luxury' => "XL Luxury"],
                "Matrix" => ['Sedan' => "Sedan"],
                "Mirai" => ['Sedan' => "Sedan"],
                "Prius" => ['Regular Sedan' => "Regular Sedan"],
                "Prius c" => ['Sedan' => "Sedan"],
                "Prius v" => ['Regular Sedan' => "Regular Sedan"],
                "RAV4" => ['Regular Sedan' => "Regular Sedan"],
                "RAV4 EV" => ['Regular Sedan' => "Regular Sedan"],
                "Sequoia" => ['SUV' => "SUV"],
                "Sienna" => ['Minivan' => "Minivan"],
                "Tacoma" => ['Sedan' => "Sedan"],
                "Tundra" => ['Sedan' => "Sedan"],
                "Venza" => ['Regular Sedan' => "Regular Sedan"],
                "Yaris" => ['Sedan' => "Sedan"]
            ],
            "Volkswagen" => [
                "Beetle" => ['Sedan' => "Sedan"],
                "CC" => ['Regular Sedan' => "Regular Sedan"],
                "e-Golf" => ['Sedan' => "Sedan"],
                "GLI" => ['Sedan' => "Sedan"],
                "Golf" => ['Sedan' => "Sedan"],
                "Golf R" => ['Sedan' => "Sedan"],
                "Golf SportWagen" => ['Regular Sedan' => "Regular Sedan"],
                "GTI" => ['Sedan' => "Sedan"],
                "Jetta" => ['Regular Sedan' => "Regular Sedan"],
                "Jetta GLI" => ['Regular Sedan' => "Regular Sedan"],
                "Jetta Hybrid" => ['Regular Sedan' => "Regular Sedan"],
                "Jetta SportWagen" => ['Sedan' => "Sedan"],
                "New Beetle" => ['Sedan' => "Sedan"],
                "Passat" => ['Regular Sedan' => "Regular Sedan"],
                "Routan" => ['Regular Sedan' => "Regular Sedan"],
                "Tiguan" => ['Regular Sedan' => "Regular Sedan"],
                "Touareg" => ['Regular Sedan' => "Regular Sedan"]
            ],
            "Volvo" => [
                "C30" => ['Sedan' => "Sedan"],
                "S40" => ['Sedan' => "Sedan"],
                "S60" => ['Regular Sedan' => "Regular Sedan"],
                "S80" => ['Luxury Car' => "Luxury Car"],
                "V50" => ['Sedan' => "Sedan"],
                "V60" => ['Sedan' => "Sedan"],
                "V70" => ['Sedan' => "Sedan"],
                "XC60" => ['Regular Sedan' => "Regular Sedan"],
                "XC70" => ['Regular Sedan' => "Regular Sedan"],
                "XC90" => ['XL Luxury' => "XL Luxury"]
            ]
        ];
    }

    public function get_unique_id($length = 32, $pool = '')
    {

        if ($pool === '') {
            $pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        }

        $unique_id = '';
        $max = strlen($pool) - 1;

        for ($i = 0; $i < $length; $i++) {
            $unique_id .= $pool[random_int(0, $max)];
        }

        return $unique_id;
    }

    public function makeshorturl($lurl)
    {
        $apiUrl = config('legacy.tinyurl.apiUrl', 'http://tiny-url.info/api/v1/create');
        $apiKey = config('legacy.tinyurl.apiKey', '');

        $response = Http::asForm()->post($apiUrl, [
            'format' => 'json',
            'apikey' => $apiKey,
            'provider' => 'tinyurl_com',
            'url' => $lurl,
        ]);


        if ($response->successful() && ($response['state'] ?? '') === 'ok') {
            return $response['shorturl'];
        }

        return (new TinnyUrlService())->generate($lurl);

    }

    public function getRenterDetails($renterId)
    {
        $userData = User::find($renterId);
        return $userData ? $userData->toArray() : [];
    }

    public function getParentOrderStartDate($parentOrderId, $startDateTime)
    {
        if (!$parentOrderId) {
            return $startDateTime;
        }

        $order = CsOrder::select('start_datetime')
            ->where('id', $parentOrderId)
            ->first();

        return $order->start_datetime ?? $startDateTime;
    }


    public function addDateInOption($basedate, $arraydata, $currency = 'USD', $basecurrency = 'USD')
    {
        if (empty($basedate)) {
            return $arraydata;
        }

        $temp = [];

        if (empty($arraydata)) {
            return $temp;
        }

        $sortedData = collect($arraydata)
            ->sortBy('after_day')
            ->sortBy('after_day_date')
            ->values();

        foreach ($sortedData as $item) {

            if (isset($item['after_day_date']) && !empty($item['after_day_date'])) {
                $item['expected'] = Carbon::parse($item['after_day_date'])->format('m/d/Y');
                $item['amount'] = CakeNumber::currency($item['amount'], $currency, $basecurrency);
                $temp[] = $item;
                continue;
            }

            if (empty($item['after_day']) && $item['after_day'] != 0) {
                continue;
            }

            $item['amount'] = CakeNumber::currency($item['amount'], $currency, $basecurrency);

            $item['expected'] = Carbon::parse($basedate)
                ->addDays("{$item['after_day']}")
                ->format('m/d/Y');

            $temp[] = $item;
        }

        return $temp;
    }

    public function makeDateInOption($basedate, $arraydata)
    {
        if (empty($basedate)) {
            return "";
        }

        $temp = "";
        $baseCarbon = Carbon::parse($basedate);
        $sortedData = collect($arraydata)
            ->sortBy('after_day')
            ->sortBy('after_day_date')
            ->values();

        foreach ($sortedData as $item) {
            $amount = $item['amount'] ?? 0;

            if (isset($item['after_day_date']) && !empty($item['after_day_date'])) {
                $itemDate = Carbon::parse($item['after_day_date']);

                if ($itemDate->greaterThanOrEqualTo($baseCarbon)) {
                    $formattedDate = $itemDate->format('m/d/Y');
                    $temp .= " \${$amount} on {$formattedDate},";
                    continue;
                }
            }

            if (!empty($item['after_day']) && $item['after_day'] != 0) {
                $days = $item['after_day'];
                $calculatedDate = $baseCarbon->copy()->addDays("{$days}")->format('m/d/Y');
                $temp .= " \${$amount} on {$calculatedDate},";
            }
        }

        return $temp;
    }

    public function makeDateInOptionForSchedule($baseDate, $arrayData, $currency = 'USD')
    {
        if (empty($baseDate) || empty($arrayData)) {
            return [];
        }

        $sortedData = collect($arrayData)
            ->sortBy('after_day')
            ->sortBy('after_day_date')
            ->values();

        $temp = [];
        $baseCarbon = Carbon::parse($baseDate);

        foreach ($sortedData as $item) {
            $amount = CakeNumber::currency($item['amount'] ?? 0, $currency);

            if (isset($item['after_day_date']) && !empty($item['after_day_date'])) {
                $formattedDate = Carbon::parse($item['after_day_date'])->format('m/d/Y');
                $temp[$item['after_day_date']] = "{$amount} on {$formattedDate}";
                continue;
            }

            if (empty($item['after_day']) && $item['after_day'] != 0) {
                continue;
            }

            $days = $item['after_day'];
            $calculatedDate = $baseCarbon->copy()->addDays("{$days}")->format('m/d/Y');
            $temp[$days] = "{$amount} on {$calculatedDate}";
        }

        return $temp;
    }

    public function updateMissedScheduleOpt($newDate, $arrayData)
    {
        if (empty($arrayData) || empty($newDate)) {
            return [];
        }

        $sortedData = collect($arrayData)
            ->sortBy('after_day')
            ->sortBy('after_day_date')
            ->values();

        $temp = [];
        $comparisonDate = Carbon::parse($newDate);

        foreach ($sortedData as $item) {
            $amount = $item['amount'] ?? 0;

            if (isset($item['after_day_date']) && !empty($item['after_day_date'])) {
                $itemDate = Carbon::parse($item['after_day_date']);

                $temp[] = [
                    "after_day_date" => $itemDate->lessThanOrEqualTo($comparisonDate) ? $comparisonDate->copy()->addDay()->format('m/d/Y') : $item['after_day_date'],
                    "amount" => $amount
                ];

                continue;
            }

            if (!isset($item['after_day']) || empty($item['after_day']) || $item['after_day'] == 0) {
                continue;
            }

            $days = $item['after_day'];

            $temp[] = [
                "after_day_date" => $comparisonDate->copy()->addDays("{$days}")->format('m/d/Y'),
                "amount" => $amount
            ];
        }

        return $temp;
    }


    public function toCoordinates($address)
    {
        $apiKey = config('legacy.GOOGLE_MAPS_API_KEY');
        $apiUrl = "https://maps.google.com/maps/api/geocode/json";

        $response = Http::withOptions([
            'verify' => false,
        ])->get($apiUrl, [
                    'address' => $address,
                    'key' => "{$apiKey}"
                ]);

        if ($response->failed()) {
            return ['lat' => 0, 'lng' => 0];
        }

        $data = $response->object();
        $status = $data->status ?? '';

        if (in_array($status, ['OVER_QUERY_LIMIT', 'ZERO_RESULTS', 'REQUEST_DENIED'])) {
            return ['lat' => 0, 'lng' => 0];
        }

        return [
            'lat' => $data->results[0]->geometry->location->lat ?? 0,
            'lng' => $data->results[0]->geometry->location->lng ?? 0
        ];
    }

    public function getProgramActiveButton($financing)
    {
        $pto = $rent = $buy = false;

        if (in_array($financing, [1, 2, 4])) {
            $pto = true;
        }

        return ["pto" => $pto, "rent" => $rent, "buy" => $buy];
    }

    public function getVehicleFinancing($id = null)
    {
        $options = ["1" => "Rent", "2" => "Rent To Own", "3" => "Lease", "4" => "Lease To Own"];

        if (is_null($id)) {
            return $options;
        }

        return isset($options[$id]) ? $options[$id] : 'None';
    }

    public function getStates(): array
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];
    }

    public function getCanadaStates(): array
    {
        return [
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NS' => 'Nova Scotia',
            'NT' => 'Northwest Territories',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon',
        ];
    }

    public function getStateName(string $code): string
    {
        return $this->getStates()[$code] ?? 'NEW JERSEY';
    }

    public function checkAutoRenew($renterid, $enddate)
    {
        $user = User::select('auto_renew')->find($renterid);

        if ($user && $user->auto_renew == 0 && Carbon::parse($enddate)->isPast()) {
            return 'text-orange-700';
        }

        return '';
    }

    public function getChildBookingEndDate($bookingId)
    {
        $child = CsOrder::selectRaw("
            MAX(end_datetime) as end_datetime,
            SUM(rent + initial_fee + extra_mileage_fee + damage_fee + uncleanness_fee) as paid_amount,
            SUM(insurance_amt + dia_insu) as insurance,
            SUM(toll + pending_toll) as toll,
            SUM(end_odometer - start_odometer) as mileage,
            SUM(dia_fee) as dia_fee,
            SUM(extra_mileage_fee) as extra_mileage_fee,
            SUM(damage_fee) as damage_fee,
            SUM(lateness_fee) as lateness_fee,
            SUM(uncleanness_fee) as uncleanness_fee
        ")->where(function ($query) use ($bookingId) {
            $query->where('parent_id', $bookingId)
                ->orWhere('id', $bookingId);
        })->where('status', 3)
            ->groupBy('id')
            ->orderBy('id', 'DESC')
            ->first();

        return $child ? $child->toArray() : [];
    }

    public function getVehicleStatus(): array
    {
        return [
            "0" => "Unlisted",
            "1" => "Active",
            "4" => "Inactive",
            "2" => "In Body Shop",
            "3" => "In Maintenance",
            "5" => "Maintenance Issues",
            '6' => 'Booked',
            /* '7'=>'Available', */
            '8' => 'Starter Disabled',
            '9' => 'Starter Enabled',
            '10' => "Waiting For Review",
            "11" => "Deleted",
            "12" => "Undo Deleted"
        ];
    }


    public function getVehicleStatusForChange(): array
    {
        return [
            "0" => "Unlisted",
            "1" => "Active",
            "4" => "Inactive",
            "2" => "In Body Shop",
            "3" => "In Maintenance",
            "5" => "Maintenance Issues",
            '8' => 'Starter Disabled',
            '9' => 'Starter Enabled',
            '10' => "Waiting For Review",
            "11" => "Deleted",
            "12" => "Undo Deleted"
        ];
    }

    public function getFleetExpenseStatus(): array
    {
        return $this->getVehicleIssueType();
    }

    public function getVehicleIssueType(): array
    {
        return [
            "1" => "Accident",
            "2" => "Roadside Assistance",
            "3" => "Mechanical Issues",
            "4" => "TDK Violation",
            "5" => "Vehicle Cleaning",
            "6" => "Maintenance",
            "7" => "Inspection Scan",
            "8" => "Pending Booking Related",
            "9" => "Vehicle License Plate Received",
            "10" => "Vehicle Insurance Ticket"
        ];
    }

    public function getWeekdays(): array
    {
        return [
            "sun" => "Sunday",
            "mon" => "Monday",
            "tue" => "Tuesday",
            'wed' => "Wednesday",
            'thu' => "Thursday",
            "fri" => "Friday",
            'sat' => "Saturday"
        ];
    }

    public function getfullWeekdays(): array
    {
        return [
            "sunday" => "Sunday",
            "monday" => "Monday",
            "tuesday" => "Tuesday",
            'wednesday' => "Wednesday",
            'thursday' => "Thursday",
            "friday" => "Friday",
            'saturday' => "Saturday"
        ];
    }


    public function getBookingTotalDeposit($bookingId)
    {
        $orderIds = CsOrder::where('id', "{$bookingId}")
            ->orWhere('parent_id', "{$bookingId}")
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0;
        }

        $payments = CsOrderPayment::whereIn('cs_order_id', $orderIds)
            ->where('type', 1)
            ->where('status', 1)
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalAmount = $payments->sum('amount');
        $currencyCode = $payments->first()->currency ?? 'USD';

        return CakeNumber::currency($totalAmount, "{$currencyCode}");
    }

    public function getBookingTotalInitialFee($bookingId)
    {
        $orderIds = CsOrder::where('id', "{$bookingId}")
            ->orWhere('parent_id', "{$bookingId}")
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0;
        }

        $payments = CsOrderPayment::whereIn('cs_order_id', $orderIds)
            ->where('type', 3)
            ->where('status', 1)
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalAmount = $payments->sum('amount');
        $currencyCode = $payments->first()->currency ?? 'USD';

        return CakeNumber::currency($totalAmount, "{$currencyCode}");
    }

    public function getBookingTotalInsurance($bookingId)
    {

        $orderIds = CsOrder::where('id', "{$bookingId}")
            ->orWhere('parent_id', "{$bookingId}")
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0;
        }

        $payments = CsOrderPayment::whereIn('cs_order_id', $orderIds)
            ->where('type', 4)
            ->where('status', 1)
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalAmount = $payments->sum('amount');
        $currencyCode = $payments->first()->currency ?? 'USD';

        return CakeNumber::currency($totalAmount, "{$currencyCode}");
    }

    public function days_between_dates($start, $end)
    {
        return (int) (new \DateTime($start))->diff(new \DateTime($end))->days;
    }

    public function years_between_dates($start, $end)
    {
        return (int) (new \DateTime($start))->diff(new \DateTime($end))->y;
    }

    public function FileSizeInBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        return match ($last) {
            'g' => $val * 1073741824,
            'm' => $val * 1048576,
            'k' => $val * 1024,
            default => $val,
        };
    }

    public function programOptions($selected = '')
    {
        $return = ["0" => "General Access", "1" => "Rideshare (Uber/Lfyt Acess)", "2" => "Both"];

        if ($selected != '') {

            if ($selected == 0) {
                $return = ["0" => "General Access"];
            }

            if ($selected == 1) {
                $return = ["1" => "Rideshare (Uber/Lfyt Acess)"];
            }

            if ($selected == 2) {
                $return = ["0" => "General Access", "1" => "Rideshare (Uber/Lfyt Acess)", "2" => "Both"];
            }
        }

        return $return;
    }

    public function programOptionsForOffers($selected = '')
    {
        $return = ["1" => "Subscription", "0" => "Rideshare"];

        if ($selected != '') {

            if ($selected == 0) {
                $return = ["1" => "Subscription"];
            }

            if ($selected == 1) {
                $return = ["0" => "Rideshare"];
            }

            if ($selected == 2) {
                $return = ["1" => "Subscription", "0" => "Rideshare"];
            }

        }

        return $return;
    }

    public function financingOptions($selecteds = [])
    {
        $return = $this->getVehicleFinancing();

        if (is_array($selecteds)) {
            $temp = [];
            foreach ($selecteds as $selected) {
                if (isset($return[$selected])):
                    $temp[$selected] = $return[$selected];
                endif;
            }
            $return = $temp;
        }

        return $return;
    }

    public function calculateAPRSellingPrice($monthlyPayment = 0)
    {
        return sprintf('%d', ($monthlyPayment * 25 / 100));
    }


    public function makeRentalDays($min_rental_period = 336)
    {
        $return = [
            "1" => "1 Day",
            "2" => "2 Days",
            "3" => "3 Days",
            "4" => "4 Days",
            "5" => "5 Days",
            "6" => "6 Days",
            "7" => "7 Days",
            "14" => "14 Days",
            "28" => "28 Days"
        ];

        if ($min_rental_period < 24) {
            return $return;
        }

        $days = $min_rental_period < 24 ? 1 : floor($min_rental_period / 24);
        $hours = $min_rental_period < 24 ? 0 : $min_rental_period % 24;

        if ($hours) {
            $days++;
        }

        if ($days >= 30) {
            return ["$days" => "$days Days"];
        }

        if ($days >= 7 && $days <= 14) {
            return ["7" => "7 Days", "14" => "14 Days", "28" => "28 Days"];
        }

        if ($days > 7 && $days <= 14) {
            return ["14" => "14 Days", "28" => "28 Days"];
        }

        if ($days > 14 && $days <= 28) {
            return ["$days" => "$days Days", "28" => "28 Days"];
        }

        $return = [];

        while ($days <= 7) {
            $return[$days] = "$days Days";
            $days++;
        }

        $return["14"] = "14 Days";
        $return["28"] = "28 Days";

        return $return;
    }

    public function getCheckrTypeValue($all = false, $key = false, $val = false)
    {
        $return = [
            '0' => "Unverified",
            '1' => 'Clear',
            '2' => 'Processing',
            '3' => 'Consider',
            '4' => 'Suspended/Disputed',
            '5' => 'Report Requested'
        ];

        if ($all) {
            return $return;
        }

        if ($key) {
            return $return[$key];
        }

        if ($val) {
            $return = array_flip($return);
            return $return[$val];
        }
    }

    public function getCheckrTypeValueForEditable()
    {
        $chkrTemp = [];
        $chkrstatus = $this->getCheckrTypeValue(true);

        foreach ($chkrstatus as $key => $val) {
            $chkrTemp[] = ["value" => $key, "text" => $val];
        }

        return $chkrTemp;
    }


    public function getExactDateAfterMonths($timestamp, $months)
    {

        $date = Carbon::createFromTimestamp($timestamp);
        $currentDay = $date->day;
        $targetMonth = $date->copy()->startOfMonth()->addMonths("{$months}");
        $daysInTargetMonth = $targetMonth->daysInMonth;
        $daysToSubtract = 0;

        if ($currentDay > $daysInTargetMonth) {
            $daysToSubtract = $currentDay - $daysInTargetMonth;
        }

        return $date->copy()
            ->addMonths("{$months}")
            ->subDays("{$daysToSubtract}")
            ->timestamp;
    }

    public function getReservationStatus($all = false, $key = null)
    {
        if ($all === true && $key === true) {
            return [
                "0" => "New",
                "4" => "Aware of booking",
                "5" => "Preparing",
                "6" => "Car scheduled to be ready",
                "7" => "Car is ready",
                "10" => "Internal Note"
            ];
        }

        $status = [
            "0" => "New",
            "1" => "Started",
            "2" => "Canceled",
            "3" => "Completed",
            "4" => "Aware of booking",
            "5" => "Preparing",
            "6" => "Car scheduled to be ready",
            "7" => "Car is ready",
            "10" => "Internal Note"
        ];

        if ($all) {
            return $status;
        }

        if (isset($status[$key])) {
            return $status[$key];
        }
    }

    public function getPickupDateAsPerDealerSetting($data)
    {
        $insideWorkingHours = $dayAvailable = true;
        $preparationTime = $data['preparation_time'] ?? 0;
        $startDateTime = now()->addHours("{$preparationTime}");
        $startDateStr = $startDateTime->toDateString(); // Y-m-d
        $dealerId = $data['user_id'];
        $workingHours = CsWorkingHour::where('user_id', $dealerId)->first();

        if (!empty($workingHours)) {
            $dayFull = strtolower($startDateTime->format('l')); // e.g. "monday"
            $dayShort = substr($dayFull, 0, 3); // e.g. "mon"

            if ($workingHours->{$dayFull} == 0) {
                $insideWorkingHours = $dayAvailable = false;
            }

            $workingdayStartTime = $workingHours->{"{$dayShort}_start"};
            $workingdayEndTime = $workingHours->{"{$dayShort}_end"};
            $currentTimestamp = $startDateTime->timestamp;
            $openingTimestamp = strtotime("{$startDateStr} {$workingdayStartTime}");
            $closingTimestamp = strtotime("{$startDateStr} {$workingdayEndTime}");

            if ($currentTimestamp >= $openingTimestamp && $currentTimestamp <= $closingTimestamp) {
                // Inside hours - do nothing
            } else {
                $insideWorkingHours = false;
            }
        } else {
            // Default if no record: set to 08:00:00
            $startDateTime = Carbon::parse("{$startDateStr} 08:00:00");
            $startDateStr = $startDateTime->toDateString();
        }

        if (!$insideWorkingHours) {
            $openingTimestamp = strtotime("{$startDateStr} {$workingdayStartTime}");
            $closingTimestamp = strtotime("{$startDateStr} {$workingdayEndTime}");

            if ($startDateTime->timestamp <= $openingTimestamp) {
                // Keep startDate the same
            } elseif ($startDateTime->timestamp > $closingTimestamp) {
                // Increment by +1 day
                $startDateStr = date('Y-m-d', strtotime("{$startDateStr} +1 day"));
            }

            if (!$dayAvailable) {
                $nextWorkingDay = $nextWorkingDate = '';

                for ($i = 1; $i <= 7; $i++) {
                    $nextTimestamp = strtotime("{$startDateStr}") + (86400 * $i);
                    $nextWorkingDate = date('Y-m-d', $nextTimestamp);
                    $nextWorkingDay = strtolower(date('l', $nextTimestamp));

                    if (isset($workingHours->{$nextWorkingDay}) && $workingHours->{$nextWorkingDay} == 1) {
                        break;
                    }
                }

                if ($nextWorkingDay != '') {
                    $nextWorkingDayShort = substr($nextWorkingDay, 0, 3);
                    $workingdayStartTime = $workingHours->{"{$nextWorkingDayShort}_start"};
                    return date('m/d/Y h:i A', strtotime("{$nextWorkingDate} {$workingdayStartTime}"));
                }

                return $startDateTime->format('m/d/Y h:i A');
            } else {
                return date('m/d/Y h:i A', strtotime("{$startDateStr} {$workingdayStartTime}"));
            }
        }

        return $startDateTime->format('m/d/Y h:i A');
    }

    public function pushDataToIdology(array $requestBody = [])
    {
        $url = 'https://web.idologylive.com/api/scan-capture.svc';
        $response = Http::asForm()
            ->timeout(160)
            ->post($url, $requestBody);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function distanceBetweenTwoGeoPoints($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);
        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);
        //Haversine Formula
        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;
        $val = pow(sin($dlati / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlong / 2), 2);
        $res = 2 * asin(sqrt($val));
        $radius = 3959;
        return ($res * $radius); //miles
    }


    public function filterMultiLocationDealers($locs, $lat, $long, $radius)
    {
        $return = [];
        foreach ($locs as $dealerid => $locations) {
            foreach ($locations as $location) {
                $distance = $this->distanceBetweenTwoGeoPoints($location['lat'], $location['lng'], $lat, $long);
                if ($distance <= $radius) {
                    $return[] = $dealerid;
                    break 1;
                }
            }
        }
        return array_unique($return);
    }


    public function getInsurancePayer($payer = null)
    {
        $insurances = [
            0 => "Driveitaway Fleet",//charge to driver and dont transfer to dealer
            1 => "Dealer Direct",//Chargeable to Dealer and dont transfer to dealer;
            2 => "Dealer Fleet",//Paid By Driver but Transferrable to Dealer;
            3 => "BYOI via Driver",//Driver will have his own insurance;
            4 => "BYOI via DIA",//charge to driver and  transfer 100% to Lender (Dealer will choose lender account on Payment setting page)
            5 => 'BYOI via Driver Financed',
            6 => 'BYOI via broker DIA financed',
            7 => 'DIA Fleet Back Up'
        ];

        if (!is_null($payer)) {
            return isset($insurances[$payer]) ? $insurances[$payer] : '';
        }

        return $insurances;
    }
    public function toBytes($str)
    {
        return $this->FileSizeInBytes($str);
    }

    public function getVehicleMovementFromHistory($id = null)
    {
        return CsTrackVehicle::getVehicleMovementFromHistory($id);
    }

    public function getLastChecklist($orderCheckLists, $checklists)
    {
        if (empty($orderCheckLists)) {
            return head($checklists); // Laravel helper for current()
        }

        $formattedJson = preg_replace('/_value/', '', $orderCheckLists);
        $processed = collect(json_decode($formattedJson, true));

        $completedKeys = $processed->reject(function ($value, $key) {
            return str_contains($key, '_note') || in_array($value, ['No', 'InProgress']);
        })->keys();

        $remaining = array_diff(array_keys($checklists), $completedKeys->toArray());
        $firstMissing = head($remaining);

        return $firstMissing !== false ? $checklists[$firstMissing] : 'N/A';
    }


    public function getMissingChecklist($orderCheckLists, $checklists, $validateMaybe = false)
    {
        if (empty($orderCheckLists)) {
            return array_keys($checklists);
        }

        $jsonString = preg_replace('/_value/', '', $orderCheckLists);
        $items = collect(json_decode($jsonString, true));

        $filtered = $items->forget(function ($value, $key) {
            return str_contains($key, '_note');
        });

        $completedKeys = $filtered->filter(function ($value) use ($validateMaybe) {
            if ($validateMaybe) {
                return $value === 'Yes';
            }
            return $value !== 'No';
        })->keys();

        return array_values(array_diff(array_keys($checklists), $completedKeys->toArray()));
    }

    public function getAvailabilityOptions($val = null)
    {
        $availabities = [
            "0" => "Available Now",
            "1" => "Waitlist",
            "2" => "Normal Wait Time"
        ];

        if (!is_null($val)) {
            return isset($availabities[$val]) ? $availabities[$val] : '';
        }

        return $availabities;
    }
}
