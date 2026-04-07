<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\EmailTemplate;
use App\Models\Legacy\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index()
    {
        return view('legacy.homes.index', [
            'title_for_layout' => 'Whip Around Town Today'
        ]);
    }

    public function support(Request $request)
    {
        $contents = Page::where('page_code', 'contact-us')->first();
        
        $viewData = [
            'metakeywords' => $contents->meta_keyword ?? '',
            'metadescription' => $contents->meta_description ?? '',
            'metatitle' => $contents->meta_title ?? '',
            'discription' => $contents->description ?? ''
        ];

        if ($request->isMethod('post')) {
            $contactData = $request->input('Contact', []);
            $phone = preg_replace('/[^a-zA-Z0-9]/', '', (string)($contactData['phone1'] ?? ''));
            $contactData['phone'] = $phone;

            $emailDetail = EmailTemplate::where('title', 'CONTACTUS')->first();
            $emailContent = $emailDetail->description ?? '';
            $emailSubject = $emailDetail->subject ?? 'Whip - Support Page';

            $userfname = ucwords($contactData['first_name'] ?? '');
            $userlname = ucwords($contactData['last_name'] ?? '');
            $userpnumber = $contactData['phone'] ?? '';
            $email = $contactData['email'] ?? '';
            $comment = $contactData['comment'] ?? '';
            $username = trim($userfname . ' ' . $userlname);

            $company = config('app.name', 'Whip'); // Replaces COMPANYTITLE

            $originalContent = ["{COMPANY}", "{FIRST_NAME}", "{LAST_NAME}", "{PHONE_NUMBER}", "{EMAIL}", "{CONTACT_COMMNET}", "{USERNAME}"];
            $userContent = [$company, $userfname, $userlname, $userpnumber, $email, $comment, $username];
            $finalEmail = str_replace($originalContent, $userContent, $emailContent);

            $body = '
                <html>
                <body>
                <table>
                <tr>
                    <td>' . $finalEmail . '</td>
                </tr>
                </table></body></html>';

            // Laravel Mail mapping equivalent
            Mail::html($body, function ($message) use ($email, $username, $emailSubject) {
                $message->from($email, $username)
                        ->to('apotash01@gmail.com')
                        ->subject($emailSubject);
            });

            return redirect('/homes/support')->with('success', 'Your message has been sent. Thank you.');
        }

        return view('legacy.homes.support', $viewData);
    }

    public function aboutus()
    {
        return view('legacy.homes.aboutus', ['title_for_layout' => 'About Us']);
    }

    public function drivers()
    {
        return view('legacy.homes.drivers', ['title_for_layout' => 'Drivers']);
    }

    public function dealers()
    {
        return view('legacy.homes.dealers', ['title_for_layout' => 'Dealers']);
    }

    public function featured()
    {
        return view('legacy.homes.featured', ['title_for_layout' => 'Featured']);
    }

    public function privacy()
    {
        return view('legacy.homes.privacy'); // layout = without_header_footer
    }

    public function terms()
    {
        return view('legacy.homes.terms'); // layout = without_header_footer
    }

    // DriveItAway grouped functions
    public function driveitaway()
    {
        return view('legacy.homes.driveitaway', ['title_for_layout' => 'DriveItAway Today']);
    }

    public function driveitawayaboutus()
    {
        return view('legacy.homes.driveitawayaboutus', ['title_for_layout' => 'Company']);
    }

    public function driveitawaydrivers()
    {
        return view('legacy.homes.driveitawaydrivers', ['title_for_layout' => 'Drivers']);
    }

    public function driveitawaydealers()
    {
        return view('legacy.homes.driveitawaydealers', ['title_for_layout' => 'Dealers']);
    }

    public function press_kit_facts_about_driveItAway()
    {
        return view('legacy.homes.press_kit_facts_about_driveItAway', ['title_for_layout' => 'Press Kit – Facts About DriveItAway']);
    }

    public function leadership_and_company_mission()
    {
        return view('legacy.homes.leadership_and_company_mission', ['title_for_layout' => 'Leadership and Company Mission']);
    }

    public function publications_blog_industry_videos()
    {
        return view('legacy.homes.publications_blog_industry_videos', ['title_for_layout' => 'Publications, Blog & Industry Videos']);
    }

    public function event_industry_presentation()
    {
        return view('legacy.homes.event_industry_presentation', ['title_for_layout' => 'Come See Us at Our Next Event or Industry Presentation']);
    }

    public function press_releases_and_news()
    {
        return view('legacy.homes.press_releases_and_news', ['title_for_layout' => 'Press Releases & In The News']);
    }

    public function contactus(Request $request)
    {
        if ($request->isMethod('post')) {
            $contactData = $request->input('Contact', []);
            $email = $contactData['email'] ?? '';
            
            if (!empty($email)) {
                $userfname = ucwords($contactData['first_name'] ?? '');
                $userlname = ucwords($contactData['last_name'] ?? '');
                $userpnumber = $contactData['phone'] ?? '';
                $comment = $contactData['comment'] ?? '';
                $userType = $contactData['usertype'] ?? '';

                $viewData = [
                    'logourl' => config('app.url') . '/img/logo-white.png',
                    'FIRST_NAME' => $userfname,
                    'LAST_NAME' => $userlname,
                    'EMAIL' => $email,
                    'PHONE_NUMBER' => $userpnumber,
                    'COMMENT' => $comment,
                    'USERTYPE' => $userType
                ];

                Mail::send('legacy.emails.contact_us', $viewData, function ($message) use ($email) {
                    $message->from('support@whip2go.com', 'Whip Team')
                            ->replyTo('no-reply@whip2go.com')
                            ->to('adam@whip2go.com')
                            ->subject('Whip - Contact Us');
                });

                // Salesforce implementation logic goes here if previously uncommented
                // $this->pushToSalesForce($contactData);

                return redirect('/contactus/')->with('success', 'Thank you for your message. We will get back to you after review all details.');
            }
        }

        return view('legacy.homes.contactus', ['title_for_layout' => 'Contact Us']);
    }

    public function nada(Request $request)
    {
        if ($request->isMethod('post')) {
            $contactData = $request->input('Contact', []);
            $email = $contactData['email'] ?? '';
            
            if (!empty($email)) {
                $username = ucwords($contactData['name'] ?? '');
                $organization = ucwords($contactData['organization'] ?? '');
                $userpnumber = $contactData['phone'] ?? '';
                $comment = $contactData['comment'] ?? '';

                $viewData = [
                    'logourl' => config('app.url') . '/img/logo-white.png',
                    'NAME' => $username,
                    'EMAIL' => $email,
                    'PHONE' => $userpnumber,
                    'ORGANIZATION' => $organization,
                    'COMMENT' => $comment
                ];

                Mail::send('legacy.emails.nada', $viewData, function ($message) {
                    $message->from('admin@whip2go.com', 'Whip Team')
                            ->replyTo('no-reply@whip2go.com')
                            ->to('apotash01@gmail.com')
                            ->subject('DriveitAway - NADA 2019');
                });

                return redirect()->route('legacy.nada')->with('success', 'Thank you for your message. We will contact you shortly.');
            }
        }

        return view('legacy.homes.nada', ['title_for_layout' => 'NADA 2019']);
    }

    protected function pushToSalesForce(array $contactData = [])
    {
        return ['status' => false, 'message' => 'Salesforce integration pending migration'];
    }
}
