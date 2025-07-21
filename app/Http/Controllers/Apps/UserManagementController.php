<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\CreateUserRequest;
use App\Http\Requests\UserManagement\EditUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {

        return $dataTable->render('pages.apps.user-management.users.list');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::get();
        return view('pages.apps.user-management.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now()->toDateTimeString(),
        ]);

        $role = Role::findById($request->role_id);
        $user->assignRole($role->name);

        return response()->json(['message' => 'Added Successfully', 'status' => 200]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //  return view('pages/apps.user-management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::get();
        return view('pages.apps.user-management.users.create', compact('roles', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EditUserRequest $request, User $user)
    {

        if ($request->hasFile('logo_image')) {
            $name = Str::slug($request->name, '_');
            $extension = $request->file('logo_image')->getClientOriginalExtension();
            $fileNameToStore = $name . '_' . uniqid() . '.' . $extension;
            if ($user->profile_photo_path)
                Storage::delete($user->profile_photo_path);
            $avatar = Storage::putFileAs("user", $request->file('logo_image'), $fileNameToStore);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($avatar ?? null) {
            $user->update(['profile_photo_path' => $avatar]);
        }

        $role = Role::findById($request->role_id);
        $user->syncRoles($role->name);

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id != 1) {
            if ($user->profile_photo_path)
                Storage::delete($user->profile_photo_path);

            $user->delete();
        }

    }
}
