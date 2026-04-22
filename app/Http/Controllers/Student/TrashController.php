<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Assignment;
use App\Models\Question;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.student.trash');
    }
}
