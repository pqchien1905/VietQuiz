<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            return response()->json([
                'classes' => $user->createdClasses()
                    ->withCount('students')
                    ->get(),
            ]);
        }

        return response()->json([
            'classes' => $user->classes()->with('teacher')->get(),
        ]);
    }

    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $code = strtoupper(trim($validated['code']));
        $class = ClassModel::where('code', $code)->first();

        if (!$class) {
            return response()->json(['error' => 'Không tìm thấy lớp với mã này.'], 404);
        }

        if ($request->user()->classes()->where('classes.id', $class->id)->exists()) {
            return response()->json(['message' => 'Bạn đã tham gia lớp này rồi.'], 200);
        }

        $request->user()->classes()->attach($class->id, ['joined_at' => now()]);

        return response()->json([
            'message' => 'Tham gia lớp thành công!',
            'class'  => $class->load('teacher'),
        ]);
    }
}
