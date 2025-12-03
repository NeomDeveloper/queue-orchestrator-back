<?php

namespace App\Http\Controllers;

class TasksController extends Controllers
{
    // Lists all tasks
    public function index()
    {
        return response()->json([
            'message' => 'Listing tasks',
            'data' => Db::table('jobs')->get()
        ]);
    }
}
