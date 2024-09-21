<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function create(Request $request)
    {
        try {
            $auth = $this->auth();
            if( ! $auth->hasRole('Admin') ) {
                return $this->accessDenied();
            }

            $validated = $this->validate($request->all(), [
                'name' => 'required|string|unique:cities,name',
            ]);
            if( $validated !== true ) return $validated;

            $city = City::create([
                'name' => $request->name,
            ]);

            $this->response->data['city'] = $city;
            $this->response->message[] = 'City has been successfully created.';
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

            $city = City::find($id);
            if( $city ) {
                $validated = $this->validate($request->all(), [
                    'name' => 'required|string|unique:cities,name,' . $city->id,
                ]);
                if( $validated !== true ) return $validated;
    
                $city->name = $request->name;
                $city->save();
    
                $this->response->data['city'] = $city;
                $this->response->message[] = 'City has been successfully updated.';
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

            $city = City::find($id);
            if( $city ) {
                $city->delete();
                $this->response->message[] = 'City has been successfully deleted.';
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

    public function getCity($id) {
        try {
            $city = City::find($id);
            if( $city ) {
                $this->response->data['city'] = $city;
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

    public function getCities(Request $request) {
        try {
            // Set default values for parameters
            $orderBy = $request->input('orderBy') ?: 'name';
            $orderDirection = $request->input('orderDirection') ?: 'asc';
            $perPage = $request->input('perPage') ?: 10;
            $search = $request->input('search', '');
            $currentPage = $request->input('page') ?: 1;
            $paginate = in_array($request->input('paginate', 0), ['true', 1]) ? true : false;

            // Query builder for Cities
            $query = City::query();

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
                $cities = [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'current_page' => $currentPage,
                    'last_page' => $paginator->lastPage(),
                ];
            } else {
                // Get all results without pagination
                $cities = $query->get();
            }

            $this->response->data['cities'] = $cities;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
