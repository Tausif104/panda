<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use stdClass;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $response;
    protected $codeMessage;
    protected $validationMessages;

    function __construct() {
        $this->codeMessage = [
            '200'=>'OK',
            '201'=>'Created',
            '204'=>'No Content',
            '400'=>'Bad Request',
            '401'=>'Unauthorized',
            '403'=>'Forbidden',
            '404'=>'Not Found',
            '405'=>'Method Not Allowed',
            '409'=>'Duplicate Record',
            '422'=>'Unprocessable Entity',
            '500'=>'Data processing failed',
        ];

        $this->response = new stdClass(); 
        $this->response->data = [];
        // $this->response->status = 'OK';
        $this->response->error = [];
        $this->response->message = [];
        // $this->response->http_status = '';

        $this->validationMessages = [
            // 'required' => 'The :attribute field is required.'
            'required'  => 'The field is required.',
            'email'     => 'The email is invalid.',
            'email.unique' => 'This email address is already in use.',
            'exists'    => 'The value is not exist.',
            'min'       => 'The :attribute is too short',
            'regex'     => 'The :attribute is not valid.'
        ];
    }

    function response($code=200) {
        // if(! in_array($code, ['200', '201', '204', '404']) ) $this->response->status = 'FAIL';
        // $this->response->http_status = $this->codeMessage[$code];
        // if($code == '404') $this->response->message[] = 'No Record';
        // else if($code == '400') $this->response->error[] = 'Data processing failed.';
        // else if($code == '403') $this->response->error[] = 'Permission denied.';
        // else if($code == '409') $this->response->error[] = 'Duplicate entry found.';
        return response()->json($this->response, $code);

    }

    function auth() {
        $user = User::find( Auth()->user()->id );
        $user->city;
        $user->specialty;
        $user->roles; // permissions are included
        // $user->getPermissionsViaRoles();
        return $user;
        // return Auth()->user();
    }

    function validate($request, array $rules, array $messages = [], array $attributes = [])
    {
        $validationMessages = $this->validationMessages;
        if( count($messages) ) $validationMessages = array_merge($this->validationMessages, $messages);
        $validator = Validator::make($request, $rules, $validationMessages);

        if( $validator->fails() ) {
            $this->response->error[] = $validator->messages()->get('*');
            return $this->response(400);
        }
        return true;
    }

    function accessDenied($error = '') {
        $this->response->error[] = 'You are not authorized to perform this action.';
        if( $error ) $this->response->error[] = $error;
        return $this->response(403);
    }
}
