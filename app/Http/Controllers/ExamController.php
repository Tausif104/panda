<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function create(Request $request)
    {
        try {
            $auth = $this->auth();
            if( ! $auth->hasRole('Admin') ) {
                return $this->accessDenied();
            }

            $validated = $this->validate($request->all(), [
                'title' => 'required|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'description' => 'nullable|string',
            ]);
            if( $validated !== true ) return $validated;

            $exam = Exam::create([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if( $request->hasFile('featured_image') ) {
                $image = $request->file('featured_image');
                $image->storeAs('public/exams', $image->hashName());
                $exam->featured_image = $image->hashName();
                $exam->save();
            }

            $this->response->data['exam'] = $exam;
            $this->response->message[] = 'Exam has been successfully created.';
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

            $exam = Exam::find($id);
            if( $exam ) {
                $validated = $this->validate($request->all(), [
                    'title' => 'required|string',
                    'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                    'description' => 'nullable|string',
                ]);
                if( $validated !== true ) return $validated;

                if( $request->hasFile('featured_image') ) {
                    // remove prvious image first
                    if( $exam->featured_image ) {
                        $image = public_path('storage/exams/' . $exam->featured_image);
                        if( file_exists($image) ) unlink($image);
                    }

                    $image = $request->file('featured_image');
                    $image->storeAs('public/exams', $image->hashName());
                    $exam->featured_image = $image->hashName();
                }

                if( $request->has('title') ) {
                    $exam->title = $request->title;
                }

                if( $request->has('description') ) {
                    $exam->description = $request->description;
                }

                $exam->save();

                $this->response->data['exam'] = $exam;
                $this->response->message[] = 'Exam has been successfully updated.';
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

            $exam = Exam::find($id);
            if( $exam ) {
                $exam->delete();
                $this->response->message[] = 'Exam has been successfully deleted.';
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

    public function getExam($id) {
        try {
            $exam = Exam::find($id);
            if( $exam ) {
                $this->response->data['exam'] = $exam;
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

    public function getExams(Request $request) {
        try {
            // Set default values for parameters
            $orderBy = $request->input('orderBy') ?: 'title';
            $orderDirection = $request->input('orderDirection') ?: 'asc';
            $perPage = $request->input('perPage') ?: 10;
            $search = $request->input('search', '');
            $currentPage = $request->input('page') ?: 1;
            $paginate = in_array($request->input('paginate', 0), ['true', 1]) ? true : false;

            // Query builder for Exams
            $query = Exam::query();

            // Apply search filter
            if ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }

            // Apply ordering
            $query->orderBy($orderBy, $orderDirection);

            // Check if pagination is requested
            if ($paginate) {
                // Get paginated results
                $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
                $exams = [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'current_page' => $currentPage,
                    'last_page' => $paginator->lastPage(),
                ];
            } else {
                // Get all results without pagination
                $exams = $query->get();
            }

            $this->response->data['exams'] = $exams;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}
