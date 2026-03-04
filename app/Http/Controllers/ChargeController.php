<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\CreateInvoiceRequest;
use App\Models\Invoice;

class ChargeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateInvoiceRequest $request)
    {
        $data = $request->validated();

        if ($invoice = Invoice::where('invoice_id', $data['invoice_id'])->first()) {
            if ($invoice->status === InvoiceStatus::PAID->value) {
                return response()->json([
                    'status' => false,
                    'ulid' => $invoice->ulid,
                    'invoice_id' => $invoice->invoice_id,
                    'payment_url' => $invoice->payment_url,
                    'message' => 'Invoice already paid.',
                ], 200);
            }

            return response()->json([
                'status' => true,
                'ulid' => $invoice->ulid,
                'invoice_id' => $invoice->invoice_id,
                'payment_url' => $invoice->payment_url,
                'message' => 'Invoice already exists.',
            ], 200);
        }

        $invoice = Invoice::create($data);

        return response()->json([
            'status' => true,
            'ulid' => $invoice->ulid,
            'invoice_id' => $invoice->invoice_id,
            'payment_url' => $invoice->payment_url,
            'message' => 'Invoice created successfully.',
        ], 201);
    }
}
