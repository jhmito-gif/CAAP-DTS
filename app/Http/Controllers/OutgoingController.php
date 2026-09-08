<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OutgoingController extends Controller
{
    //
    public function Index()
    {
        return view('records.outgoing');
    } 
}
