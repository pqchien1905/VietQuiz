<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\User;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $students = User::where('role', 'student')
            ->whereHas('classes', fn($q) => $q->where('classes.teacher_id', $user->id))
            ->with(['classes' => fn($q) => $q->where('classes.teacher_id', $user->id)])
            ->withAvg('grades as avg_score', 'score')
            ->withCount(['submissions as submissions_count'])
            ->latest()
            ->get();

        $classes = $user->createdClasses()->get();

        return view('pages.teacher.students', compact('students', 'classes'));
    }

    public function inviteByEmail(Request $request)
    {
        $validated = $request->validate([
            'emails'   => 'required|array|min:1',
            'emails.*' => 'email',
            'class_id' => 'required|exists:classes,id',
        ]);

        $class = ClassModel::find($validated['class_id']);

        abort_unless($class->teacher_id === $request->user()->id, 403);

        $invited = 0;
        foreach ($validated['emails'] as $email) {
            $email = trim(strtolower($email));

            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                if ($existingUser->role !== 'student') {
                    continue;
                }
                if (!$class->students()->where('user_id', $existingUser->id)->exists()) {
                    $class->students()->attach($existingUser->id, ['joined_at' => now()]);
                    $invited++;
                }
            } else {
                $tempPassword = bin2hex(random_bytes(8));
                $newStudent = User::create([
                    'name'     => explode('@', $email)[0],
                    'email'    => $email,
                    'password' => Hash::make($tempPassword),
                    'role'     => 'student',
                ]);
                $class->students()->attach($newStudent->id, ['joined_at' => now()]);
                $invited++;
            }
        }

        return back()->with('success', "Đã mời {$invited} học sinh vào lớp {$class->name}!");
    }

    public function inviteByLink(Request $request, ClassModel $class)
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);

        $link = url("/join/{$class->code}");

        return response()->json([
            'link'   => $link,
            'expires' => now()->addDays(7)->toIso8601String(),
        ]);
    }
}
