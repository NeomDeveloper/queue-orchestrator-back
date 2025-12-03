<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class LargeTask implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $broadcast = Http::post('http://realtime:3001/broadcast', [
            'channel' => 'tasks',
            'event' => 'TaskCreated',
            'task' => [
                "queue" => $this->job->getQueue()
            ]
        ])->body();
        
        $ini = microtime(true);
        $rand = 5;
        $infosLog = [
            'id' => $this->job->getJobId(),
            'queue' => $this->job->getQueue(),
            'rand' => $rand
        ];
        Log::info("Starting large task", $infosLog);

        for ($i=0; $i < $rand; $i++) {
             
            // Simulate a large task
            sleep(1);
            $infosLog['progress'] = (($i+1) * 100) / $rand;
            //Log::info("Large task progress...", $infosLog);
            $broadcast = Http::post('http://realtime:3001/broadcast', [
                'channel' => 'tasks',
                'event' => 'TaskProgress',
                'data' => [
                    'task_id' => $this->job->getJobId(),
                    'queue' => $this->job->getQueue(),
                    'progress' => $infosLog['progress'],
                ],
                'task'=> [
                    'x' => $this->job->getQueue() . "-" . $this->job->getJobId(),
                    'y' => $infosLog['progress'],
                ]
            ])->body();
            Log::info("Sending to broadcast", [
                'return' => $broadcast
            ]);
            
        }
        //sleep(rand(5,10));
        //Log::info("Completed large task...", $infosLog);
        $end = microtime(true);
        $broadcast = Http::post('http://realtime:3001/broadcast', [
                'channel' => 'tasks',
                'event' => 'TaskCompleted',
                'data' => [
                    'task_id' => $this->job->getJobId(),
                    'queue' => $this->job->getQueue(),
                    'infoLog' => $infosLog
                ],
                'task'=> [
                    'x' => $this->job->getQueue()."-".$this->job->getJobId(),
                    'y' => ($end - $ini),
                ]
            ])->body();
        Log::info("Sending to broadcast", [
            'return' => $broadcast
        ]
    );
        
    }
}
