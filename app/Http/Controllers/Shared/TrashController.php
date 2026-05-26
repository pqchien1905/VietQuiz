<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrashController extends Controller
{
    private const RETENTION_DAYS = 30;

    private const TYPES = [
        'class' => [
            'model' => ClassModel::class,
            'name' => 'name',
            'label' => 'Lớp học',
        ],
        'course' => [
            'model' => Course::class,
            'name' => 'name',
            'label' => 'Khóa học',
        ],
        'quiz' => [
            'model' => Quiz::class,
            'name' => 'title',
            'label' => 'Đề thi',
        ],
        'assignment' => [
            'model' => Assignment::class,
            'name' => 'title',
            'label' => 'Bài tập',
        ],
        'question' => [
            'model' => Question::class,
            'name' => 'content',
            'label' => 'Câu hỏi',
        ],
    ];

    private const STUDENT_TYPES = [
        'notification' => [
            'model' => Notification::class,
            'name' => 'title',
            'label' => 'Thông báo',
        ],
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $viewPath = $user->isTeacher() ? 'pages.teacher.trash' : 'pages.student.trash';

        $type = $request->get('type', 'all');
        $availableTypes = $this->typesForUser($user);
        $type = array_key_exists($type, $availableTypes) ? $type : 'all';

        $allTrashed = $this->trashedItemsForUser($user);
        $counts = ['all' => $allTrashed->count()];
        foreach (array_keys($availableTypes) as $trashType) {
            $counts[$trashType] = $allTrashed->where('type', $trashType)->count();
        }

        $trashedItems = $type === 'all'
            ? $allTrashed
            : $allTrashed->where('type', $type)->values();

        $typeLabels = collect($availableTypes)
            ->mapWithKeys(fn($meta, $trashType) => [$trashType => $meta['label']])
            ->all();

        return view($viewPath, compact('trashedItems', 'allTrashed', 'counts', 'type', 'typeLabels'));
    }

    public function restore(Request $request, string $type, string $id)
    {
        $user = $request->user();

        $this->queryForType($type, $user)->where('id', $id)->restore();

        return back()->with('success', 'Đã khôi phục mục thành công.');
    }

    public function forceDelete(Request $request, string $type, string $id)
    {
        $user = $request->user();

        $this->queryForType($type, $user)->where('id', $id)->forceDelete();

        return back()->with('success', 'Đã xóa vĩnh viễn mục đã chọn.');
    }

    public function restoreAll(Request $request)
    {
        $user = $request->user();

        foreach (array_keys($this->typesForUser($user)) as $type) {
            $this->queryForType($type, $user)->restore();
        }

        return redirect()
            ->route($this->trashRouteName($user))
            ->with('success', 'Đã khôi phục tất cả mục trong thùng rác.');
    }

    public function forceDeleteAll(Request $request)
    {
        $user = $request->user();

        foreach (array_keys($this->typesForUser($user)) as $type) {
            $this->queryForType($type, $user)->forceDelete();
        }

        return redirect()
            ->route($this->trashRouteName($user))
            ->with('success', 'Đã xóa vĩnh viễn tất cả mục trong thùng rác.');
    }

    public function restoreSelected(Request $request)
    {
        $user = $request->user();

        $items = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string'],
        ])['items'];

        $count = $this->performSelectedAction($items, $user, 'restore');

        return back()->with('success', "Đã khôi phục {$count} mục đã chọn.");
    }

    public function forceDeleteSelected(Request $request)
    {
        $user = $request->user();

        $items = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string'],
        ])['items'];

        $count = $this->performSelectedAction($items, $user, 'forceDelete');

        return back()->with('success', "Đã xóa vĩnh viễn {$count} mục đã chọn.");
    }

    private function trashedItemsForUser($user): Collection
    {
        return collect($this->typesForUser($user))
            ->flatMap(fn($meta, $type) => $this->queryForType($type, $user)
                ->latest('deleted_at')
                ->get()
                ->map(fn($item) => $this->toTrashItem($item, $type, $meta)))
            ->sortByDesc('deleted_at')
            ->values();
    }

    private function toTrashItem($item, string $type, array $meta): object
    {
        $deletedAt = $item->deleted_at;
        $ageDays = $deletedAt ? (int) floor($deletedAt->diffInDays(now())) : 0;

        return (object) [
            'id' => $item->id,
            'key' => "{$type}:{$item->id}",
            'name' => Str::limit((string) $item->{$meta['name']}, 80),
            'type' => $type,
            'type_label' => $meta['label'],
            'deleted_at' => $deletedAt,
            'deleted_at_label' => $deletedAt?->format('d/m/Y H:i') ?? '',
            'age_days' => $ageDays,
            'days_left' => max(0, self::RETENTION_DAYS - $ageDays),
            'is_expiring' => max(0, self::RETENTION_DAYS - $ageDays) <= 7,
            'description' => $this->trashItemDescription($item, $type),
        ];
    }

    private function queryForType(string $type, $user)
    {
        $types = $this->typesForUser($user);
        abort_unless(array_key_exists($type, $types), 404);

        $model = $types[$type]['model'];

        $query = $model::onlyTrashed();

        if ($user->isTeacher()) {
            return $query->where('teacher_id', $user->id);
        }

        return match ($type) {
            'notification' => $query->where('user_id', $user->id)->forAudience('student'),
            default => abort(404),
        };
    }

    private function performSelectedAction(array $items, $user, string $action): int
    {
        $types = $this->typesForUser($user);
        $grouped = collect($items)
            ->map(function ($item) {
                [$type, $id] = array_pad(explode(':', $item, 2), 2, null);

                return [
                    'type' => $type,
                    'id' => is_string($id) && $id !== '' ? $id : null,
                ];
            })
            ->filter(fn($item) => array_key_exists((string) $item['type'], $types) && $item['id'])
            ->groupBy('type');

        $count = 0;
        foreach ($grouped as $type => $typeItems) {
            $ids = $typeItems->pluck('id')->all();
            $query = $this->queryForType((string) $type, $user)->whereIn('id', $ids);
            $count += (clone $query)->count();
            $query->{$action}();
        }

        return $count;
    }

    private function typesForUser($user): array
    {
        return $user->isTeacher() ? self::TYPES : self::STUDENT_TYPES;
    }

    private function trashRouteName($user): string
    {
        return $user->isTeacher() ? 'teacher.trash' : 'student.trash';
    }

    private function trashItemDescription($item, string $type): ?string
    {
        return match ($type) {
            'notification' => Str::limit((string) ($item->body ?? ''), 120),
            default => null,
        };
    }
}
