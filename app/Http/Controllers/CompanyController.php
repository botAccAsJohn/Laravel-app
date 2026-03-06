<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __invoke(Request $request)
    {
        dd(config("company.name"));
    }
}
