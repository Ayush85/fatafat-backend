<?php

namespace App\Http\Controllers\API\v2\File;

use App\Http\Controllers\Controller;
use App\Services\DailyUploadsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    public function __construct(private DailyUploadsService $dailyUploadsService) {}

    /**
     * Backwards-compatible alias for the previous single-file upload endpoint.
     */
    public function upload(Request $request)
    {
        return $this->uploadSingle($request);
    }

    /**
     * Upload a single image file.
     *
     * Expects multipart/form-data with:
     * - file: image/*, pdf, doc, docx (see validation rule), max 2MB
     */
    public function uploadSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // "image" covers most common image formats; we explicitly allow other common image extensions too.
            'file' => 'required|file|max:2048|mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,heif,tif,tiff,pdf,doc,docx',
            'directory' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json($this->dailyUploadsService->uploadToDailyUploads(
            file: $request->file('file'),
            directory: $request->input('directory')
        ));
    }

    /**
     * Upload multiple image files in a single request.
     *
     * Expects multipart/form-data with:
     * - files[]: array of image/*, pdf, doc, docx (see validation rule), max 2MB each
     */
    public function uploadBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:2048|mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,heif,tif,tiff,pdf,doc,docx',
            'directory' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json($this->dailyUploadsService->uploadManyToDailyUploads(
            files: $request->file('files', []),
            directory: $request->input('directory')
        ));
    }
}
