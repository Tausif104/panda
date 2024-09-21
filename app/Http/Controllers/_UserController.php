<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BankDetails;
use App\Models\Education;
use App\Models\Skill;
use App\Models\Social;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class UserController extends Controller
{
    

    public function get_profile()
    {
        try {
            $auth = $this->auth();

            $all_permissions = $auth->allPermissions();

            $auth = $auth->toArray();
            $auth['all_permissions'] = $all_permissions;

            $this->response->data['auth'] = $auth;

            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_user($user_id)
    {
        try {
            $user = User::find($user_id);

            if (!$user) {
                $this->response->error[] = 'User not found';
                return $this->response(404);
            }

            $user->contacts;
            $user->skills;
            $user->educations;
            if( $user->hasPermissionTo('manage_user') ) {
                $user->salary;
                $user->salaryPlans;
            }
            $user->position;
            $user->positionHistory;
            $user->socials;
            $user->bank_details;
            $user->roles;

            $all_permissions = $user->allPermissions();
            $user = $user->toArray();
            $user['all_permissions'] = $all_permissions;

            $this->response->data['user'] = $user;
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
                'date_of_birth' => 'nullable|date_format:Y-m-d',
                'organization' => 'nullable|string|max:150',
                'gender' => 'nullable|string|in:Male,Female,Other',
                'blood_group' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            ]);
            if ($validated !== true) return $validated;

            $user = Auth::user();
            $user->name = $request->name;
            $user->date_of_birth = $request->date_of_birth;
            $user->gender = $request->gender;
            $user->organization = $request->organization;
            $user->blood_group = $request->blood_group;
            $user->save();

            $this->response->data['auth'] = $user;
            $this->response->message[] = 'Your profile has been successfully updated.';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
    public function update_profile_social(Request $request)
    {
        try {
            $validated = $this->validate($request->all(), [
                'facebook' => 'nullable|string|url',
                'linkedin' => 'nullable|url',
            ]);
            if ($validated !== true) return $validated;

            $user = Auth::user();

            if ($user->socials) {
                // If social links exist, update them
                $social = $user->socials;
                $social->facebook = $request->input('facebook');
                $social->linkedin = $request->input('linkedin');
                $social->save();
            } else {
                // If social links don't exist, create new ones
                $social = new Social([
                    'facebook' => $request->input('facebook'),
                    'linkedin' => $request->input('linkedin'),
                ]);
                $user->socials()->save($social);
            }

            $this->response->data['social'] = $social;
            $this->response->message[] = 'Your social links has been successfully updated.';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_profile_social()
    {
        try {
            $user = Auth::user();
            $this->response->data['social'] = $user->socials;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function update_profile_bank_details(Request $request)
    {
        try {
            $validated = $this->validate($request->all(), [
                'bank_name' => 'required|string|max:200',
                'account_name' => 'required|string|max:200',
                'account_number' => 'required|string|max:200',
                'branch_name' => 'nullable|string|max:200',
                'routing_number' => 'nullable|string|max:200',
            ]);
            if ($validated !== true) return $validated;

            $user = Auth::user();

            if ($user->bank_details) {
                $bank_details = $user->bank_details;
                $bank_details->bank_name = $request->input('bank_name');
                $bank_details->account_name = $request->input('account_name');
                $bank_details->account_number = $request->input('account_number');
                $bank_details->branch_name = $request->input('branch_name');
                $bank_details->routing_number = $request->input('routing_number');
                $bank_details->save();
            } else {
                $bank_details = new BankDetails([
                    'bank_name' => $request->input('bank_name'),
                    'account_name' => $request->input('account_name'),
                    'account_number' => $request->input('account_number'),
                    'branch_name' => $request->input('branch_name'),
                    'routing_number' => $request->input('routing_number'),
                ]);
                if (!$user->bank_details()->save($bank_details)) {
                    $this->response->error[] = 'Failed to update bank details.';
                    return $this->response(500);
                }
            }

            $this->response->data['bank_details'] = $bank_details;
            $this->response->message[] = 'Your bank details has been successfully updated.';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_profile_bank_details()
    {
        try {
            $user = Auth::user();
            $this->response->data['bank_details'] = $user->bank_details;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function update_profile_skills(Request $request)
    {
        try {
            $validated = $this->validate($request->all(), [
                'skill_id' => 'exists:skills,id',
                'level' => 'required|string|max:50',
            ]);
            if ($validated !== true) return $validated;

            $user = Auth::user();

            $skillId = $request->input('skill_id');
            $level = $request->input('level');

            // Check if the skill exists for the user
            if ($user->skills()->where('skills.id', $skillId)->exists()) {
                // If the skill exists, update the level
                $user->skills()->updateExistingPivot($skillId, ['level' => $level]);
                $this->response->message[] = 'Your skill has been successfully updated.';
            } else {
                // If the skill doesn't exist, create it
                $user->skills()->attach($skillId, ['level' => $level]);
                $this->response->message[] = 'Your skill has been successfully created.';
            }
            $this->response->data['skills'] = $user->skills()->withPivot('level')->get();
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function delete_profile_skills(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $user->skills()->detach($id);
            $this->response->data['skills'] = $user->skills()->withPivot('level')->get();
            $this->response->message[] = 'Skill has been successfully deleted.';
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_profile_skills()
    {
        try {
            $user = Auth::user();

            $this->response->data['skills'] = $user->skills()->withPivot('level')->get();
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_user_skills(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if ($user) {
                $this->response->data['skills'] = $user->skills()->withPivot('level')->get();
                return $this->response(200);
            } else {
                $this->response->error[] = 'User not found';
                return $this->response(404);
            }
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function get_users(Request $request)
    {
        try {
            // Set default values for parameters
            $orderBy = $request->input('orderBy') ?: 'name';
            $orderDirection = $request->input('orderDirection') ?: 'asc';
            $perPage = $request->input('perPage') ?: 10;
            $search = $request->input('search', '');
            $currentPage = $request->input('page') ?: 1;
            $paginate = in_array($request->input('paginate', 0), ['true', 1]) ? true : false;

            // Query builder for Skills
            $query = User::query();

            // Apply search filter
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
                $query->orWhere('email', 'like', '%' . $search . '%');
            }

            // Apply ordering
            $query->orderBy($orderBy, $orderDirection);

            // Check if pagination is requested
            if ($paginate) {
                // Get paginated results
                $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
                $users = [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'current_page' => $currentPage,
                    'last_page' => $paginator->lastPage(),
                ];
            } else {
                // Get all results without pagination
                $users = $query->get();
            }

            $this->response->data['users'] = $users;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
