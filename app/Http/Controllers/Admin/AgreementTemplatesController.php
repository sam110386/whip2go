<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AgreementTemplatesController extends LegacyAppController
{
    public function index($userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Agreement Templates';
        return view('admin.agreement_templates.index', compact('title', 'userid'));
    }
    public function rental(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect('/admin/users/index')->with('error', 'Invalid user ID');
        }

        $title = 'Update Rental Agreement Template';
        $directory = public_path('files/agreement_templates');
        $customFilePath = "{$directory}/{$userid}_rental.html";
        $defaultFilePath = "{$directory}/rental.html";

        if ($request->isMethod('post') && $request->has('AgreementTemplate.content')) {
            try {
                $content = $request->input('AgreementTemplate.content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';

                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                $filePath = $directory . '/' . $userid . '_rental.html';
                File::put($filePath, $content);

                return redirect()->back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        if (File::exists($customFilePath)) {
            $template = File::get($customFilePath);
        } elseif (File::exists($defaultFilePath)) {
            $template = File::get($defaultFilePath);
        } else {
            $template = '';
        }

        return view('admin.agreement_templates.rental', compact('title', 'template', 'userid'));
    }
    public function rentToOwn(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect('/admin/users/index')->with('error', 'Invalid user ID');
        }

        $title = "Update Rent To Own Agreement Template";
        $directory = public_path('files/agreement_templates');
        $userFilePath = "{$directory}/{$userid}_rent_to_own.html";
        $defaultFilePath = "{$directory}/rent_to_own.html";

        if ($request->isMethod('post') && $request->filled('AgreementTemplate.content')) {
            try {
                $content = $request->input('AgreementTemplate.content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';

                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                File::put($userFilePath, $content);

                return redirect()->back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        if (File::exists($userFilePath)) {
            $template = File::get($userFilePath);
        } elseif (File::exists($defaultFilePath)) {
            $template = File::get($defaultFilePath);
        } else {
            $template = '';
        }

        return view('admin.agreement_templates.rent_to_own', compact('title', 'template', 'userid'));
    }
    public function lease(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect('/admin/users/index')->with('error', 'Invalid user ID');
        }

        $title = "Update Lease Agreement Template";
        $directory = public_path('files/agreement_templates');
        $userFilePath = "{$directory}/{$userid}_lease.html";
        $defaultFilePath = "{$directory}/lease.html";

        if ($request->isMethod('post') && $request->filled('AgreementTemplate.content')) {
            try {
                $content = $request->input('AgreementTemplate.content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';

                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                File::put($userFilePath, $content);

                return redirect()->back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        if (File::exists($userFilePath)) {
            $template = File::get($userFilePath);
        } elseif (File::exists($defaultFilePath)) {
            $template = File::get($defaultFilePath);
        } else {
            $template = '';
        }

        return view('admin.agreement_templates.lease', compact('title', 'template', 'userid'));
    }
    public function leaseToOwn(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect('/admin/users/index')->with('error', 'Invalid user ID');
        }

        $title = "Update Lease To Own Agreement Template";
        $directory = public_path('files/agreement_templates');
        $userFilePath = "{$directory}/{$userid}_lease_to_own.html";
        $defaultFilePath = "{$directory}/lease_to_own.html";

        if ($request->isMethod('post') && $request->filled('AgreementTemplate.content')) {
            try {
                $content = $request->input('AgreementTemplate.content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';

                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                File::put($userFilePath, $content);

                return redirect()->back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        if (File::exists($userFilePath)) {
            $template = File::get($userFilePath);
        } elseif (File::exists($defaultFilePath)) {
            $template = File::get($defaultFilePath);
        } else {
            $template = '';
        }

        return view('admin.agreement_templates.lease_to_own', compact('title', 'template', 'userid'));
    }
}
