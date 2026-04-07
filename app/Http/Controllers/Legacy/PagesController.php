<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\Page;
use Illuminate\Http\Request;

class PagesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    // ─── view (public static page by pagecode) ────────────────────────────────
    public function view(Request $request, $pagecode = null)
    {
        if (empty($pagecode)) {
            return redirect('/');
        }

        $page = Page::where('pagecode', $pagecode)->first();

        $listAllLinks = [];
        if (!empty($page->pagegroup)) {
            $listAllLinks = Page::where('pagegroup', $page->pagegroup)
                ->orderBy('sequence')
                ->pluck('title', 'pagecode')
                ->toArray();
        }

        return view('legacy.pages.view', [
            'page'              => $page,
            'list_all_links'    => $listAllLinks,
            'title_for_layout'  => $page->meta_title       ?? '',
            'meta_description'  => $page->meta_description ?? '',
            'meta_keywords'     => $page->meta_keyword     ?? '',
        ]);
    }

    // ─── index (homepage/pagecode-based) ─────────────────────────────────────
    public function index(Request $request, $pageCode = 'home')
    {
        $contents = Page::where('page_code', $pageCode)->first();

        return view('legacy.pages.index', [
            'metakeywords'   => $contents->meta_keyword    ?? '',
            'metadescription'=> $contents->meta_description ?? '',
            'metatitle'      => $contents->meta_title      ?? '',
            'title'          => $contents->title            ?? '',
            'discription'    => $contents->description      ?? '',
        ]);
    }

    // ─── mobilefaq ────────────────────────────────────────────────────────────
    public function mobilefaq()
    {
        return view('legacy.pages.mobilefaq');
    }

    // ─── telematics ───────────────────────────────────────────────────────────
    public function telematics()
    {
        return view('legacy.pages.telematics', [
            'metakeywords'    => 'DriveItAway GPS Telematics',
            'metadescription' => 'DriveItAway GPS Telematics',
            'metatitle'       => 'DriveItAway GPS Telematics',
            'title'           => 'Telematics',
        ]);
    }

    public function display(Request $request, ...$path)
    {
        $pageCode = !empty($path[0]) ? (string) $path[0] : 'home';
        return $this->index($request, $pageCode);
    }

    protected function _validateId($id): bool
    {
        return !empty($id);
    }
}
