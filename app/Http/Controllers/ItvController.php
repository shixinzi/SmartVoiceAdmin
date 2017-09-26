<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ItvTrigger;

class ItvController extends Controller
{
    public function trigger(Request $request)
    {
        ItvTrigger::create($request->all());
        return response()->json([
            'status' => 0,
        ]);
    }
}
