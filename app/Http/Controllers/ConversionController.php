<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Conversion;
use App\Jobs\ConvertPdfJob;
use Illuminate\Http\Response;

class ConversionController extends Controller
{
    /**
     * Upload a PDF and enqueue conversion
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $conversion = Conversion::create();
        $id = $conversion->id;

        $file = $request->file('file');
        $filename = "{$id}.pdf";

        Storage::disk('local')->putFileAs('', $file, $filename);

        ConvertPdfJob::dispatch($conversion);

        return response()->json([
            'id' => $id,
            'status_url' => route('convert.show', ['id' => $id]),
            'download_url' => route('convert.download', ['id' => $id]),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Show conversion status
     */
    public function show($id)
    {
        $pdfPath = Storage::disk('local')->path("{$id}.pdf");
        $docPath = Storage::disk('local')->path("{$id}.docx");

        if (!file_exists($pdfPath)) {
            return response()->json(['status' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        if (file_exists($docPath)) {
            return response()->json(['status' => 'done']);
        }

        return response()->json(['status' => 'processing']);
    }

    /**
     * Download converted DOCX
     */
    public function download($id)
    {
        $docPath = Storage::disk('local')->path("{$id}.docx");

        if (!file_exists($docPath)) {
            return response()->json(['error' => 'not_ready'], Response::HTTP_NOT_FOUND);
        }

        return response()->download($docPath, "conversion-{$id}.docx");
    }
}
