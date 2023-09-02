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

    const CUSTOMER_TYPE = array(1 => "Khách hàng cá nhân", 2 => "Khách hàng doanh nghiệp");
    const INVITATION_LETTERS_TYPE = array("Lỗi nghiêm trọng" => "Lỗi nghiêm trọng", "Lỗi nghiệp vụ" => "Lỗi nghiệp vụ", "Lỗi cơ bản" => "Lỗi cơ bản");
    const PROPRERTY_TYPE = array(1 => "Bất động sản", 2 => "Phương tiện vận tải", 3 => "Máy móc thiết bị", 4 => "Giá trị doanh nghiệp", 5 => "Khoản nợ", 6 => "Tài sản hỗn hợp");
    const PROPRERTY_ADDRESS = array(1 => "Tỉnh/thành phố", 2 => "Quận/huyện", 3 => "Xã/phường/thị trấn", 4 => "Đường");
    const INVITATION_PURPOSE = array("Thế chấp vay vốn" => "Thế chấp vay vốn", "Xử lý nợ" => "Xử lý nợ", "Phê duyệt dự toán" => "Phê duyệt dự toán",
                                      "Góp vốn đầu tư" => "Góp vốn đầu tư", "Mua sắm - thanh lý tài sản" => "Mua sắm - thanh lý tài sản",
                                      "Mua bán chuyển nhượng" => "Mua bán chuyển nhượng", "Mục đích khác" => "Mục đích khác");
    const PROPRERTY_PURPOSE = array(1 => "Đất ở", 2 => "Đất nông nghiệp", 3 => "Đất TMDV", 4 => "Đất SXKD", 5 => "PNN", 6 => "Đất hỗn hợp");
    const VEHICLE_TYPE = array(1 => "Ô tô con", 2 => "Xe tải", 3 => "Xe đầu kéo", 4 => "Sơ mi rơ móc", 5 => "Xe nâng", 6 => "Tàu nội địa", 7 => "Tàu biển");
    const APPROVE_TYPE = array(0 => "Không", 1 => "Từ chối", 2 => "Đồng ý");
    const ASSESSMENT_TYPE = array(  "So sánh" => "So sánh", 
                                    "Chi phí thay thế" => "Chi phí thay thế",
                                    "Chi phí tái tạo" => "Chi phí tái tạo",
                                    "Vốn hoá trực tiếp" => "Vốn hoá trực tiếp",
                                    "Dòng tiền chiết khấu" => "Dòng tiền chiết khấu",
                                    "Chiết trừ" => "Chiết trừ",
                                    "Thặng dư" =>  "Thặng dư",
                                    "Giá giao dịch" => "Giá giao dịch",
                                    "Tỷ số bình quân" => "Tỷ số bình quân",
                                    "Tài sản" => "Tài sản",
                                    "Chiết khấu dòng cổ tức" => "Chiết khấu dòng cổ tức",
                                    "Chiết khấu dòng tiền tự do vốn chủ sở hữu" => "Chiết khấu dòng tiền tự do vốn chủ sở hữu" ,
                                    "Chiết khấu dòng tiền tự do của doanh nghiệp" => "Chiết khấu dòng tiền tự do của doanh nghiệp",
                                    "Tiền sử dụng tài sản vô hình" => "Tiền sử dụng tài sản vô hình",
                                    "Lợi nhuận vượt trội" => "Lợi nhuận vượt trội",
                                    "Thu nhập tăng thêm" => "Thu nhập tăng thêm");

    const CONTRACT_TYPE = array(0 => "Sơ bộ", 1 => "Chính thức");
    const PAYMENT_METHOD = array(1 => "Chuyển khoản", 2 => "Tiền mặt");
    const YES_NO = array(0 => "Không", 1 => "Có");
    const INVITATION_STATUS = array(1 => "Chưa gửi khách hàng", 2 => "Khách hàng đồng ý",  2 => "Khách hàng từ chối");

    const BUSINESS_STAFF = 5;
    const QA_STAFF = 7;

    const PRE_CONTRACT_INPUTTING_STATUS = 65;
    const CONTRACT_INPUTTING_STATUS = 56;
    const WAIT_ASSIGN = 85;
    const OFFICIAL_ASSIGN = 70;
    const ASSESSMENT_DONE_STATUS = 58;
    const PRE_CONTRACT_TYPE = 0;
    const OFFICIAL_CONTRACT_TYPE = 1;
    const DONE_CONTRACT_STATUS = 35;
    const DONE_SCORE_STATUS = 74;
    const PRE_CONTRACT_INIT = 64;
    const OFFICIAL_CONTRACT_INIT = 6;
    const PRE_CONTRACT_REQUIRE = 66;
    const OFFICIAL_CONTRACT_REQUIRE = 70;





    const DEFAULT_STATUS = array("Mới tạo" => "Mới tạo");
    const DIRECTOR_ROLE = 'bld';
    const ROLES = array("administrator" => "administrator", "bld" => "bld", "nvkd" => "nvkd", "cvnv" => "cvnv", "tpnv" => "tpnv", "kscl" => "kscl", "hckt" => "hckt");
    const TABLES = array("invitation_letters" => "invitation_letters", "pre_contracts" => "pre_contracts",
        "contracts" => "contracts", "task_notes" => "task_notes", "pre_assessments" => "pre_assessments",
        "official_assessments" => "official_assessments", "score_cards" => "score_cards", 
        "contract_acceptances" => "contract_acceptances", "valuation_documents" => "valuation_documents"
    );

    const INVITATION_LETTER_TABLE = "invitation_letters";
    const PRE_CONTRACT_TABLE = "pre_contracts";
    const CONTRACT_TABLE = "contracts";
    const TASK_NOTE_TABLE = "task_notes";
    const PRE_ASSESS_TABLE = "pre_assessments";
    const OFFICIAL_ASSESS_TABLE = "official_assessments";
    const SCORE_CARD_TABLE = "score_cards";
    const CONTRACT_ACCEPTANCE_TABLE = "contract_acceptances";
    const VALUATION_DOCUMENT_TABLE = "valuation_documents";
}
