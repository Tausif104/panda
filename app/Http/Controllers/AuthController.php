<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function registration(Request $request)
    {
        try {

            $validated = $this->validate($request->all(), [
                'name' => 'required|string|max:200',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_number' => 'required|string|max:20',
                'password' => 'required|string|confirmed|min:6',
                'gender' => 'nullable|in:Male,Female,Other',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'city_id' => 'required|exists:cities,id',
                'specialty_id' => 'required|exists:specialties,id',
                // 'roles' => 'required|array',
                // 'roles.*' => 'required|exists:roles,name',
                'active' => 'nullable|boolean',
            ]);
            if ($validated !== true) return $validated;

            $userData = $request->only('name', 'email', 'gender', 'phone_number', 'city_id', 'specialty_id', 'active');
            $userData['password'] = bcrypt($request->password);
            $userData['active'] = empty($request->active) ? true : $request->active;

            if ($request->hasFile('avatar')) {
                // upload image here
                $image = $request->file('avatar');
                $path = $image->store('public/avatars');
                $userData['avatar_dir'] = $path;
            }

            $user   = User::create($userData);
            $user->assignRole('Subscriber');
            $token  = $user->createToken('authToken')->plainTextToken;

            $rpe = $request->has('receive_promotional_emails') ? $request->receive_promotional_emails : 0;
            $user->meta('receive_promotional_emails', $rpe);

            $this->response->data = ['token' => $token, 'user' => $user];
            $this->response->message[] = 'Your account has been successfully created.';
            return $this->response(201);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    // public function update_user(Request $request, $id) {
    //     try {
    //         if( !Auth::user()->can('edit_user') ) {
    //             return $this->accessDenied();
    //         }
    //         $user = User::find($id);
    //         if (!$user) {
    //             $this->response->error[] = 'User not found';
    //             return $this->response(404);
    //         }
    //         $validated = $this->validate($request->all(), [
    //             'name' => 'required|string|max:200',
    //             'email' => 'required|string|email|max:255|unique:users',
    //             'date_of_birth' => 'nullable|date_format:Y-m-d',
    //             'gender' => 'nullable|string|in:Male,Female,Other',
    //             'organization' => 'nullable|string|max:150',
    //             'blood_group' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
    //             'lead_manager_id' => 'nullable|exists:users,id',
    //             'direct_manager_id' => 'nullable|exists:users,id',
    //             'is_active' => 'nullable|boolean',
    //         ]);
    //         if ($validated !== true) return $validated;

    //         if( $request->has('name') ) $user->name = $request->name;
    //         if( $request->has('email') ) $user->email = $request->email;
    //         if( $request->has('date_of_birth') ) $user->date_of_birth = $request->date_of_birth;
    //         if( $request->has('gender') ) $user->gender = $request->gender;
    //         if( $request->has('organization') ) $user->organization = $request->organization;
    //         if( $request->has('blood_group') ) $user->blood_group = $request->blood_group;
    //         if( $request->has('lead_manager_id') ) $user->lead_manager_id = $request->lead_manager_id;
    //         if( $request->has('direct_manager_id') ) $user->direct_manager_id = $request->direct_manager_id;
    //         if( $request->has('is_active') and !empty($request->is_active) ) $user->is_active = $request->is_active;
    //         $user->save();

    //         $this->response->data['user'] = $user;
    //         $this->response->message[] = 'Your profile has been successfully updated.';
    //         return $this->response(200);
    //     } catch (\Exception $e) {
    //         $this->response->error[] = $e->getMessage();
    //         return $this->response(500);
    //     }
    // }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = $request->user();
            $token = $user->createToken('authToken')->plainTextToken;

            $this->response->data = ['token' => $token, 'user' => $user];
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => __($status)])
            : response()->json(['email' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)])
            : response()->json(['email' => __($status)], 400);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            $this->response->message[] = 'Logged out';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }


    public function get_profile()
    {
        try {
            $auth = $this->auth();

            $auth = $auth->toArray();
            $this->response->data['auth'] = $auth;

            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function update_profile(Request $request)
    {
        try {
            $validated = $this->validate($request->all(), [
                'name' => 'required|string|max:200',
                'gender' => 'nullable|string|in:Male,Female,Other',
                'phone_number' => 'required|string|max:20',
                'city_id' => 'reqired|exists:cities,id',
                'specialty_id' => 'reqired|exists:specialties,id',
            ]);
            if ($validated !== true) return $validated;

            $user = Auth::user();
            $user->name = $request->name;
            $user->gender = $request->gender;
            $user->phone_number = $request->phone_number;
            $user->city_id = $request->city_id;
            $user->specialty_id = $request->specialty_id;
            $user->save();

            $this->response->data['auth'] = $user;
            $this->response->message[] = 'Your profile has been successfully updated.';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
