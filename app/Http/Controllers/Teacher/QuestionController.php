<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Services\AiQuestionGenerator;
use App\Services\DocumentTextExtractor;
use App\Services\QuestionFileImporter;
use App\Support\AiQuestionIntentGuard;
use App\Support\VipFeature;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'folder_q' => trim((string) $request->query('folder_q', '')),
            'type' => $request->query('type'),
            'quiz_id' => $request->query('quiz_id'),
            'folder_id' => $request->query('folder_id'),
        ];

        $folders = $user->questionFolders()
            ->withCount('questions')
            ->orderBy('name')
            ->get();

        $selectedFolder = $folders->firstWhere('id', (int) $filters['folder_id']);
        $isFolderOpen = $selectedFolder !== null;
        $folderCards = $filters['folder_q'] === ''
            ? $folders
            : $folders->filter(fn ($folder) => str_contains(
                mb_strtolower($folder->name),
                mb_strtolower($filters['folder_q'])
            ))->values();

        $questionsQuery = $user->questions()
            ->with(['quiz:id,title', 'folder:id,name'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $search = $filters['q'];
                $query->where(function ($inner) use ($search) {
                    $inner->where('content', 'like', "%{$search}%")
                        ->orWhere('correct_answer', 'like', "%{$search}%")
                        ->orWhere('explanation', 'like', "%{$search}%")
                        ->orWhereHas('quiz', fn ($quizQuery) => $quizQuery->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('folder', fn ($folderQuery) => $folderQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($filters['type'], ['multiple_choice', 'true_false', 'short_answer'], true), function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when($filters['quiz_id'] !== null && $filters['quiz_id'] !== '', function ($query) use ($filters) {
                $query->where('quiz_id', (int) $filters['quiz_id']);
            })
            ->when($filters['folder_id'] !== null && $filters['folder_id'] !== '', function ($query) use ($filters) {
                if ($filters['folder_id'] === 'none') {
                    $query->whereNull('folder_id');
                } else {
                    $query->where('folder_id', (int) $filters['folder_id']);
                }
            })
            ->latest();

        $questions = $questionsQuery->paginate(20)->withQueryString();
        $statsQuery = $user->questions();
        if ($isFolderOpen) {
            $statsQuery->where('folder_id', $selectedFolder->id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'multiple_choice' => (clone $statsQuery)->where('type', 'multiple_choice')->count(),
            'true_false' => (clone $statsQuery)->where('type', 'true_false')->count(),
            'short_answer' => (clone $statsQuery)->where('type', 'short_answer')->count(),
        ];

        $quizzes = $user->quizzes()->select('id', 'title')->orderBy('title')->get();

        return view('pages.teacher.questions', compact('questions', 'quizzes', 'folders', 'folderCards', 'filters', 'stats', 'selectedFolder', 'isFolderOpen'));
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $request->user()->questionFolders()->firstOrCreate([
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Đã tạo thư mục ngân hàng câu hỏi.');
    }

    public function updateFolder(Request $request, QuestionFolder $folder)
    {
        abort_unless($folder->teacher_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('question_folders', 'name')
                    ->where('teacher_id', $request->user()->id)
                    ->ignore($folder->id),
            ],
        ]);

        $folder->update([
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Đã cập nhật thư mục.');
    }

    public function destroyFolder(Request $request, QuestionFolder $folder)
    {
        abort_unless($folder->teacher_id === $request->user()->id, 403);

        $deletedQuestions = $folder->questions()->delete();
        $folder->delete();

        return redirect()->route('teacher.questions')
            ->with('success', "Đã xóa thư mục và {$deletedQuestions} câu hỏi trong thư mục.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => ['required_without:quiz_id', 'integer', Rule::exists('question_folders', 'id')->where('teacher_id', $request->user()->id)],
            'quiz_id' => ['nullable', 'integer', Rule::exists('quizzes', 'id')->where('teacher_id', $request->user()->id)],
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'content' => 'required|string',
            'options' => 'nullable|array',
            'correct_answer' => 'required|string',
            'explanation' => 'nullable|string',
        ]);

        if (empty($validated['quiz_id']) && !VipFeature::canAddBankQuestions($request->user())) {
            return back()->withInput()->with('error', VipFeature::bankQuestionLimitMessage());
        }

        $quiz = isset($validated['quiz_id']) ? $this->ownedQuiz($request, (int) $validated['quiz_id']) : null;
        $options = $this->cleanOptions($validated['options'] ?? []);
        $correctAnswer = trim($validated['correct_answer']);

        if ($validated['type'] === 'multiple_choice') {
            if (count($options) < 2) {
                return back()->withInput()
                    ->with('error', 'Câu trắc nghiệm cần ít nhất 2 đáp án.');
            }

            $selectedIndex = (int) $correctAnswer;
            if ($selectedIndex < 0 || $selectedIndex >= count($options)) {
                $selectedIndex = 0;
            }
            $correctAnswer = (string) $selectedIndex;
        } elseif ($validated['type'] === 'true_false') {
            $options = [];
            $correctAnswer = in_array(mb_strtolower($correctAnswer), ['false', '0', 'sai'], true) ? 'false' : 'true';
        } else {
            $options = [];
            $correctAnswer = $correctAnswer !== '' ? $correctAnswer : 'Giáo viên chấm theo ý chính.';
        }

        Question::create([
            'quiz_id' => $quiz?->id,
            'teacher_id' => $request->user()->id,
            'folder_id' => isset($validated['folder_id']) ? (int) $validated['folder_id'] : null,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'points' => 1,
            'explanation' => $validated['explanation'] ?? null,
            'order' => $quiz ? Question::where('quiz_id', $quiz->id)->count() + 1 : 0,
        ]);

        return redirect()->route('teacher.questions', array_filter(['folder_id' => $validated['folder_id'] ?? null]))
            ->with('success', 'Thêm câu hỏi vào ngân hàng thành công.');
    }

    public function update(Request $request, Question $question)
    {
        abort_unless($question->teacher_id === $request->user()->id, 403);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', Rule::exists('question_folders', 'id')->where('teacher_id', $request->user()->id)],
            'content' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'options' => 'nullable|array',
            'correct_answer' => 'required|string',
            'explanation' => 'nullable|string',
        ]);

        $validated['options'] = $this->cleanOptions($validated['options'] ?? []);
        $validated['points'] = 1;
        $question->update($validated);

        return back()->with('success', 'Cập nhật câu hỏi thành công.');
    }

    public function destroy(Request $request, Question $question)
    {
        abort_unless($question->teacher_id === $request->user()->id, 403);
        $question->delete();

        return back()
            ->with('success', 'Đã xóa câu hỏi.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ]);

        $deleted = $request->user()->questions()
            ->whereIn('id', $validated['question_ids'])
            ->delete();

        return back()->with('success', "Đã xóa {$deleted} câu hỏi.");
    }

    public function generateAi(Request $request, AiQuestionGenerator $generator, DocumentTextExtractor $extractor)
    {
        if (!VipFeature::isVip($request->user())) {
            return back()->withInput()->with('error', VipFeature::aiMessage());
        }

        $validated = $request->validate([
            'folder_id' => ['required', 'integer', Rule::exists('question_folders', 'id')->where('teacher_id', $request->user()->id)],
            'quiz_id' => ['nullable', 'integer', Rule::exists('quizzes', 'id')->where('teacher_id', $request->user()->id)],
            'topic' => 'nullable|required_without:source_file|string|min:3|max:500',
            'type' => 'required|in:mixed,multiple_choice,true_false,short_answer',
            'count' => 'required|integer|min:1|max:100',
            'difficulty' => 'required|in:easy,medium,hard',
            'grade' => 'nullable|string|max:100',
            'extra_context' => 'nullable|string|max:1000',
            'source_file' => 'nullable|file|max:15360|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ]);

        AiQuestionIntentGuard::ensureMeaningfulRequest(
            $validated,
            $request->hasFile('source_file')
        );

        $quiz = isset($validated['quiz_id']) ? $this->ownedQuiz($request, (int) $validated['quiz_id']) : null;

        try {
            if ($request->hasFile('source_file')) {
                $sourceFile = $request->file('source_file');
                $extension = strtolower($sourceFile->getClientOriginalExtension());
                $validated['topic'] = $validated['topic']
                    ?? pathinfo($sourceFile->getClientOriginalName(), PATHINFO_FILENAME);

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $contents = file_get_contents($sourceFile->getRealPath());
                    if ($contents === false || $contents === '') {
                        throw new \RuntimeException('Không đọc được file ảnh.');
                    }

                    $validated['image_data_url'] = sprintf(
                        'data:%s;base64,%s',
                        $sourceFile->getMimeType() ?: 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
                        base64_encode($contents)
                    );
                    $validated['extra_context'] = trim(($validated['extra_context'] ?? '') . "\n\nHãy đọc nội dung trong ảnh và tạo câu hỏi.");
                } else {
                    $documentText = mb_substr($extractor->extract($sourceFile), 0, 12000);
                    $validated['extra_context'] = trim(($validated['extra_context'] ?? '') . "\n\nNội dung tài liệu để tạo câu hỏi:\n" . $documentText);
                }
            }

            $questions = $generator->generate($validated);
            if (!$quiz && !VipFeature::canAddBankQuestions($request->user(), count($questions))) {
                return back()->withInput()->with('error', VipFeature::bankQuestionLimitMessage());
            }

            $count = $this->persistQuestions($request, $quiz, (int) $validated['folder_id'], $questions);

            return redirect()->route('teacher.questions', ['folder_id' => $validated['folder_id']])
                ->with('success', "Đã tạo {$count} câu hỏi bằng AI.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()
                ->with('error', $exception->getMessage() ?: 'Không thể tạo câu hỏi bằng AI.');
        }
    }

    public function importFile(Request $request, QuestionFileImporter $importer, AiQuestionGenerator $generator)
    {
        $validated = $request->validate([
            'folder_id' => ['required', 'integer', Rule::exists('question_folders', 'id')->where('teacher_id', $request->user()->id)],
            'quiz_id' => ['nullable', 'integer', Rule::exists('quizzes', 'id')->where('teacher_id', $request->user()->id)],
            'source_file' => 'required|file|max:15360|mimes:xlsx,xls,pdf,doc,docx,jpg,jpeg,png,webp',
            'topic' => 'nullable|string|max:500',
            'type' => 'required|in:mixed,multiple_choice,true_false,short_answer',
            'difficulty' => 'required|in:easy,medium,hard',
            'count' => 'nullable|integer|min:1|max:100',
        ]);

        $quiz = isset($validated['quiz_id']) ? $this->ownedQuiz($request, (int) $validated['quiz_id']) : null;

        try {
            $file = $request->file('source_file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                if (!VipFeature::isVip($request->user())) {
                    return back()->withInput()->with('error', VipFeature::aiMessage());
                }

                $contents = file_get_contents($file->getRealPath());
                if ($contents === false || $contents === '') {
                    throw new \RuntimeException('Không đọc được file ảnh.');
                }

                $questions = $generator->generate([
                    'topic' => $validated['topic'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'type' => $validated['type'],
                    'count' => (int) ($validated['count'] ?? 10),
                    'difficulty' => $validated['difficulty'],
                    'grade' => null,
                    'extra_context' => 'Hãy đọc nội dung trong ảnh và chuyển thành câu hỏi cho ngân hàng câu hỏi.',
                    'image_data_url' => sprintf(
                        'data:%s;base64,%s',
                        $file->getMimeType() ?: 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
                        base64_encode($contents)
                    ),
                ]);
            } else {
                $questions = $importer->import($file, (int) ($validated['count'] ?? 100));
            }

            if ($validated['type'] !== 'mixed') {
                $questions = array_values(array_filter(
                    $questions,
                    fn (array $question): bool => ($question['type'] ?? '') === $validated['type']
                ));
            }

            if (!$quiz && !VipFeature::canAddBankQuestions($request->user(), count($questions))) {
                return back()->withInput()->with('error', VipFeature::bankQuestionLimitMessage());
            }

            $count = $this->persistQuestions($request, $quiz, (int) $validated['folder_id'], $questions);

            return redirect()->route('teacher.questions', ['folder_id' => $validated['folder_id']])
                ->with('success', "Đã import thành công {$count} câu hỏi.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()
                ->with('error', $exception->getMessage() ?: 'Không import được câu hỏi từ file.');
        }
    }

    public function importCsv(Request $request)
    {
        return redirect()->route('teacher.questions')
            ->with('error', 'Import CSV đã được thay bằng import Excel, PDF, Word hoặc hình ảnh.');
    }

    private function ownedQuiz(Request $request, int $quizId): Quiz
    {
        return Quiz::whereKey($quizId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();
    }

    /**
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function cleanOptions(array $options): array
    {
        return array_values(array_filter(
            array_map(fn ($option): string => trim((string) $option), $options),
            fn (string $option): bool => $option !== ''
        ));
    }

    /**
     * @param array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}> $questions
     */
    private function persistQuestions(Request $request, ?Quiz $quiz, int $folderId, array $questions): int
    {
        $order = $quiz ? Question::where('quiz_id', $quiz->id)->count() : 0;
        $count = 0;

        foreach ($questions as $question) {
            if (trim((string) ($question['content'] ?? '')) === '') {
                continue;
            }

            $order++;
            Question::create([
                'quiz_id' => $quiz?->id,
                'teacher_id' => $request->user()->id,
                'folder_id' => $folderId,
                'type' => $question['type'] ?? 'multiple_choice',
                'content' => $question['content'],
                'options' => array_values($question['options'] ?? []),
                'correct_answer' => (string) ($question['correct_answer'] ?? ''),
                'points' => 1,
                'explanation' => $question['explanation'] ?? null,
                'order' => $quiz ? $order : 0,
            ]);
            $count++;
        }

        if ($count === 0) {
            throw new \RuntimeException('Không có câu hỏi hợp lệ để lưu.');
        }

        return $count;
    }
}
