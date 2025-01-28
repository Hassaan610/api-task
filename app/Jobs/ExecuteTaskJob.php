<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Schedule;
use Mail;

class ExecuteTaskJob implements ShouldQueue
{
    public $record;

    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(Schedule $record)
    {
       $this->record = $record;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = Schedule::find($this->record->id);

        if ($task && $task->status === 'in queue') {
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
        }
    }
}
