<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeePasswordChanged
{
    /**
     * Block employee access until the initial password has been changed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ($user->role ?? null) !== 'employee') {
            return $next($request);
        }

        $employee = $this->resolveEmployee($user);

        if (! $employee || ! $employee->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'password.force',
            'password.force.update',
            'employee.password.status',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password pertama belum diganti. Silakan ubah password terlebih dahulu.',
            ], 423);
        }

        return redirect()->route('password.force');
    }

    private function resolveEmployee($user): ?Employee
    {
        if (! empty($user->email) && str_contains($user->email, '@local')) {
            $nipFromEmail = explode('@', $user->email, 2)[0];
            $employee = Employee::where('nip', $nipFromEmail)->first();

            if ($employee) {
                return $employee;
            }
        }

        if (! empty($user->name)) {
            return Employee::where('name', $user->name)->first();
        }

        return null;
    }
}
