<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function showJoinForm()
    {
        return view('pages.student.join-class');
    }

    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $code = strtoupper(trim($validated['code']));
        $class = ClassModel::where('code', $code)->first();

        if (!$class) {
            return back()->withErrors(['code' => 'Không tìm thấy lớp với mã này.']);
        }

        $user = $request->user();

        if ($user->classes()->where('classes.id', $class->id)->exists()) {
            return back()->with('info', 'Bạn đã tham gia lớp này rồi!');
        }

        $user->classes()->attach($class->id, ['joined_at' => now()]);

        return redirect()
            ->route('student.courses')
            ->with('success', "Tham gia lớp '{$class->name}' thành công!");
    }
}
