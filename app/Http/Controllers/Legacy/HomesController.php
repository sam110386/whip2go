<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
/**
 * CakePHP `HomesController` — public marketing pages (no dealer session required).
 */
class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index()
    {
        return view('homes.index', [
            'title_for_layout' => 'Whip Around Town Today',
        ]);
    }

    public function aboutus()
    {
        return view('homes.aboutus', ['title_for_layout' => 'About Us']);
    }

    public function drivers()
    {
        return view('homes.drivers', ['title_for_layout' => 'Drivers']);
    }

    public function dealers()
    {
        return view('homes.dealers', ['title_for_layout' => 'Dealers']);
    }

    public function featured()
    {
        return view('homes.featured', ['title_for_layout' => 'Featured']);
    }

    public function privacy()
    {
        return view('homes.privacy', ['title_for_layout' => 'Privacy Policy']);
    }

    public function terms()
    {
        return view('homes.terms', ['title_for_layout' => 'Terms of Service']);
    }

    public function support(Request $request)
    {
        $page = null;
        if (Schema::hasTable('pages')) {
            $page = DB::table('pages')
                ->where('page_code', 'contact-us')
                ->whereIn('status', ['1', 1])
                ->first();
            if ($page === null) {
                $page = DB::table('pages')->where('page_code', 'contact-us')->first();
            }
        }

        if ($request->isMethod('post')) {
            $data = $request->input('Contact', []);
            $first = trim((string) ($data['first_name'] ?? ''));
            $last = trim((string) ($data['last_name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $phoneRaw = (string) ($data['phone'] ?? $data['phone1'] ?? '');
            $phone = preg_replace('/\D/', '', str_replace(['(', ')', '-', ' '], '', $phoneRaw));
            $comment = trim((string) ($data['comment'] ?? ''));

            if ($first === '' || $last === '' || $email === '' || $comment === '') {
                return redirect('/homes/support')->withInput()->with('error', 'Please complete all required fields.');
            }

            $to = trim((string) config('legacy.homes_support_to', ''));
            if ($to === '') {
                $to = trim((string) config('mail.from.address', ''));
            }

            $templateRow = null;
            if (Schema::hasTable('email_templates')) {
                $templateRow = DB::table('email_templates')
                    ->where('title', 'CONTACTUS')
                    ->select(['description', 'subject'])
                    ->first();
            }

            $emailContent = $templateRow->description ?? '';
            $emailSubject = $templateRow->subject ?? 'Support request';
            $company = (string) config('app.name', 'DriveItAway');
            $userfname = ucwords($first);
            $userlname = ucwords($last);
            $username = ucwords($first . ' ' . $last);
            $original = ['{COMPANY}', '{FIRST_NAME}', '{LAST_NAME}', '{PHONE_NUMBER}', '{EMAIL}', '{CONTACT_COMMNET}', '{USERNAME}'];
            $values = [$company, $userfname, $userlname, $phone, $email, $comment, $username];
            $finalEmail = str_replace($original, $values, $emailContent);
            $body = '<html><body><table><tr><td>' . $finalEmail . '</td></tr></table></body></html>';
            $subject = $company . ' - Support Page';

            if ($to === '') {
                Log::warning('homes.support: no recipient configured (legacy.homes_support_to / mail.from.address).');

                return redirect('/homes/support')->withInput()->with('error', 'Support email is not configured on this server.');
            }

            try {
                Mail::html($body, function ($message) use ($to, $subject, $email, $username) {
                    $message->to($to)
                        ->replyTo($email, $username)
                        ->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::error('homes.support mail failed: ' . $e->getMessage());

                return redirect('/homes/support')->withInput()->with('error', 'Could not send your message. Please try again later.');
            }

            return redirect('/homes/support')->with('success', 'Your message has been sent. Thank you.');
        }

        return view('homes.support', [
            'title_for_layout' => 'Support',
            'page' => $page,
            'metakeywords' => $page->meta_keyword ?? '',
            'metadescription' => $page->meta_description ?? '',
            'metatitle' => $page->meta_title ?? 'Support',
            'discription' => $page->description ?? '',
        ]);
    }

    public function driveitaway()
    {
        return view('homes.driveitaway', [
            'title_for_layout' => 'DriveItAway Today',
        ]);
    }

    public function driveitawayaboutus()
    {
        return view('homes.driveitawayaboutus', [
            'title_for_layout' => 'Company',
        ]);
    }

    public function driveitawaydrivers()
    {
        return view('homes.driveitawaydrivers', [
            'title_for_layout' => 'Drivers',
        ]);
    }

    public function driveitawaydealers()
    {
        return view('homes.driveitawaydealers', [
            'title_for_layout' => 'Dealers',
        ]);
    }

    public function press_kit_facts_about_driveItAway()
    {
        return view('homes.press_kit_facts_about_drive_it_away', [
            'title_for_layout' => 'Press Kit – Facts About DriveItAway',
        ]);
    }

    public function leadership_and_company_mission()
    {
        return view('homes.leadership_and_company_mission', [
            'title_for_layout' => 'Leadership and Company Mission',
        ]);
    }

    public function publications_blog_industry_videos()
    {
        return view('homes.publications_blog_industry_videos', [
            'title_for_layout' => 'Publications, Blog & Industry Videos',
        ]);
    }

    public function event_industry_presentation()
    {
        return view('homes.event_industry_presentation', [
            'title_for_layout' => 'Come See Us at Our Next Event or Industry Presentation',
        ]);
    }

    public function press_releases_and_news()
    {
        return view('homes.press_releases_and_news', [
            'title_for_layout' => 'Press Releases & In The News',
        ]);
    }

    public function contactus(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'Contact.first_name' => 'required|string|max:160',
                'Contact.last_name' => 'required|string|max:160',
                'Contact.email' => 'required|email|max:255',
                'Contact.phone' => 'required|string|max:80',
                'Contact.usertype' => 'required|string|in:Driver,Dealer,Other',
                'Contact.comment' => 'nullable|string|max:20000',
            ]);
            $data = $request->input('Contact', []);
            $first = ucwords(strtolower(trim($data['first_name'])));
            $last = ucwords(strtolower(trim($data['last_name'])));
            $email = trim($data['email']);
            $phone = trim($data['phone']);
            $comment = trim((string) ($data['comment'] ?? ''));
            $usertype = $data['usertype'];

            $to = trim((string) config('legacy.homes_driveitaway_contactus_to', ''));
            if ($to === '') {
                $to = trim((string) config('mail.from.address', ''));
            }
            if ($to === '') {
                Log::warning('homes.contactus: no recipient (legacy.homes_driveitaway_contactus_to / mail.from.address).');

                return redirect('/contactus')->withInput()->with('error', 'Contact email is not configured on this server.');
            }

            $fromAddr = trim((string) config('legacy.homes_driveitaway_contactus_from_address', ''));
            if ($fromAddr === '') {
                $fromAddr = trim((string) config('mail.from.address', ''));
            }
            if ($fromAddr === '') {
                return redirect('/contactus')->withInput()->with('error', 'Mail “from” address is not configured (legacy.homes_driveitaway_contactus_from_address / MAIL_FROM_ADDRESS).');
            }
            $fromName = (string) config('legacy.homes_driveitaway_contactus_from_name', 'Whip Team');
            $subject = (string) config('legacy.homes_driveitaway_contactus_subject', 'Whip - Contact Us');

            $html = view('emails.contact_us', [
                'logourl' => url(legacy_asset('img/logo-white.png')),
                'FIRST_NAME' => $first,
                'LAST_NAME' => $last,
                'EMAIL' => $email,
                'PHONE_NUMBER' => $phone,
                'USERTYPE' => $usertype,
                'ADDRESS' => '',
                'COMMENT' => $comment,
            ])->render();

            try {
                Mail::html($html, function ($message) use ($to, $subject, $fromAddr, $fromName, $email, $first, $last) {
                    $message->to($to)->subject($subject);
                    $message->from($fromAddr, $fromName);
                    $message->replyTo($email, $first . ' ' . $last);
                });
            } catch (\Throwable $e) {
                Log::error('homes.contactus mail failed: ' . $e->getMessage());

                return redirect('/contactus')->withInput()->with('error', 'Could not send your message. Please try again later.');
            }

            return redirect('/contactus')->with('success', 'Thank you for your message. We will get back to you after review all details.');
        }

        return view('homes.contactus', [
            'title_for_layout' => 'Contact Us',
        ]);
    }

    public function nada(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'Contact.name' => 'required|string|max:200',
                'Contact.email' => 'required|email|max:255',
                'Contact.phone' => 'required|string|max:80',
                'Contact.organization' => 'required|string|max:200',
                'Contact.comment' => 'nullable|string|max:20000',
            ]);
            $data = $request->input('Contact', []);
            $name = ucwords(strtolower(trim($data['name'])));
            $organization = ucwords(strtolower(trim($data['organization'])));
            $email = trim($data['email']);
            $phone = trim($data['phone']);
            $comment = trim((string) ($data['comment'] ?? ''));

            $to = trim((string) config('legacy.homes_driveitaway_nada_to', ''));
            if ($to === '') {
                $to = trim((string) config('mail.from.address', ''));
            }
            if ($to === '') {
                Log::warning('homes.nada: no recipient (legacy.homes_driveitaway_nada_to / mail.from.address).');

                return redirect('/nada2019')->withInput()->with('error', 'Registration email is not configured on this server.');
            }

            $fromAddr = trim((string) config('legacy.homes_driveitaway_nada_from_address', ''));
            if ($fromAddr === '') {
                $fromAddr = trim((string) config('mail.from.address', ''));
            }
            if ($fromAddr === '') {
                return redirect('/nada2019')->withInput()->with('error', 'Mail “from” address is not configured (legacy.homes_driveitaway_nada_from_address / MAIL_FROM_ADDRESS).');
            }
            $fromName = (string) config('legacy.homes_driveitaway_nada_from_name', 'Whip Team');
            $subject = (string) config('legacy.homes_driveitaway_nada_subject', 'DriveitAway - NADA 2019');

            $html = view('emails.nada', [
                'logourl' => url(legacy_asset('img/logo-white.png')),
                'NAME' => $name,
                'EMAIL' => $email,
                'PHONE' => $phone,
                'ORGANIZATION' => $organization,
                'COMMENT' => $comment,
            ])->render();

            try {
                Mail::html($html, function ($message) use ($to, $subject, $fromAddr, $fromName, $email, $name) {
                    $message->to($to)->subject($subject);
                    $message->from($fromAddr, $fromName);
                    $message->replyTo($email, $name);
                });
            } catch (\Throwable $e) {
                Log::error('homes.nada mail failed: ' . $e->getMessage());

                return redirect('/nada2019')->withInput()->with('error', 'Could not send your message. Please try again later.');
            }

            return redirect('/nada2019')->with('success', 'Thank you for your message. We will contact you shortly.');
        }

        return view('homes.nada', [
            'title_for_layout' => 'NADA 2019',
        ]);
    }
}
