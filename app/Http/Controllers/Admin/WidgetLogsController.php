<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;

class WidgetLogsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'widget_logs_limit';
    private const DEFAULT_LIMIT = 25;

    /**
     * Cake `admin_index`: paginated list of JSONL log files from public/files/widgets/.
     * AJAX requests receive the `_index` partial only.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $folderPath = public_path('files/widgets');
        $files = [];

        if (is_dir($folderPath)) {
            $entries = scandir($folderPath);
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (is_file($folderPath . DIRECTORY_SEPARATOR . $entry)) {
                    $files[] = $entry;
                }
            }
        }

        rsort($files);

        $fileData = array_map(function ($f) {
            return [
                'filename' => $f,
                'date' => str_replace('_log.jsonl', '', $f),
            ];
        }, $files);

        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;
        $total = count($fileData);
        $pagedData = array_slice($fileData, $offset, $limit);
        $pageCount = $total > 0 ? (int) ceil($total / $limit) : 1;

        $paging = [
            'page' => $page,
            'current' => count($pagedData),
            'count' => $total,
            'prevPage' => $page > 1,
            'nextPage' => ($offset + $limit) < $total,
            'pageCount' => $pageCount,
            'limit' => $limit,
        ];

        $viewData = [
            'title_for_layout' => 'Widget Logs',
            'files' => $pagedData,
            'paging' => $paging,
            'limit' => $limit,
        ];

        if ($request->ajax()) {
            return view('admin.widget_logs._index', $viewData);
        }

        return view('admin.widget_logs.index', $viewData);
    }

    /**
     * Cake `admin_display`: reads a JSONL log file, groups records by IP when available.
     */
    public function display(Request $request, $filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$filename) {
            return response('Filename is required', 400);
        }

        $filePath = public_path('files/widgets/' . $filename);

        if (!file_exists($filePath)) {
            return response('File not found', 404);
        }

        $handle = fopen($filePath, 'r');
        $records = [];
        $ipRecords = [];

        if ($handle) {
            $k = 0;
            while (($line = fgets($handle)) !== false) {
                $record = json_decode($line, true);
                if ($record === null) {
                    continue;
                }
                $records[$k++] = $record;
                if (!empty($record['ip'])) {
                    $ipRecords[$record['ip']][] = $record;
                }
            }
            fclose($handle);
        }

        krsort($records);

        if (!empty($ipRecords)) {
            return view('admin.widget_logs.ip_display', [
                'filename' => $filename,
                'ipRecords' => $ipRecords,
            ]);
        }

        return view('admin.widget_logs.display', [
            'records' => $records,
        ]);
    }

    /**
     * Cake `admin_display_sub`: AJAX POST — filters JSONL records by a given IP.
     */
    public function displaySub(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax() || !$request->isMethod('post')) {
            return response('Invalid request', 400);
        }

        $filename = $request->input('filename');
        $ip = $request->input('ip');

        if (!$filename || !$ip) {
            return response('Filename and IP are required', 400);
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
                if ($record !== null && !empty($record['ip']) && $record['ip'] === $ip) {
                    $ipRecords[] = $record;
                }
            }
            fclose($handle);
        }

        krsort($ipRecords);

        return view('admin.widget_logs.ip_display_sub', [
            'ipRecords' => $ipRecords,
        ]);
    }

    /**
     * Cake `admin_delete`: removes a JSONL log file and returns JSON status.
     */
    public function delete($filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$filename) {
            return response()->json(['status' => false, 'message' => 'Filename is required']);
        }

        $filePath = public_path('files/widgets/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json(['status' => false, 'message' => 'File not found']);
        }

        if (@unlink($filePath)) {
            return response()->json(['status' => true, 'message' => 'File deleted successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete file']);
    }

    protected function resolveLimit(Request $request): int
    {
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if ($lim > 0) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);
                return $lim;
            }
        }

        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if ($sess > 0) {
            return $sess;
        }

        return self::DEFAULT_LIMIT;
    }
}
