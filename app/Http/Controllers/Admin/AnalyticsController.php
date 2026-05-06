<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends AdminBaseController
{
    public function analytics(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return view('pages.admin.analytics', $this->adminAnalyticsPayload($request));
    }

    public function exportAnalytics(Request $request): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $data = $this->adminAnalyticsPayload($request);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('VietQuiz')
            ->setTitle('VietQuiz Admin Analytics');

        foreach ($this->analyticsExportSheets($data) as $index => $sheetData) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $this->writeSheet($sheet, $sheetData['title'], $sheetData['rows']);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'vietquiz-admin-thong-ke-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

