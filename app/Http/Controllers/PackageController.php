<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;

class PackageController extends Controller
{
    public function create(Request $request)
    {
        try {
            $auth = $this->auth();
            if( ! $auth->hasRole('Admin') ) {
                return $this->accessDenied();
            }

            $validated = $this->validate($request->all(), [
                'name' => 'required|string',
                'sub_title' => 'required|string',
                'description' => 'nullable|string',
                'duration' => 'required|integer', // in months, 0 = unlimited
                'price' => 'required|numeric',
            ]);
            if( $validated !== true ) return $validated;

            $package = Package::create([
                'name' => $request->name,
                'sub_title' => $request->sub_title,
                'description' => $request->description,
                'duration' => $request->duration,
                'price' => $request->price,
            ]);

            $this->response->data['package'] = $package;
            $this->response->message[] = 'Package has been successfully created.';
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

            $package = Package::find($id);
            if( $package ) {
                $validated = $this->validate($request->all(), [
                    'name' => 'required|string|unique:packages,name,' . $package->id,
                    'sub_title' => 'nullable|string',
                    'description' => 'nullable|string',
                    'duration' => 'required|integer', // in months, 0 = unlimited
                    'price' => 'required|numeric',
                ]);
                if( $validated !== true ) return $validated;

                $package->name = $request->name;
                $package->sub_title = $request->sub_title;
                $package->description = $request->description;
                $package->duration = $request->duration;
                $package->price = $request->price;
                $package->save();

                $this->response->data['package'] = $package;
                $this->response->message[] = 'Package has been successfully updated.';
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

            $package = Package::find($id);
            if( $package ) {
                $package->delete();
                $this->response->message[] = 'Package has been successfully deleted.';
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

    public function getPackage($id) {
        try {
            $package = Package::find($id);
            if( $package ) {
                $this->response->data['package'] = $package;
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

    public function getPackages(Request $request) {
        try {
            // Set default values for parameters
            $orderBy = $request->input('orderBy') ?: 'name';
            $orderDirection = $request->input('orderDirection') ?: 'asc';
            $perPage = $request->input('perPage') ?: 10;
            $search = $request->input('search', '');
            $currentPage = $request->input('page') ?: 1;
            $paginate = in_array($request->input('paginate', 0), ['true', 1]) ? true : false;

            // Query builder for Packages
            $query = Package::query();

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
                $packages = [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'current_page' => $currentPage,
                    'last_page' => $paginator->lastPage(),
                ];
            } else {
                // Get all results without pagination
                $packages = $query->get();
            }

            $this->response->data['packages'] = $packages;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
