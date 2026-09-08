<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Record;
use Illuminate\Support\Facades\Auth;

class IncomingController extends Controller
{
    //

    public function Index()
    {
        return view('records.incoming');
    }    
}
