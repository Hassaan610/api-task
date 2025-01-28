<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Jobs\ExecuteTaskJob;
use Mail;

class ScheduleController extends Controller
{
   public function storeSchedule(Request $request)
   {
       	$validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,in queue,canceled,paused',
            'status_description' => 'required',
       	    'execution_date' => 'required|date|after:now',
       	]);

       	if ($validator->fails()) {
       	    return response()->json(['errors' => $validator->errors()], 422);
       	}

       	$validated = $validator->validated();
	
 	 	$task = Schedule::create([
 	 	    'execution_date' => Carbon::parse($validated['execution_date']),
 	 	    'status' => $validated['status'],
 	 	    'status_description' => $validated['status_description'],
 	 	    'exception' => null,
       	]);

       $delay = Carbon::parse($validated['execution_date'])->diffInSeconds(now());

       ExecuteTaskJob::dispatch($task)->delay($delay);
       return response()->json(['message' => 'Task added successfully', 'task' => $task], 201);
	}

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,in queue,canceled,paused',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $task = Schedule::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if ($task && $task->status == 'in queue' && $validated['status'] == 'completed') {
            try {
                $task->update([
                    'status' => 'completed',
                    'status_description' => 'Task executed successfully',
                ]);

                Mail::raw('Task #'.$task->id.' executed successfully.', function ($message) {
                    $message->to(env('ADMIN_EMAIL'))->subject('Task Completion');
                });
            } catch (\Exception $e) {
                $task->update([
                    'status' => 'canceled',
                    'status_description' => 'Error during execution.',
                    'exception' => $e->getMessage(),
                ]);

                Log::error('Execution Error: ' . $e->getMessage());
            }
        }else{    	
	        $task->update([
	            'status' => $validated['status'],
	        ]);
        }


        return response()->json(['message' => 'Task status updated successfully', 'task' => $task]);
    }

    public function randomResults()
    {
        $responses = ['success', 'error'];
        $randomResponse = $responses[array_rand($responses)];

        if ($randomResponse == 'error') {
        	return response()->json([
        		'status'=> $randomResponse
        	],500);
        }

        return response()->json([
			'status'=> $randomResponse
		],200);
    }
}
