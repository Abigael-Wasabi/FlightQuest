<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;


class Handler extends ExceptionHandler
{


    protected function unauthenticated( $request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated. Would you like to login or signup?'], 401);
        }

        return redirect()->guest(route('login'))->with('message', 'Would you like to login or signup?');
    }

}
