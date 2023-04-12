<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        $logs = Log::with("loggable")->paginate(20);

        $actions = Log::ACTIONS;
        $models = Log::MODELS;

        return view("admin.logs.list", [
            'list' => $logs,
            'actions' => $actions,
            'models' => $models
        ]);
    }

    public function getLog(Request $request)
    {
        $id = $request->id;
        $dataType = $request->data_type;

        $log = Log::query()->with("loggable")->findOrFail($id);

        $logtype = $log->loggable_type;

        $data = json_decode($log->data);
        if ($dataType == "data")
        {
            return response()->json()->setData($data)->setStatusCode(200);
        }
        $data = $log->loggable;
        return view('admin.logs.model-log-view', compact("data", 'logtype'));

    }
}
