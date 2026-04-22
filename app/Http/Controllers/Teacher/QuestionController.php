<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $questions = $user->questions()
            ->with('quiz:id,title')
            ->latest()
            ->paginate(20);

        $quizzes = $user->quizzes()->select('id', 'title')->get();

        return view('pages.teacher.questions', compact('questions', 'quizzes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'quiz_id'     => 'required|exists:quizzes,id',
            'type'        => 'required|in:multiple_choice,true_false,short_answer',
            'content'     => 'required|string',
            'options'     => 'nullable|array',
            'correct_answer' => 'required|string',
            'points'      => 'nullable|integer|min:1',
            'explanation' => 'nullable|string',
        ]);

        $validated['teacher_id'] = $request->user()->id;
        $validated['options'] = isset($validated['options']) ? json_encode($validated['options']) : null;
        $validated['order'] = Question::where('quiz_id', $validated['quiz_id'])->count() + 1;

        Question::create($validated);

        return redirect()->route('teacher.questions')
            ->with('success', 'Thêm câu hỏi thành công!');
    }

    public function update(Request $request, Question $question)
    {
        abort_unless($question->teacher_id === $request->user()->id, 403);

        $validated = $request->validate([
            'content'     => 'required|string',
            'type'        => 'required|in:multiple_choice,true_false,short_answer',
            'options'     => 'nullable|array',
            'correct_answer' => 'required|string',
            'points'      => 'nullable|integer|min:1',
            'explanation' => 'nullable|string',
        ]);

        $validated['options'] = isset($validated['options']) ? json_encode($validated['options']) : null;
        $question->update($validated);

        return redirect()->route('teacher.questions')
            ->with('success', 'Cập nhật câu hỏi thành công!');
    }

    public function destroy(Request $request, Question $question)
    {
        abort_unless($question->teacher_id === $request->user()->id, 403);
        $question->delete();

        return redirect()->route('teacher.questions')
            ->with('success', 'Đã xóa câu hỏi!');
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // skip header
        $count = 0;
        $order = Question::where('quiz_id', $request->quiz_id)->count();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            $order++;
            Question::create([
                'quiz_id'     => $request->quiz_id,
                'teacher_id'  => $request->user()->id,
                'type'        => $row[0] ?? 'multiple_choice',
                'content'     => $row[1] ?? '',
                'options'     => isset($row[2]) ? json_encode(explode('|', $row[2])) : null,
                'correct_answer' => $row[3] ?? '',
                'points'      => (int)($row[4] ?? 1),
                'explanation' => $row[5] ?? null,
                'order'       => $order,
            ]);
            $count++;
        }
        fclose($handle);

        return redirect()->route('teacher.questions')
            ->with('success', "Đã import thành công $count câu hỏi!");
    }
}
