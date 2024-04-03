<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAdmin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $data = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->whereNotIn('users.id', [$request->user()->id])
            ->select('users.*', DB::raw('GROUP_CONCAT(roles.name) as role'))
            ->groupBy('users.id')
            ->orderBy('users.name', 'asc')
            ->get();
        return view('admins.index', ['data' => UserResource::collection($data)->resolve()]);
    }

    public function userRoles()
    {
        $usersWithRoles = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.*', DB::raw('GROUP_CONCAT(roles.name) as role'))
            ->groupBy('users.id')
            ->get();
        return $usersWithRoles;
    }
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [Rule::unique('users')->ignore($request->Id, 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first('email')]);
        }

        return response()->json(['status' => 'success']);
    }

    public function store(ValidationAdmin $request)
    {
        $profileImage = $request->file('profile_image');

        $profileImageName = null;

        if (!empty($profileImage)) {
            $profileImageName = $profileImage->getClientOriginalName();
            $profileImage->store('public/uploads');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt('12345678'),
            'age' => $request->age,
            'gender' => $request->gender,
            'profile_image' => $profileImageName,
        ]);

        $user->roles()->attach(
            Role::where('name', 'admin')->first()->id
        );

        return redirect(route('admins'));
    }

    public function show(User $user)
    {
        $user = User::leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('users.id', $user->id)
            ->select('users.*', DB::raw('GROUP_CONCAT(roles.name) as role'))
            ->groupBy('users.id')
            ->first();

        return view('admins.show', ['user' => new UserResource($user)]);
    }


    public function edit(User $user)
    {
        $user = User::leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('users.id', $user->id)
            ->select('users.*', DB::raw('GROUP_CONCAT(roles.name) as role'))
            ->groupBy('users.id')
            ->first();

        return view('admins.edit', ['user' => new UserResource($user)]);
    }

    public function update(ValidationAdmin $request, User $user)
    {
        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image');
            $profileImageName = $profileImage->getClientOriginalName();
            $profileImage->store('public/uploads');
            $user->profile_image = $profileImageName;
        } else {
            $profileImageName = $user->profile_image;
        }
        $data = $request->all();
        $data['profile_image'] = $profileImageName;
        $user->update($data);

        return redirect()->route('admins')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();
        return redirect()->route('admins')->with('success', 'Admin deleted successfully');
    }
}
