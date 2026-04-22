<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\BulkCreateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return UserResource::collection($users);
    }
    public function store(CreateUserRequest $request): UserResource
    {
        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => 'password123',
            'role'      => $request->role,
            'is_active' => true,
        ]);

        return new UserResource($user);
    }
    public function bulk(BulkCreateUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $created = [];
        $errors  = [];
        $row     = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;

            // Skip header row
            if ($row === 1) continue;

            [$name, $email, $role] = array_pad($line, 3, null);

            // Validate each row
            if (!$name || !$email || !in_array($role, ['admin', 'kitchen_staff', 'user'])) {
                $errors[] = "Row {$row}: invalid data.";
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $errors[] = "Row {$row}: email {$email} already exists.";
                continue;
            }

            $user = User::create([
                'tenant_id' => $request->user()->tenant_id,
                'name'      => trim($name),
                'email'     => trim($email),
                'password'  => 'password123',
                'role'      => trim($role),
                'is_active' => true,
            ]);

            $created[] = $user->id;
        }

        fclose($handle);

        return response()->json([
            'message' => count($created) . ' users created.',
            'created' => count($created),
            'errors'  => $errors,
        ]);
    }
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorizeTenant($user, $request);

        $user->update($request->only(['name', 'email', 'role']));

        return new UserResource($user);
    }

    private function authorizeTenant(User $user, Request $request): void
    {
        if ($user->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized.');
        }
    }
    public function activate(Request $request, User $user): UserResource
    {
        $this->authorizeTenant($user, $request);
        $user->update(['is_active' => true]);
        return new UserResource($user);
    }

    public function deactivate(Request $request, User $user): UserResource
    {
        $this->authorizeTenant($user, $request);
        $user->update(['is_active' => false]);
        return new UserResource($user);
    }
    public function destroy(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $this->authorizeTenant($user, $request);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }
}