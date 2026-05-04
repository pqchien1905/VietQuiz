<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        if (! in_array($user->role, $roles)) {
            $targetRole = $roles[0] ?? null;

            if ($user->canSwitchRole() && $targetRole === 'teacher' && $user->isStudent()) {
                return redirect()->to(\Illuminate\Support\Facades\URL::signedRoute('switch.to.teacher', [
                    'intended' => $request->fullUrl(),
                ]));
            }

            if ($user->canSwitchRole() && $targetRole === 'student' && $user->isTeacher()) {
                return redirect()->to(\Illuminate\Support\Facades\URL::signedRoute('switch.to.student', [
                    'intended' => $request->fullUrl(),
                ]));
            }

            if ($user->isTeacher()) {
                $quiz = $request->route('quiz');
                if ($quiz && ($request->is('student/quiz-take/*') || $request->is('student/quiz-result/*'))) {
                    return redirect()->to(\Illuminate\Support\Facades\URL::signedRoute('switch.to.student', [
                        'intended' => $request->fullUrl(),
                    ]));
                }

                return redirect()->route('teacher.dashboard');
            }

            if ($user->isStudent()) {
                return redirect()->route('student.dashboard');
            }

            abort(403);
        }

        return $next($request);
    }
}
