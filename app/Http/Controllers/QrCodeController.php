<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Models\AdminUser;

class QrCodeController extends Controller
{
    public function show($encoded_id, Request $request)
    {
        $decoded_contractCode = base64_decode($encoded_id);

        $convertIdToNameUser = function ($userId) {
            $adminUser = AdminUser::find($userId);
            return $adminUser ? mb_strtoupper($adminUser->name, 'UTF-8') : '';
        };

        $qrCodeRecord = DB::table('qr_codes')
            ->join('contracts', 'qr_codes.contract_code', '=', 'contracts.code')
            ->leftJoin('official_assessments', 'contracts.id', '=', 'official_assessments.contract_id') // Thêm join tới bảng official_assessments
            ->select(
                'qr_codes.*',
                'contracts.customer_type',
                DB::raw(
                    'CASE
            WHEN contracts.customer_type = 1
                THEN contracts.personal_name
                ELSE contracts.business_name
            END as customer_name'
                ),
                'contracts.tdv_migrate',
                'contracts.created_date',
                'contracts.property_address',
                'contracts.total_fee',
                'official_assessments.certificate_code'
            )
            ->where('qr_codes.contract_code', $decoded_contractCode)
            ->first();

        if ($qrCodeRecord) {
            if ($request->isMethod('post')) {
                $inputPin = $request->input('pin');

                if ($inputPin == $qrCodeRecord->pin_code) {
                    $suffix = explode('.', $qrCodeRecord->contract_code)[1] ?? '';
                    $count = DB::table('qr_codes')
                        ->where('contract_code', 'like', '%' . $suffix)
                        ->count();
                    $ordinal = DB::table('qr_codes')
                        ->where('contract_code', 'like', '%' . $suffix)
                        ->pluck('contract_code')
                        ->search($qrCodeRecord->contract_code);
                    $original_number = str_pad($ordinal + 1, 4, '0', STR_PAD_LEFT);

                    $qrCodeRecord->supervisor = $convertIdToNameUser($qrCodeRecord->tdv_migrate ?? null);
                    $qrCodeRecord->original_number = $original_number;
                    return view('qrCode', compact('qrCodeRecord'));
                } else {
                    return back()->withErrors(['pin' => 'Mã PIN không đúng']);
                }
            }

            return view('qrCodePinForm', compact('qrCodeRecord'));
        } else {
            return abort(404);
        }
    }
}
