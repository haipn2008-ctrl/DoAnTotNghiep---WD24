<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\View\View;

class LandlordInformationController extends Controller
{
    public function __invoke(): View
    {
        return view('client.landlord-information', [
            'setting' => Setting::currentOrCreate(),
        ]);
    }
}
