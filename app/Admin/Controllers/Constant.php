<?php

namespace App\Admin\Controllers;

abstract class Constant
{
    const VIEW_CUSTOMERS = "view.customers";
    const EDIT_CUSTOMERS = "edit.customers";
    const VIEW_PROPERTIES = "view.properties";
    const EDIT_PROPERTIES = "edit.properties";
    const VIEW_INVITATION_LETTERS = "view.invitationletters";
    const EDIT_INVITATION_LETTERS = "edit.invitationletters";
    const VIEW_CONTRACTS = "view.contracts";
    const EDIT_CONTRACTS = "edit.contracts";

    const CUSTOMER_TYPE = array(1 => "Khác hàng cá nhân", 2 => "Khách hàng doanh nghiệp");
    const PROPRERTY_TYPE = array(1 => "Bất động sản", 2 => "Phương tiện vận tải", 3 => "Máy móc thiết bị", 4 => "Giá trị doanh nghiệp", 5 => "Khoản nợ", 6 => "Tài sản hỗn hợp");
    const PROPRERTY_ADDRESS = array(1 => "Tỉnh/thành phố", 2 => "Quận/huyện", 3 => "Xã/phường/thị trấn", 4 => "Đường");
    const PROPRERTY_PURPOSE = array(1 => "Đất ở", 2 => "Đất nông nghiệp", 3 => "Đất TMDV", 4 => "Đất SXKD", 5 => "PNN", 6 => "Đất hỗn hợp");
    const VEHICLE_TYPE = array(1 => "Ô tô con", 2 => "Xe tải", 3 => "Xe đầu kéo", 4 => "Sơ mi rơ móc", 5 => "Xe nâng", 6 => "Tàu nội địa", 7 => "Tàu biển");
    const APPROVE_TYPE = array(0 => "Không", 1 => "Từ chối", 2 => "Đồng ý");

    const PAYMENT_METHOD = array(1 => "Chuyển khoản", 2 => "Tiền mặt");
    const YES_NO = array(0 => "Không", 1 => "Có");
    const INVITATION_STATUS = array(1 => "Mới tạo", 2 => "Khách từ chối");

    const DEFAULT_STATUS = array("Mới tạo" => "Mới tạo");
    const ROLES = array("administrator" => "administrator", "bld" => "bld", "nvkd" => "nvkd", "cvnv" => "cvnv", "tpnv" => "tpnv", "kscl" => "kscl", "hckt" => "hckt");
    const TABLES = array(
        "contracts" => "contracts", "task_notes" => "task_notes", "pre_assessments" => "pre_assessments",
        "official_assessments" => "official_assessments", "score_cards" => "score_cards"
    );

    const CONTRACT_TABLE = "contracts";
    const TASK_NOTE_TABLE = "task_notes";
    const PRE_ASSESS_TABLE = "pre_assessments";
    const OFFICIAL_ASSESS_TABLE = "official_assessments";
    const SCORE_CARD_TABLE = "score_cards";
}
