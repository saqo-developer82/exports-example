<?php

namespace Exports\Jobs;

use App\Jobs\Job;
use Exports\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GenerateGeneralExport extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $user;
    private $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $request_array)
    {
        $this->user = $user;
        $this->request = new Request($request_array);
        $this->setUseQueue();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->dispatchIfNeeded();

        $entity = ucfirst($this->request->entity);
        $filename = $entity . "Export-" . $this->user->company_id;
        $entity_export_class_name = str_plural(ucfirst(Str::camel($entity))) . 'Export';
        $export_namespace = '\\Exports\\Exporters\\' . $entity_export_class_name;

        $attachment_file = \Excel::download(
            new $export_namespace($this->user->id, $this->request),
            $filename . '.csv'
        )->getFile();

        $attachment_file_path = $attachment_file->getRealPath();

        $subject = "$entity Export";
        $email_body = "Your $entity export is attached.";

        $notifiable = $this->user;

        Mail::send('emails.general', ['email_body' => $email_body], function ($m) use ($entity, $subject, $notifiable, $attachment_file_path) {
            $from_address = config('mail.from.address');
            $from_name = config('mail.from.name');
            $m->from($from_address, $from_name);
            $m->to($notifiable->email, $notifiable->full_name)->subject($subject);
            $m->attach($attachment_file_path,
                [
                    'as' => ucwords(str_replace('-', '_', $entity)) . '_export_sheet.csv',
                    'mime' => 'text/csv'
                ]);
        });

        Notification::sendEntityExportNotification($this->user, [
            'entity' => $this->request->entity,
            'export_percentage' => 100
        ]);
    }
}
