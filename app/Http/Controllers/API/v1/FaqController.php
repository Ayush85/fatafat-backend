<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

/**
 * @group FAQs
 *
 * Frequently asked questions endpoints.
 */
class FaqController extends Controller
{
    /**
     * List FAQs
     *
     * @name List FAQs
     */
    public function index(Request $request)
    {
        $query = Faq::query();

        // Optional filtering by type if needed in future
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        $faqs = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $faqs,
            'message' => 'FAQs retrieved successfully'
        ]);
    }
}
