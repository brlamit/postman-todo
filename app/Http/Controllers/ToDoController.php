<?php

namespace App\Http\Controllers;

use App\Models\ToDo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToDoController extends Controller
{
    public function __construct()
    {
        // Protect all methods with authentication
        // $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the user's to-dos.
     */
    public function index()
    {
        $user = Auth::guard('sanctum')->user();
        $toDos = ToDo::where('user_id', $user->id)->orderBy('due_date', 'asc')->get();

        return response()->json([
            'message' => 'To-do list retrieved successfully',
            'to_dos' => $toDos,
        ], 200);
    }

    /**
     * Store a newly created to-do in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed,archived,not_started,cancelled',
        ]);

        $user = Auth::guard('sanctum')->user();
        $toDo = ToDo::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'completed' => false,
            'user_id' => $user->id,
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'To-do created successfully',
            'to_do' => $toDo,
        ], 201);
    }

    /**
     * Display the specified to-do.
     */
    public function show($id)
    {
        $user = Auth::guard('sanctum')->user();
        $toDo = ToDo::where('user_id', $user->id)->find($id);

        if (!$toDo) {
            return response()->json(['message' => 'To-do not found'], 404);
        }

        return response()->json([
            'message' => 'To-do retrieved successfully',
            'to_do' => $toDo,
        ], 200);
    }

    /**
     * Update the specified to-do in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user();
        $toDo = ToDo::where('user_id', $user->id)->find($id);

        if (!$toDo) {
            return response()->json(['message' => 'To-do not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'due_date' => 'sometimes|nullable|date',
            'priority' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:pending,in_progress,completed,archived,not_started,cancelled',
            'completed' => 'sometimes|boolean',
        ]);

        $toDo->update($validated);

        return response()->json([
            'message' => 'To-do updated successfully',
            'to_do' => $toDo,
        ], 200);
    }

    /**
     * Remove the specified to-do from storage.
     */
    public function destroy($id)
    {
        $user = Auth::guard('sanctum')->user();
        $toDo = ToDo::where('user_id', $user->id)->find($id);

        if (!$toDo) {
            return response()->json(['message' => 'To-do not found'], 404);
        }

        $toDo->delete();

        return response()->json(['message' => 'To-do deleted successfully'], 200);
    }
}