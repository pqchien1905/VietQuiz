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

class TrashController extends AdminBaseController
{
    public function trash(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = array_key_exists($request->query('type', 'all'), $map) ? $request->query('type', 'all') : 'all';
        $queryText = trim((string) $request->query('q', ''));
        $age = $request->query('age', 'all');
        $sort = $request->query('sort', 'latest');

        $allItems = $this->adminTrashItems($map);
        $summary = [
            'total' => $allItems->count(),
            'today' => $allItems->where('age_days', 0)->count(),
            'week' => $allItems->where('age_days', '<=', 7)->count(),
            'expiring' => $allItems->where('is_expiring', true)->count(),
            'old' => $allItems->where('age_days', '>', 30)->count(),
        ];
        $typeCounts = collect($map)->map(fn ($config, $key) => $allItems->where('type', $key)->count())->all();

        $items = $allItems
            ->when($type !== 'all', fn ($items) => $items->where('type', $type))
            ->when($queryText !== '', function ($items) use ($queryText) {
                $needle = Str::lower($queryText);

                return $items->filter(fn ($item) => Str::contains(Str::lower($item->search_text), $needle));
            })
            ->when($age === 'today', fn ($items) => $items->where('age_days', 0))
            ->when($age === 'week', fn ($items) => $items->filter(fn ($item) => $item->age_days <= 7))
            ->when($age === 'month', fn ($items) => $items->filter(fn ($item) => $item->age_days <= 30))
            ->when($age === 'old', fn ($items) => $items->filter(fn ($item) => $item->age_days > 30));

        $items = match ($sort) {
            'oldest' => $items->sortBy('deleted_at'),
            'type' => $items->sortBy([['type_label', 'asc'], ['deleted_at', 'desc']]),
            'name' => $items->sortBy('title'),
            default => $items->sortByDesc('deleted_at'),
        };

        $items = $items->values();

        return view('pages.admin.trash', compact('items', 'summary', 'typeCounts', 'type', 'map', 'queryText', 'age', 'sort'));
    }

    public function restoreTrashItem(Request $request, string $type, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        abort_unless(isset($map[$type]), 404);

        $map[$type]['model']::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục dữ liệu.');
    }

    public function forceDeleteTrashItem(Request $request, string $type, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        abort_unless(isset($map[$type]), 404);

        $map[$type]['model']::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Đã xóa vĩnh viễn dữ liệu.');
    }

    public function restoreAllTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = (string) $request->input('type', 'all');
        $targets = array_key_exists($type, $map) ? [$type => $map[$type]] : $map;

        foreach ($targets as $config) {
            $config['model']::onlyTrashed()->restore();
        }

        return redirect()->route('admin.trash')->with('success', 'Đã khôi phục tất cả dữ liệu trong thùng rác.');
    }

    public function forceDeleteAllTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = (string) $request->input('type', 'all');
        $targets = array_key_exists($type, $map) ? [$type => $map[$type]] : $map;

        foreach ($targets as $config) {
            $config['model']::onlyTrashed()->forceDelete();
        }

        return redirect()->route('admin.trash')->with('success', 'Đã xóa vĩnh viễn tất cả dữ liệu trong thùng rác.');
    }

    public function restoreSelectedTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $count = $this->performSelectedTrashAction($request, 'restore');

        return back()->with('success', "Đã khôi phục {$count} mục đã chọn.");
    }

    public function forceDeleteSelectedTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $count = $this->performSelectedTrashAction($request, 'forceDelete');

        return back()->with('success', "Đã xóa vĩnh viễn {$count} mục đã chọn.");
    }
}

