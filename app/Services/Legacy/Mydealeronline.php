<?php

namespace App\Services\Legacy;

/**
 * Port of CakePHP app/Lib/Mydealeronline.php
 * Maps vehicle make/model to body-type categories (Sedan, SUV, Luxury, Minivan).
 */
class Mydealeronline
{
    /**
     * @return string  Vehicle type classification (e.g. "Sedan", "SUV", "Luxury", "Minivan")
     */
    public function getVehicleType(string $make, string $model): string
    {
        $make = strtolower(trim($make));
        $model = strtolower(trim($model));
        $map = self::vehicleTypeMap();

        return $map[$make][$model] ?? 'Sedan';
    }

    private static function vehicleTypeMap(): array
    {
        return [
            'acura' => ['ilx' => 'Sedan', 'ilx hybrid' => 'Sedan', 'mdx' => 'SUV', 'rdx' => 'Sedan', 'rl' => 'Luxury', 'rlx' => 'Luxury', 'tl' => 'Sedan', 'tlx' => 'Sedan', 'tsx' => 'Sedan', 'tsx sport wagon' => 'Sedan', 'zdx' => 'Sedan'],
            'audi' => ['a3 sedan' => 'Sedan', 'a3 hatchback' => 'Sedan', 'a3 e-tron' => 'Sedan', 'a4' => 'Sedan', 'a6' => 'Luxury', 'a7' => 'Luxury', 'a8' => 'Luxury', 'allroad' => 'Luxury', 'q3' => 'Sedan', 'q5' => 'Luxury', 'q7' => 'SUV', 'rs 7' => 'Luxury', 's3' => 'Sedan', 's4' => 'Sedan', 's6' => 'Luxury', 's7' => 'Luxury', 's8' => 'Luxury', 'sq5' => 'Luxury', 'tt' => 'Sedan', 'tts' => 'Sedan'],
            'bmw' => ['3 series' => 'Sedan', '3 series gran turismo' => 'Luxury', '4 series gran coupe' => 'Sedan', '5 series' => 'Luxury', '5 series gran turismo' => 'Luxury', '6 series gran coupe' => 'Luxury', '7 series' => 'Luxury', 'activehybrid 5' => 'Sedan', 'activehybrid 7' => 'Luxury', 'activehybrid x6' => 'Luxury', 'alpina b6 gran coupe' => 'Sedan', 'alpina b7' => 'Luxury', 'i3' => 'Sedan', 'm3' => 'Sedan', 'm5' => 'Luxury', 'm6 gran coupe' => 'Luxury', 'x1' => 'Sedan', 'x3' => 'Luxury', 'x4' => 'Luxury', 'x5' => 'Luxury', 'x5 m' => 'Luxury', 'x6' => 'Luxury', 'x6 m' => 'Luxury'],
            'buick' => ['anthem envision' => 'Sedan', 'enclave' => 'SUV', 'encore' => 'Sedan', 'lacrosse' => 'Luxury', 'lucerne' => 'Sedan', 'regal' => 'Sedan', 'verano' => 'Sedan'],
            'cadillac' => ['ats' => 'Sedan', 'ats-v' => 'Sedan', 'cts' => 'Luxury', 'cts coupe' => 'Sedan', 'cts wagon' => 'Luxury', 'cts-v' => 'Luxury', 'cts-v coupe' => 'Sedan', 'cts-v wagon' => 'Luxury', 'dts' => 'Luxury', 'escalade' => 'SUV', 'escalade esv' => 'SUV', 'escalade hybrid' => 'SUV', 'srx' => 'Luxury', 'sts' => 'Luxury', 'xt5' => 'Luxury', 'xts' => 'Luxury'],
            'chevrolet' => ['aveo' => 'Sedan', 'captiva sport' => 'Sedan', 'city express' => 'Sedan', 'cobalt' => 'Sedan', 'colorado' => 'Sedan', 'cruze' => 'Sedan', 'cruze limited sedan' => 'Sedan', 'equinox' => 'Sedan', 'hhr' => 'Sedan', 'impala' => 'Sedan', 'impala limited' => 'Sedan', 'malibu' => 'Sedan', 'malibu hybrid' => 'Sedan', 'silverado 1500' => 'Sedan', 'silverado 2500hd' => 'Sedan', 'silverado 3500hd' => 'Sedan', 'sonic' => 'Sedan', 'spark' => 'Sedan', 'spark ev' => 'Sedan', 'ss' => 'Sedan', 'suburban' => 'SUV', 'tahoe' => 'SUV', 'tahoe hybrid' => 'SUV', 'traverse' => 'Minivan', 'trax' => 'Sedan', 'volt' => 'Sedan'],
            'chrysler' => ['200' => 'Sedan', '300' => 'Sedan', 'pacifica' => 'Minivan', 'pt cruiser' => 'Sedan', 'sebring' => 'Sedan', 'town and country' => 'Minivan'],
            'dodge' => ['avenger' => 'Sedan', 'caliber' => 'Sedan', 'charger' => 'Sedan', 'dakota' => 'Sedan', 'dart' => 'Sedan', 'durango' => 'SUV', 'grand caravan' => 'Minivan', 'journey' => 'Minivan', 'nitro' => 'Sedan'],
            'fiat' => ['500' => 'Sedan', '500e' => 'Sedan', '500l' => 'Sedan'],
            'ford' => ['c-max energi' => 'Sedan', 'c-max hybrid' => 'Sedan', 'crown victoria' => 'Sedan', 'edge' => 'Sedan', 'escape' => 'Sedan', 'escape hybrid' => 'Sedan', 'expedition' => 'SUV', 'explorer' => 'SUV', 'fiesta' => 'Sedan', 'flex' => 'SUV', 'focus' => 'Sedan', 'focus st' => 'Sedan', 'fusion' => 'Sedan', 'fusion energi' => 'Sedan', 'fusion hybrid' => 'Sedan', 'taurus' => 'Sedan', 'transit connect' => 'Sedan'],
            'gmc' => ['acadia' => 'SUV', 'canyon' => 'Sedan', 'envoy' => 'Sedan', 'sierra 1500' => 'Sedan', 'sierra 2500hd' => 'Sedan', 'sierra 3500hd' => 'Sedan', 'terrain' => 'Sedan', 'yukon' => 'SUV', 'yukon hybrid' => 'SUV', 'yukon suv' => 'SUV'],
            'honda' => ['accord' => 'Sedan', 'accord crosstour' => 'Sedan', 'accord hybrid' => 'Sedan', 'accord plug-in hybrid' => 'Sedan', 'civic' => 'Sedan', 'cr-v' => 'Sedan', 'cr-z' => 'Sedan', 'crosstour' => 'Sedan', 'element' => 'Sedan', 'fit' => 'Sedan', 'fit ev' => 'Sedan', 'hr-v' => 'Sedan', 'insight' => 'Sedan', 'odyssey' => 'Minivan', 'pilot' => 'SUV'],
            'hummer' => ['h3' => 'Sedan'],
            'hyundai' => ['accent' => 'Sedan', 'azera' => 'Sedan', 'elantra' => 'Sedan', 'elantra gt' => 'Sedan', 'elantra touring' => 'Sedan', 'equus' => 'Luxury', 'genesis' => 'Luxury', 'santa fe' => 'Minivan', 'santa fe sport' => 'Sedan', 'sonata' => 'Sedan', 'sonata hybrid' => 'Sedan', 'tucson' => 'Sedan', 'veloster' => 'Sedan', 'veracruz' => 'SUV'],
            'infiniti' => ['ex' => 'Sedan', 'ex35' => 'Sedan', 'fx' => 'Sedan', 'fx35' => 'Sedan', 'fx50' => 'Sedan', 'g sedan' => 'Sedan', 'g37 sedan' => 'Sedan', 'jx' => 'SUV', 'm' => 'Luxury', 'm35' => 'Luxury', 'm37' => 'Luxury', 'm45' => 'Luxury', 'm56' => 'Luxury', 'q40' => 'Sedan', 'q50' => 'Sedan', 'q70' => 'Luxury', 'qx' => 'SUV', 'qx50' => 'Sedan', 'qx56' => 'SUV', 'qx60' => 'SUV', 'qx70' => 'Sedan', 'qx80' => 'SUV'],
            'jaguar' => ['f-pace' => 'Luxury', 'xf' => 'Luxury', 'xj' => 'Luxury'],
            'jeep' => ['cherokee' => 'Sedan', 'commander' => 'SUV', 'compass' => 'Sedan', 'grand cherokee' => 'Sedan', 'grand cherokee srt' => 'Sedan', 'liberty' => 'Sedan', 'patriot' => 'Sedan', 'renegade' => 'Sedan', 'wrangler' => 'Sedan'],
            'kia' => ['amanti' => 'Sedan', 'cadenza' => 'Sedan', 'forte' => 'Sedan', 'k900' => 'Luxury', 'optima' => 'Sedan', 'optima hybrid' => 'Sedan', 'rio' => 'Sedan', 'sedona' => 'SUV', 'sorento' => 'Sedan', 'soul' => 'Sedan', 'soul ev' => 'Sedan', 'sportage' => 'Sedan'],
            'land rover' => ['discovery sport' => 'Sedan', 'lr2' => 'Sedan', 'lr3' => 'Sedan', 'lr4' => 'Luxury', 'range rover' => 'Luxury', 'range rover evoque' => 'Sedan', 'range rover sport' => 'Luxury'],
            'lexus' => ['ct 200h' => 'Sedan', 'es 300h' => 'Luxury', 'gs 350' => 'Luxury', 'gs 450h' => 'Luxury', 'gs 460' => 'Luxury', 'gs f' => 'Sedan', 'gx 460' => 'Luxury', 'hs 250h' => 'Sedan', 'is 250' => 'Sedan', 'is 350' => 'Sedan', 'is f' => 'Sedan', 'ls 460' => 'Luxury', 'ls 600h l' => 'Luxury', 'lx 570' => 'Luxury', 'nx 200t' => 'Sedan', 'nx 300h' => 'Sedan', 'rx 350' => 'Luxury', 'rx 450h' => 'Luxury'],
            'lincoln' => ['mkc' => 'Sedan', 'mks' => 'Sedan', 'mkt' => 'SUV', 'mkx' => 'Luxury', 'mkz' => 'Sedan', 'mkz hybrid' => 'Sedan', 'navigator' => 'SUV', 'town car' => 'Sedan'],
            'maserati' => ['ghibli' => 'Sedan', 'quattroporte' => 'Sedan'],
            'mazda' => ['cx-5' => 'Sedan', 'cx-7' => 'Sedan', 'cx-9' => 'SUV', 'mazda2' => 'Sedan', 'mazda3' => 'Sedan', 'mazda5' => 'Sedan', 'mazda6' => 'Sedan', 'mazdaspeed3' => 'Sedan', 'tribute' => 'Sedan'],
            'mercedes-benz' => ['b-class electric drive' => 'Sedan', 'c-class' => 'Sedan', 'cla-class' => 'Sedan', 'cls-class' => 'Sedan', 'e-class sedan' => 'Luxury', 'e-class wagon' => 'SUV', 'g-class' => 'Luxury', 'gl-class' => 'SUV', 'gla-class' => 'Sedan', 'glc-class' => 'Luxury', 'gle-class' => 'Luxury', 'glk-class' => 'Luxury', 'm-class' => 'Luxury', 'metris passenger van' => 'SUV', 'r-class' => 'Luxury', 's-class' => 'Luxury'],
            'mercury' => ['grand marquis' => 'Sedan', 'mariner' => 'Sedan', 'mariner hybrid' => 'Sedan', 'milan' => 'Sedan', 'milan hybrid' => 'Sedan', 'mountaineer' => 'SUV', 'sable' => 'Sedan'],
            'mini' => ['cooper' => 'Sedan', 'cooper clubman' => 'Sedan', 'cooper countryman' => 'Sedan', 'cooper coupe' => 'Sedan', 'cooper paceman' => 'Sedan'],
            'mitsubishi' => ['eclipse' => 'Sedan', 'endeavor' => 'Sedan', 'galant' => 'Sedan', 'i-miev' => 'Sedan', 'lancer' => 'Sedan', 'lancer evolution' => 'Sedan', 'lancer sportback' => 'Sedan', 'mirage' => 'Sedan', 'outlander' => 'SUV', 'outlander sport' => 'Sedan'],
            'nissan' => ['altima' => 'Sedan', 'altima hybrid' => 'Sedan', 'armada' => 'SUV', 'cube' => 'Sedan', 'juke' => 'Sedan', 'leaf' => 'Sedan', 'maxima' => 'Sedan', 'murano' => 'Sedan', 'murano crosscabriolet' => 'Sedan', 'nv200' => 'Minivan', 'pathfinder' => 'SUV', 'quest' => 'SUV', 'rogue' => 'Sedan', 'rogue select' => 'Sedan', 'sentra' => 'Sedan', 'versa' => 'Sedan', 'versa note' => 'Sedan', 'xterra' => 'Sedan'],
            'pontiac' => ['g6' => 'Sedan', 'g8' => 'Sedan', 'torrent' => 'Sedan', 'vibe' => 'Sedan'],
            'porsche' => ['cayenne' => 'Luxury', 'macan' => 'Luxury', 'panamera' => 'Luxury'],
            'ram' => ['cargo van' => 'Sedan', 'c/v tradesman' => 'Sedan', 'cv tradesman' => 'Sedan', 'dakota' => 'Sedan', 'promaster city' => 'Sedan'],
            'rolls-royce' => ['ghost' => 'Luxury', 'phantom' => 'Luxury'],
            'saab' => ['9-3 griffin' => 'Sedan', '9-4x' => 'Sedan', '9-7x' => 'Sedan'],
            'saturn' => ['vue' => 'Sedan'],
            'scion' => ['iq' => 'Sedan', 'tc' => 'Sedan', 'xb' => 'Sedan', 'xd' => 'Sedan'],
            'smart' => ['fortwo' => 'Sedan'],
            'subaru' => ['forester' => 'Sedan', 'impreza' => 'Sedan', 'impreza wrx' => 'Sedan', 'legacy' => 'Sedan', 'outback' => 'Sedan', 'tribeca' => 'SUV', 'wrx' => 'Sedan', 'xv crosstrek' => 'Sedan'],
            'suzuki' => ['equator' => 'Sedan', 'grand vitara' => 'Sedan', 'kizashi' => 'Sedan', 'sx4' => 'Sedan'],
            'tesla' => ['model s' => 'Luxury', 'model x' => 'SUV'],
            'toyota' => ['4runner' => 'Sedan', 'avalon' => 'Sedan', 'avalon hybrid' => 'Sedan', 'camry' => 'Sedan', 'camry hybrid' => 'Sedan', 'corolla' => 'Sedan', 'fj cruiser' => 'Sedan', 'highlander' => 'SUV', 'highlander hybrid' => 'SUV', 'land cruiser' => 'SUV', 'matrix' => 'Sedan', 'mirai' => 'Sedan', 'prius' => 'Sedan', 'prius c' => 'Sedan', 'prius v' => 'Sedan', 'rav4' => 'Sedan', 'rav4 ev' => 'Sedan', 'sequoia' => 'SUV', 'sienna' => 'Minivan', 'tacoma' => 'Sedan', 'tundra' => 'Sedan', 'venza' => 'Sedan', 'yaris' => 'Sedan'],
            'volkswagen' => ['beetle' => 'Sedan', 'cc' => 'Sedan', 'e-golf' => 'Sedan', 'gli' => 'Sedan', 'golf' => 'Sedan', 'golf r' => 'Sedan', 'golf sportwagen' => 'Sedan', 'gti' => 'Sedan', 'jetta' => 'Sedan', 'jetta gli' => 'Sedan', 'jetta hybrid' => 'Sedan', 'jetta sportwagen' => 'Sedan', 'new beetle' => 'Sedan', 'passat' => 'Sedan', 'routan' => 'Sedan', 'tiguan' => 'Sedan', 'touareg' => 'Sedan'],
            'volvo' => ['c30' => 'Sedan', 's40' => 'Sedan', 's60' => 'Sedan', 's80' => 'Luxury', 'v50' => 'Sedan', 'v60' => 'Sedan', 'v70' => 'Sedan', 'xc60' => 'Sedan', 'xc70' => 'Sedan', 'xc90' => 'SUV'],
        ];
    }
}
