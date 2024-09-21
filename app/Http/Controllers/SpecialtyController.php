<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    public function create(Request $request)
    {
        try {
            $auth = $this->auth();
            if( ! $auth->hasRole('Admin') ) {
                return $this->accessDenied();
            }

            $validated = $this->validate($request->all(), [
                'name' => 'required|string|unique:specialties,name',
            ]);
            if( $validated !== true ) return $validated;

            $specialty = Specialty::create([
                'name' => $request->name,
            ]);

            $this->response->data['specialty'] = $specialty;
            $this->response->message[] = 'Specialty has been successfully created.';
            return $this->response(201);
        } catch(\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if( !$this->auth()->hasRole('admin') ) {
                return $this->accessDenied();
            }

            $specialty = Specialty::find($id);
            if( $specialty ) {
                $validated = $this->validate($request->all(), [
                    'name' => 'required|string|unique:specialties,name,' . $specialty->id,
                ]);
                if( $validated !== true ) return $validated;
    
                $specialty->name = $request->name;
                $specialty->save();
    
                $this->response->data['specialty'] = $specialty;
                $this->response->message[] = 'Specialty has been successfully updated.';
                return $this->response(200);
            } else {
                $this->response->error[] = 'We encountered an error processing your request.';
                return $this->response(400);
            }
        } catch(\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function delete($id)
    {
        try {
            if( !$this->auth()->hasRole('admin') ) {
                return $this->accessDenied();
            }

            $specialty = Specialty::find($id);
            if( $specialty ) {
                $specialty->delete();
                $this->response->message[] = 'Specialty has been successfully deleted.';
                return $this->response(204);
            } else {
                $this->response->error[] = 'We encountered an error processing your request.';
                return $this->response(400);
            }
        } catch(\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function getSpecialty($id) {
        try {
            $specialty = Specialty::find($id);
            if( $specialty ) {
                $this->response->data['specialty'] = $specialty;
                return $this->response(200);
            } else {
                $this->response->message[] = 'No records found.';
                return $this->response(404);
            }
        } catch(\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function getSpecialties(Request $request) {
        try {
            // Set default values for parameters
            $orderBy = $request->input('orderBy') ?: 'name';
            $orderDirection = $request->input('orderDirection') ?: 'asc';
            $perPage = $request->input('perPage') ?: 10;
            $search = $request->input('search', '');
            $currentPage = $request->input('page') ?: 1;
            $paginate = in_array($request->input('paginate', 0), ['true', 1]) ? true : false;

            // Query builder for Specialties
            $query = Specialty::query();

            // Apply search filter
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            // Apply ordering
            $query->orderBy($orderBy, $orderDirection);

            // Check if pagination is requested
            if ($paginate) {
                // Get paginated results
                $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
                $specialties = [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'current_page' => $currentPage,
                    'last_page' => $paginator->lastPage(),
                ];
            } else {
                // Get all results without pagination
                $specialties = $query->get();
            }

            $this->response->data['specialties'] = $specialties;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
