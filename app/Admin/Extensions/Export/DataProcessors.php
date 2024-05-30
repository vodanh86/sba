<?php
namespace App\Admin\Extensions\Export;

use App\Admin\Controllers\Constant;
use App\Http\Models\AdminUser;
use App\Http\Models\Contract;
use App\Http\Models\ContractAcceptance;
use App\Http\Models\OfficialAssessment;
use App\Http\Models\PreAssessment;
use App\Http\Models\ScoreCard;


class DataProcessors
{
    public static function processContractAcceptanceData($branch_id)
    {
        $processedData = array();
        $query = ContractAcceptance::where('branch_id', $branch_id);

        foreach ($query->get() as $contractAcceptance) {
            $processedData[] = [
                $contractAcceptance->id,
                $contractAcceptance->contract->code,
                $contractAcceptance->contract->property,
                $contractAcceptance->date_acceptance,
                $contractAcceptance->contract->customer_type,
                $contractAcceptance->contract->tax_number,
                $contractAcceptance->contract->business_name,
                $contractAcceptance->contract->personal_address,
                $contractAcceptance->contract->representative,
                $contractAcceptance->contract->position,
                $contractAcceptance->contract->personal_name,
                $contractAcceptance->contract->id_number,
                $contractAcceptance->contract->issue_place,
                $contractAcceptance->contract->issue_date,
                $contractAcceptance->export_bill,
                $contractAcceptance->buyer_name,
                $contractAcceptance->buyer_address,
                $contractAcceptance->tax_number,
                $contractAcceptance->bill_content,
                $contractAcceptance->total_fee,
                $contractAcceptance->delivery,
                $contractAcceptance->recipient,
                $contractAcceptance->advance_fee,
                $contractAcceptance->official_fee,
                $contractAcceptance->statusDetail->name,
                $contractAcceptance->created_at,
                $contractAcceptance->updated_at
            ];
        }
        return $processedData;
    }

    public static function processContractData($branch_id)
    {
        $processedData = array();
        $contracts = Contract::where('branch_id', $branch_id)->where('contract_type', 1)->get();

        foreach ($contracts as $contract) {
            $contractType = Constant::CONTRACT_TYPE[$contract->contract_type] ?? '';
            $customerType = Constant::CUSTOMER_TYPE[$contract->customer_type] ?? '';
            $tdv = $contract->tdvDetail->name ?? '';
            $legalRepresentative = $contract->legalRepresentative->name ?? '';
            $tdvMigrate = $contract->tdvDetail->tdvMigrate->name ?? '';
            $assistant = $contract->assistant->name ?? '';
            $supervisor = $contract->supervisorDetail->name ?? '';
            $creator = $contract->creator->name ?? '';

            $processedData[] = [
                $contract->id,
                $contract->code,
                $contractType,
                $contract->created_date,
                $customerType,
                $contract->tax_number,
                $contract->business_name,
                $contract->business_address,
                $contract->representative,
                $contract->position,
                $contract->personal_address,
                $contract->print,
                $contract->id_number,
                $contract->personal_name,
                $contract->issue_place,
                $contract->issue_date,
                $contract->property,
                $contract->purpose,
                $contract->appraisal_date,
                $contract->from_date,
                $contract->to_date,
                $contract->total_fee,
                $contract->advance_fee,
                $contract->broker,
                $contract->source,
                $contract->sale,
                $tdv,
                $legalRepresentative,
                $tdvMigrate,
                $assistant,
                $supervisor,
                $contract->net_revenue,
                $contract->contact,
                $contract->note,
                $contract->comment,
                $contract->statusDetail->name ?? '',
                $creator,
                $contract->created_at,
                $contract->updated_at
            ];
        }

        return $processedData;
    }

    public static function processDoneContractData($branch_id)
    {
        $processedData = array();
        $query = ContractAcceptance::where('branch_id', $branch_id)->where('status', 35);

        foreach ($query->get() as $index => $contractAcceptance) {
            $creator = optional(AdminUser::find($contractAcceptance->created_by))->name;
            $processedData[] = [
                $contractAcceptance->id,
                $contractAcceptance->contract->code,
                $contractAcceptance->contract->property,
                $contractAcceptance->date_acceptance,
                $contractAcceptance->contract->customer_type,
                $contractAcceptance->contract->tax_number,
                $contractAcceptance->contract->business_name,
                $contractAcceptance->contract->business_address,
                $contractAcceptance->contract->representative,
                $contractAcceptance->contract->position,
                $contractAcceptance->contract->personal_name,
                $contractAcceptance->contract->id_number,
                $contractAcceptance->contract->issue_place,
                $contractAcceptance->contract->issue_date,
                $contractAcceptance->export_bill,
                $contractAcceptance->buyer_name,
                $contractAcceptance->buyer_address,
                $contractAcceptance->tax_number,
                $contractAcceptance->bill_content,
                $contractAcceptance->total_fee,
                $contractAcceptance->delivery,
                $contractAcceptance->recipient,
                $contractAcceptance->advance_fee,
                $contractAcceptance->official_fee,
                $contractAcceptance->contract->net_revenue,
                $creator,
                $contractAcceptance->created_at,
                $contractAcceptance->updated_at
            ];
        }
        return $processedData;
    }

    public static function processOfficialAssessmentData($branch_id)
    {
        $processedData = array();
        $query = OfficialAssessment::where('branch_id', $branch_id);

        foreach ($query->get() as $index => $officialAssessment) {
            $performerDetail = optional(AdminUser::find($officialAssessment->performer))->name;
            $processedData[] = [
                $officialAssessment->id,
                $officialAssessment->contract->code,
                $officialAssessment->certificate_code,
                $officialAssessment->certificate_date,
                $officialAssessment->contract->property,
                $officialAssessment->finished_date,
                $performerDetail,
                $officialAssessment->assessment_type,
                $officialAssessment->note,
                $officialAssessment->statusDetail->name,
                $officialAssessment->official_value,
                $officialAssessment->comment,
                $officialAssessment->created_at,
                $officialAssessment->updated_at
            ];
        }
        return $processedData;
    }

    public static function processPreAssessmentData($branch_id)
    {
        $processedData = array();
        $query = PreAssessment::where('branch_id', $branch_id);

        foreach ($query->get() as $index => $preAssessment) {
            $performerDetail = optional(AdminUser::find($preAssessment->performer))->name;
            $processedData[] = [
                $preAssessment->id,
                $preAssessment->contract->code,
                $preAssessment->contract->property,
                $preAssessment->finished_date,
                $performerDetail,
                $preAssessment->note,
                $preAssessment->comment,
                $preAssessment->statusDetail->name,
                $preAssessment->pre_value,
                $preAssessment->created_at,
                $preAssessment->updated_at
            ];
        }
        return $processedData;
    }

    public static function processPreContractData($branch_id)
    {
        $processedData = array();
        $contracts = Contract::where('branch_id', $branch_id)->where('contract_type', 1)->get();

        foreach ($contracts as $index => $contract) {
            $contractType = Constant::CONTRACT_TYPE[$contract->contract_type];
            $customerType = Constant::CUSTOMER_TYPE[$contract->customer_type];
            $tdv = optional(AdminUser::find($contract->tdv))->name;
            $legalRepresentative = optional(AdminUser::find($contract->legal_representative))->name;
            $tdvMigrate = optional(AdminUser::find($contract->tdv_migrate))->name;
            $assistant = optional(AdminUser::find($contract->tdv_assistant))->name;
            $supervisor = optional(AdminUser::find($contract->supervisor))->name;
            $creator = optional(AdminUser::find($contract->created_by))->name;
            $processedData[] = [
                $contract->id,
                $contract->code,
                $contractType,
                $contract->created_date,
                $customerType,
                $contract->tax_number,
                $contract->business_name,
                $contract->business_address,
                $contract->representative,
                $contract->position,
                $contract->personal_address,
                $contract->print,
                $contract->id_number,
                $contract->personal_name,
                $contract->issue_place,
                $contract->issue_date,
                $contract->property,
                $contract->purpose,
                $contract->appraisal_date,
                $contract->from_date,
                $contract->to_date,
                $contract->total_fee,
                $contract->advance_fee,
                $contract->broker,
                $contract->source,
                $contract->sale,
                $tdv,
                $legalRepresentative,
                $tdvMigrate,
                $assistant,
                $supervisor,
                $contract->net_revenue,
                $contract->contact,
                $contract->note,
                $contract->comment,
                $contract->statusDetail->name,
                $creator,
                $contract->created_at,
                $contract->updated_at
            ];
        }
        return $processedData;
    }

    public static function processScoreCardData($branch_id)
    {
        $processedData = array();
        $query = ScoreCard::where('branch_id', $branch_id);

        foreach ($query->get() as $index => $scoreCard) {
            $processedData[] = [
                $scoreCard->id,
                $scoreCard->contract->code,
                $scoreCard->contract->property,
                $scoreCard->score,
                $scoreCard->basic_error,
                $scoreCard->business_error,
                $scoreCard->serious_error,
                $scoreCard->note,
                $scoreCard->statusDetail->name,
                $scoreCard->comment,
                $scoreCard->created_at,
                $scoreCard->updated_at
            ];
        }
        return $processedData;
    }
}
