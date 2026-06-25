<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Legacy\LegacyAppController;
class WidgetLogsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Widget Logs';
        $sessLimitName = 'widget_logs_limit';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sessLimitName, $limit);
        } elseif ($request->session()->has($sessLimitName)) {
            $limit = $request->session()->get($sessLimitName);
        } else {
            $limit = $this->recordsPerPage;
        }

        $request->merge(['Record' => ['limit' => $limit]]);
        $folderPath = public_path('files/widgets');
        $files = [];

        if (File::exists($folderPath)) {
            $files = collect(File::files($folderPath))
                ->map(fn($file) => $file->getFilename())
                ->toArray();
            rsort($files);
        }

        $fileData = array_map(function ($f) {
            return [
                'filename' => $f,
                'date' => str_replace('_log.jsonl', '', $f)
            ];
        }, $files);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($fileData);
        $pagedData = $itemCollection->slice(($currentPage - 1) * $limit, $limit)->all();
        $paginatedFiles = new LengthAwarePaginator(
            $pagedData,
            count($itemCollection),
            $limit,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        if ($request->ajax()) {
            return view('admin.widget_logs.elements._index', [
                'files' => $paginatedFiles,
                'limit' => $limit
            ])->render();
        }

        return view('admin.widget_logs.index', [
            'title' => $title,
            'files' => $paginatedFiles,
            'limit' => $limit
        ]);
    }
    public function display(Request $request, $filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$filename) {
            return response()->json(['error' => 'Filename is required'], 400);
        }

        $filePath = public_path('files/widgets/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $handle = fopen($filePath, 'r');

        $records = [];
        $ipRecords = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $record = json_decode($line, true);

                if ($record) {
                    $records[] = $record;

                    if (isset($record['ip']) && !empty($record['ip'])) {
                        $ipRecords[$record['ip']][] = $record;
                    }
                }
            }

            fclose($handle);
        }

        krsort($records);

        if (!empty($ipRecords)) {
            return view('admin.widget_logs.ip_display', [
                'records' => $records,
                'filename' => $filename,
                'ipRecords' => $ipRecords
            ]);
        }

        return view('admin.widget_logs.display', compact('records'));
    }
    public function displaySub(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax() || !$request->isMethod('post')) {
            return response()->json(['error' => 'Invalid request method'], 400);
        }

        $filename = $request->input('filename');
        $ip = $request->input('ip');

        if (!$filename || !$ip) {
            return response()->json(['error' => 'Missing filename or IP address'], 422);
        }

        $filePath = public_path('files/widgets/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $handle = fopen($filePath, 'r');
        $ipRecords = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $record = json_decode($line, true);

                if ($record && isset($record['ip']) && !empty($record['ip']) && $ip === $record['ip']) {
                    $ipRecords[] = $record;
                }
            }

            fclose($handle);
        }

        krsort($ipRecords);

        return view('admin.widget_logs.ip_display_sub', [
            'ipRecords' => $ipRecords
        ]);
    }
    public function delete($filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$filename) {
            return response()->json([
                'status' => false,
                'message' => 'Filename is required'
            ], 400);
        }

        $filePath = public_path('files/widgets/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], 404);
        }

        if (@unlink($filePath)) {
            return response()->json([
                'status' => true,
                'message' => 'File deleted successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete file'
            ], 500);
        }
    }
}
