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

        if ($request->isMethod('post') && $request->has('AgreementTemplate.content')) {
            try {
                $content = $request->input('AgreementTemplate.content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';
                $directory = public_path('files/agreement_templates');

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

        $title = 'Update Rental Agreement Template';
        $customFilePath = public_path('files/agreement_templates/' . $userid . '_rental.html');
        $defaultFilePath = public_path('files/agreement_templates/rental.html');

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

        $uid = $this->decodeId($userid !== null ? (string) $userid : '');
        if (!$uid) {
            return redirect('/admin/users/index');
        }
        $userid = $uid;
        $useridB64 = base64_encode((string) $userid);

        if ($request->isMethod('post') && $request->filled('content')) {
            try {
                $content = $request->input('content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';
                File::ensureDirectoryExists($this->templateBasePath());
                File::put($this->templateBasePath() . $userid . '_rent_to_own.html', $content);

                return back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $listTitle = 'Update Lease To Own Agreement Template';
        $filePath = $this->templateBasePath() . $userid . '_rent_to_own.html';
        $defaultPath = $this->templateBasePath() . 'rent_to_own.html';
        $template = is_file($filePath) ? File::get($filePath) : (is_file($defaultPath) ? File::get($defaultPath) : '');

        return view('admin.agreement_templates.rent_to_own', compact('listTitle', 'template', 'userid', 'useridB64'));
    }

    public function lease(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $uid = $this->decodeId($userid !== null ? (string) $userid : '');
        if (!$uid) {
            return redirect('/admin/users/index');
        }
        $userid = $uid;
        $useridB64 = base64_encode((string) $userid);

        if ($request->isMethod('post') && $request->filled('content')) {
            try {
                $content = $request->input('content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';
                File::ensureDirectoryExists($this->templateBasePath());
                File::put($this->templateBasePath() . $userid . '_lease.html', $content);

                return back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $listTitle = 'Update Lease To Own Agreement Template';
        $filePath = $this->templateBasePath() . $userid . '_lease.html';
        $defaultPath = $this->templateBasePath() . 'lease.html';
        $template = is_file($filePath) ? File::get($filePath) : (is_file($defaultPath) ? File::get($defaultPath) : '');

        return view('admin.agreement_templates.lease', compact('listTitle', 'template', 'userid', 'useridB64'));
    }

    public function leaseToOwn(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $uid = $this->decodeId($userid !== null ? (string) $userid : '');
        if (!$uid) {
            return redirect('/admin/users/index');
        }
        $userid = $uid;
        $useridB64 = base64_encode((string) $userid);

        if ($request->isMethod('post') && $request->filled('content')) {
            try {
                $content = $request->input('content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';
                File::ensureDirectoryExists($this->templateBasePath());
                File::put($this->templateBasePath() . $userid . '_lease_to_own.html', $content);

                return back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $listTitle = 'Update Lease To Own Agreement Template';
        $filePath = $this->templateBasePath() . $userid . '_lease_to_own.html';
        $defaultPath = $this->templateBasePath() . 'lease_to_own.html';
        $template = is_file($filePath) ? File::get($filePath) : (is_file($defaultPath) ? File::get($defaultPath) : '');

        return view('admin.agreement_templates.lease_to_own', compact('listTitle', 'template', 'userid', 'useridB64'));
    }
    private function templateBasePath(): string
    {
        return public_path('files/agreement_templates/');
    }
}
