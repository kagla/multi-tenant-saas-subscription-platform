<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withoutGlobalScopes()->with('tenant');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(string $userId): View
    {
        $user = User::withoutGlobalScopes()->with('tenant')->findOrFail($userId);

        $logs = AuditLog::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('admin.users.show', compact('user', 'logs'));
    }
}
