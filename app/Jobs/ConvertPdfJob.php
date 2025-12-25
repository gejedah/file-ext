<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Conversion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class ConvertPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conversion = $this->conversion;
        $id = $conversion->id;

        $disk = Storage::disk('local');
        $pdfPath = $disk->path("{$id}.pdf");
        $outDir = dirname($pdfPath);
        $docPath = $disk->path("{$id}.docx");

        if (!file_exists($pdfPath)) {
            Log::warning("PDF not found for conversion {$id}");
            return;
        }

        // Run LibreOffice headless conversion
        $process = new Process([
            'soffice',
            '--headless',
            '--convert-to',
            'docx',
            '--outdir',
            $outDir,
            $pdfPath,
        ]);

        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            Log::error('Conversion failed for ' . $id . ': ' . $e->getMessage());
            return;
        }

        // LibreOffice will create a file with same basename but .docx extension
        $generated = $disk->path(pathinfo($pdfPath, PATHINFO_FILENAME) . '.docx');

        if (file_exists($generated)) {
            // ensure target name matches expected docPath
            if ($generated !== $docPath) {
                @rename($generated, $docPath);
            }
        }
    }
}
