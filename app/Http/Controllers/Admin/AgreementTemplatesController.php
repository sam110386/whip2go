<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AgreementTemplatesController extends LegacyAppController
{
    private function templateBasePath(): string
    {
        return public_path('files/agreement_templates/');
    }

    public function index(Request $request, $userid = null)
    {
        $listTitle = 'Agreement Templates';

        return view('admin.agreement_templates.index', compact('listTitle', 'userid'));
    }

    public function rental(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.agreement_templates.index')
                ->with('error', 'Invalid user ID');
        }

        if ($request->isMethod('post') && $request->filled('content')) {
            try {
                $content = $request->input('content');
                $content = '<!DOCTYPE html><html lang="en"><body>' . $content . '</body></html>';
                File::ensureDirectoryExists($this->templateBasePath());
                File::put($this->templateBasePath() . $userid . '_rental.html', $content);

                return back()->with('success', 'Template is saved successfully');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $listTitle = 'Update Rental Agreement Template';
        $filePath = $this->templateBasePath() . $userid . '_rental.html';
        $defaultPath = $this->templateBasePath() . 'rental.html';
        $template = is_file($filePath) ? File::get($filePath) : (is_file($defaultPath) ? File::get($defaultPath) : '');

        return view('admin.agreement_templates.rental', compact('listTitle', 'template', 'userid'));
    }

    public function rentToOwn(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.agreement_templates.index')
                ->with('error', 'Invalid user ID');
        }

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

        return view('admin.agreement_templates.rent_to_own', compact('listTitle', 'template', 'userid'));
    }

    public function lease(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.agreement_templates.index')
                ->with('error', 'Invalid user ID');
        }

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

        return view('admin.agreement_templates.lease', compact('listTitle', 'template', 'userid'));
    }

    public function leaseToOwn(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.agreement_templates.index')
                ->with('error', 'Invalid user ID');
        }

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

        return view('admin.agreement_templates.lease_to_own', compact('listTitle', 'template', 'userid'));
    }
}
