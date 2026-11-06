<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\HelpContact;

class HelpController extends Controller
{
    /**
     * Return all help data: contacts + FAQs.
     */
    public function index()
    {
        $contacts = HelpContact::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'type', 'title', 'value', 'icon']);

        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'question', 'answer']);

        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => $contacts,
                'faqs' => $faqs,
            ],
        ]);
    }
}
