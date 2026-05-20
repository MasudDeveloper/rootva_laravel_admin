<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PcashRechargeLog;

class PcashLogController extends Controller
{
    public function index()
    {
        $logs = PcashRechargeLog::latest()->paginate(20);
        return view('admin.pcash.logs.index', compact('logs'));
    }
}
