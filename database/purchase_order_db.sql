-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- 생성 시간: 25-08-05 04:42
-- 서버 버전: 10.4.32-MariaDB
-- PHP 버전: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `purchase_order_db`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `access_tokens`
--

CREATE TABLE `access_tokens` (
                                 `id` int(30) NOT NULL,
                                 `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                 `token` varchar(100) NOT NULL COMMENT '토큰',
                                 `token_type` enum('upload','view','admin') DEFAULT 'upload' COMMENT '토큰 타입',
                                 `expires_at` datetime NOT NULL COMMENT '만료 시간',
                                 `is_used` tinyint(1) DEFAULT 0 COMMENT '사용 여부',
                                 `used_at` datetime DEFAULT NULL COMMENT '사용 시간',
                                 `ip_address` varchar(45) DEFAULT NULL COMMENT '사용된 IP',
                                 `user_agent` text DEFAULT NULL COMMENT '사용된 브라우저',
                                 `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `document_categories`
--

CREATE TABLE `document_categories` (
                                       `id` int(30) NOT NULL,
                                       `parent_id` int(30) DEFAULT NULL COMMENT '상위 분류 ID',
                                       `name` varchar(255) NOT NULL COMMENT '분류명',
                                       `level` tinyint(1) NOT NULL COMMENT '분류 레벨 (1=대, 2=중, 3=소)',
                                       `type` enum('folder','document') DEFAULT 'folder',
                                       `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
                                       `is_required` tinyint(1) DEFAULT 0 COMMENT '필수 서류 여부',
                                       `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                       `description` text DEFAULT NULL,
                                       `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_categories`
--

INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `type`, `display_order`, `is_required`, `status`, `description`, `date_created`) VALUES
                                                                                                                                                            (1, NULL, '안전관리계획서', 1, 'folder', 1, 0, 1, '', '2025-07-31 18:52:23'),
                                                                                                                                                            (2, NULL, '유해위험방지계획서', 1, 'folder', 2, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (3, NULL, '교육시설안전성평가', 1, 'folder', 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (4, NULL, '철도보호지구안전성평가', 1, 'folder', 4, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (5, NULL, '소규모안전관리계획서', 1, 'folder', 5, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (6, NULL, '기본', 1, 'folder', 6, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (7, NULL, '설계', 1, 'folder', 7, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (8, NULL, '공사안전보건대장', 1, 'folder', 8, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (9, NULL, '적정성평가', 1, 'folder', 9, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (10, NULL, '기타', 1, 'folder', 10, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (25, 1, '안전점검계획서', 2, 'folder', 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (27, 2, '고소작업방지계획서', 2, 'folder', 2, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (28, 2, '크레인작업방지계획서', 2, 'folder', 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (29, 8, '안전보건대장 기본양식', 2, 'folder', 1, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (30, 8, '작업일지', 2, 'folder', 2, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (31, 8, '안전교육 기록', 2, 'folder', 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (32, 10, '사업자등록증', 2, 'folder', 1, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (33, 10, '건설업면허증', 2, 'folder', 2, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (34, 10, '보험가입증명서', 2, 'folder', 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                            (37, 1, 'A', 2, 'document', 0, 0, 1, NULL, '2025-08-01 11:40:06'),
                                                                                                                                                            (38, 25, 'B', 3, 'folder', 0, 0, 1, NULL, '2025-08-01 11:40:29'),
                                                                                                                                                            (39, 2, 'C', 2, 'document', 0, 0, 1, NULL, '2025-08-01 11:40:40'),
                                                                                                                                                            (41, 27, 'D', 3, 'folder', 0, 1, 1, NULL, '2025-08-04 16:24:57');

-- --------------------------------------------------------

--
-- 테이블 구조 `document_costs`
--

CREATE TABLE `document_costs` (
                                  `id` int(30) NOT NULL,
                                  `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                                  `base_cost` decimal(10,2) DEFAULT 0.00 COMMENT '기본 작성비용',
                                  `vat_percentage` decimal(5,2) DEFAULT 10.00 COMMENT 'VAT 비율',
                                  `vat_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'VAT 금액',
                                  `total_cost` decimal(10,2) DEFAULT 0.00 COMMENT '총 비용',
                                  `cost_type` enum('fixed','variable','negotiable') DEFAULT 'fixed' COMMENT '비용 타입',
                                  `effective_date` date NOT NULL COMMENT '적용 시작일',
                                  `expiry_date` date DEFAULT NULL COMMENT '적용 종료일',
                                  `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_costs`
--

INSERT INTO `document_costs` (`id`, `category_id`, `base_cost`, `vat_percentage`, `vat_amount`, `total_cost`, `cost_type`, `effective_date`, `expiry_date`, `status`, `date_created`) VALUES
                                                                                                                                                                                          (37, 1, 220000.00, 10.00, 22000.00, 242000.00, 'fixed', '2025-01-01', NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                          (38, 2, 350000.00, 10.00, 35000.00, 385000.00, 'fixed', '2025-01-01', NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                          (39, 8, 130000.00, 10.00, 13000.00, 143000.00, 'fixed', '2025-01-01', NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                          (40, 9, 700000.00, 10.00, 70000.00, 770000.00, 'fixed', '2025-01-01', NULL, 1, '2025-07-31 18:52:23');

-- --------------------------------------------------------

--
-- 테이블 구조 `document_cost_details`
--

CREATE TABLE `document_cost_details` (
                                         `id` int(30) NOT NULL,
                                         `request_id` int(30) NOT NULL,
                                         `safety_plan_cost` varchar(50) DEFAULT NULL,
                                         `hazard_prevention_cost` varchar(50) DEFAULT NULL,
                                         `structure_review_cost` varchar(50) DEFAULT NULL,
                                         `structure_review_agency` varchar(100) DEFAULT NULL,
                                         `plan_review_cost` varchar(50) DEFAULT NULL,
                                         `plan_review_agency` varchar(100) DEFAULT NULL,
                                         `safety_health_cost` varchar(50) DEFAULT NULL,
                                         `education_facility_cost` varchar(50) DEFAULT NULL,
                                         `railway_protection_cost` varchar(50) DEFAULT NULL,
                                         `evaluation_cost` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_cost_details`
--

INSERT INTO `document_cost_details` (`id`, `request_id`, `safety_plan_cost`, `hazard_prevention_cost`, `structure_review_cost`, `structure_review_agency`, `plan_review_cost`, `plan_review_agency`, `safety_health_cost`, `education_facility_cost`, `railway_protection_cost`, `evaluation_cost`) VALUES
    (10, 10, '11', NULL, '11', '', '11', '', '11', NULL, NULL, '');

-- --------------------------------------------------------

--
-- 테이블 구조 `document_requests`
--

CREATE TABLE `document_requests` (
                                     `id` int(30) NOT NULL,
                                     `request_no` varchar(50) NOT NULL COMMENT '요청번호 (REQ-YYYYMMDD-001)',
                                     `supplier_id` int(30) NOT NULL COMMENT '의뢰처 ID',
                                     `project_name` varchar(255) NOT NULL COMMENT '프로젝트명',
                                     `due_date` date NOT NULL COMMENT '제출 기한',
                                     `additional_notes` text DEFAULT NULL COMMENT '추가 요청사항',
                                     `upload_token` varchar(100) NOT NULL COMMENT '업로드 토큰',
                                     `status` tinyint(1) DEFAULT 0 COMMENT '상태 (0=대기, 1=진행중, 2=완료)',
                                     `email_sent_at` datetime DEFAULT NULL COMMENT '이메일 전송 시간',
                                     `completed_at` datetime DEFAULT NULL,
                                     `created_by` int(30) NOT NULL COMMENT '생성자 ID',
                                     `date_created` datetime DEFAULT current_timestamp(),
                                     `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_requests`
--

INSERT INTO `document_requests` (`id`, `request_no`, `supplier_id`, `project_name`, `due_date`, `additional_notes`, `upload_token`, `status`, `email_sent_at`, `completed_at`, `created_by`, `date_created`, `date_updated`) VALUES
    (10, 'REQ-20250805-001', 3, '전천 지방하천 정비사업', '9999-12-31', '', 'decc8266e75a4364fa927e2b7c98b097f8d612020ef1cc5d1a568c36a6cd6a4a', 2, '2025-08-05 10:52:10', '2025-08-05 11:29:57', 1, '2025-08-05 10:51:20', '2025-08-05 11:29:57');

-- --------------------------------------------------------

--
-- 테이블 구조 `document_request_details`
--

CREATE TABLE `document_request_details` (
                                            `id` int(30) NOT NULL,
                                            `request_id` int(30) NOT NULL,
                                            `construction_method` varchar(50) DEFAULT NULL,
                                            `manager_name` varchar(100) NOT NULL,
                                            `manager_contact` varchar(50) NOT NULL,
                                            `manager_email` varchar(100) NOT NULL,
                                            `director_name` varchar(100) DEFAULT NULL,
                                            `director_contact` varchar(50) DEFAULT NULL,
                                            `order_date` date DEFAULT NULL,
                                            `total_cost` varchar(50) DEFAULT NULL,
                                            `vat_included` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_request_details`
--

INSERT INTO `document_request_details` (`id`, `request_id`, `construction_method`, `manager_name`, `manager_contact`, `manager_email`, `director_name`, `director_contact`, `order_date`, `total_cost`, `vat_included`) VALUES
    (10, 10, '단독', '', '', '', '', '', '2025-08-05', '44', 0);

-- --------------------------------------------------------

--
-- 테이블 구조 `document_targets`
--

CREATE TABLE `document_targets` (
                                    `id` int(30) NOT NULL,
                                    `request_id` int(30) NOT NULL,
                                    `safety_plan_type` varchar(50) DEFAULT NULL,
                                    `review_agency` varchar(100) DEFAULT NULL,
                                    `hazard_prevention_type` varchar(50) DEFAULT NULL,
                                    `safety_health_agency` varchar(100) DEFAULT NULL,
                                    `safety_health_ledger_type` varchar(50) DEFAULT NULL,
                                    `evaluation_type` varchar(50) DEFAULT NULL,
                                    `education_facility` text DEFAULT NULL,
                                    `education_office` varchar(100) DEFAULT NULL,
                                    `railway_protection` text DEFAULT NULL,
                                    `railway_manager` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_targets`
--

INSERT INTO `document_targets` (`id`, `request_id`, `safety_plan_type`, `review_agency`, `hazard_prevention_type`, `safety_health_agency`, `safety_health_ledger_type`, `evaluation_type`, `education_facility`, `education_office`, `railway_protection`, `railway_manager`) VALUES
    (10, 10, '1종', '국토안전관리원', '높이31m이상', '', '기본', '기본', '', '', '', '');

-- --------------------------------------------------------

--
-- 테이블 구조 `document_uploads`
--

CREATE TABLE `document_uploads` (
                                    `id` int(30) NOT NULL,
                                    `document_id` int(30) NOT NULL,
                                    `file_path` varchar(500) NOT NULL,
                                    `original_name` varchar(255) NOT NULL,
                                    `file_size` bigint(20) NOT NULL,
                                    `file_type` varchar(100) DEFAULT NULL,
                                    `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
                                    `uploaded_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `document_writers`
--

CREATE TABLE `document_writers` (
                                    `id` int(30) NOT NULL,
                                    `request_id` int(30) NOT NULL,
                                    `main_writer` varchar(100) DEFAULT NULL,
                                    `revenue_manager` varchar(100) DEFAULT NULL,
                                    `field_writers` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_writers`
--

INSERT INTO `document_writers` (`id`, `request_id`, `main_writer`, `revenue_manager`, `field_writers`) VALUES
    (10, 10, '', '', '');

-- --------------------------------------------------------

--
-- 테이블 구조 `email_logs`
--

CREATE TABLE `email_logs` (
                              `id` int(30) NOT NULL,
                              `request_id` int(30) NOT NULL COMMENT '요청 ID',
                              `recipient_email` varchar(255) NOT NULL COMMENT '수신자 이메일',
                              `subject` varchar(500) NOT NULL COMMENT '제목',
                              `content` longtext NOT NULL COMMENT '내용',
                              `status` enum('pending','sent','failed') DEFAULT 'pending' COMMENT '발송 상태',
                              `sent_at` datetime DEFAULT NULL COMMENT '발송 시간',
                              `error_message` text DEFAULT NULL COMMENT '오류 메시지',
                              `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `email_templates`
--

CREATE TABLE `email_templates` (
                                   `id` int(30) NOT NULL,
                                   `template_name` varchar(100) NOT NULL COMMENT '템플릿명',
                                   `template_type` enum('request_notification','reminder','completion','rejection') NOT NULL COMMENT '템플릿 타입',
                                   `subject` varchar(500) NOT NULL COMMENT '제목 템플릿',
                                   `content` longtext NOT NULL COMMENT '내용 템플릿',
                                   `variables` text DEFAULT NULL COMMENT '사용 가능한 변수 (JSON)',
                                   `is_html` tinyint(1) DEFAULT 1 COMMENT 'HTML 형식 여부',
                                   `is_default` tinyint(1) DEFAULT 0 COMMENT '기본 템플릿 여부',
                                   `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                   `date_created` datetime DEFAULT current_timestamp(),
                                   `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_name`, `template_type`, `subject`, `content`, `variables`, `is_html`, `is_default`, `status`, `date_created`, `date_updated`) VALUES
                                                                                                                                                                                 (1, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '<div style=\"max-width: 600px; margin: 0px auto; padding: 20px;\">\r\n    <h2 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\">서류 제출 요청</h2><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><br></p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">안녕하세요, {{contact_person}}님</p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">{{company_name}}에서 {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.</p>\r\n    \r\n    <div style=\"background-color: rgb(248, 249, 250); padding: 20px; margin: 20px 0px; border-radius: 5px;\">\r\n        <h3 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">프로젝트 정보</h3>\r\n        <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\"><strong>프로젝트명:</strong> {{project_name}}</p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\"><br></p><h3 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; color: rgb(52, 58, 64); letter-spacing: -0.14px;\">필수서류</h3><p style=\"\"><font face=\"Noto Sans KR, sans-serif\">{{document_list}}</font></p></div><div style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; margin: 20px 0px;\"><br></div>\r\n    \r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\">문의사항이 있으시면 회신 부탁드립니다.</p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\">감사합니다.</p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><br></p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><span style=\"letter-spacing: -0.14px;\">{{upload_link}}</span></p>\r\n</div>                        ', NULL, 1, 0, 1, '2025-08-01 17:58:44', '2025-08-01 17:59:35'),
                                                                                                                                                                                 (2, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '<div style=\"max-width: 600px; margin: 0px auto; padding: 20px;\">\r\n    <h2 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><span style=\"font-size: 36px;\"><b>서류 제출 요청</b></span></h2><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><br></p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">안녕하세요, {{contact_person}}님</p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">{{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.</p>\r\n    \r\n    <div style=\"background-color: rgb(248, 249, 250); padding: 20px; margin: 20px 0px; border-radius: 5px;\">\r\n        <h3 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\">프로젝트 정보</h3>\r\n        <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\"><strong>프로젝트명:</strong> {{project_name}}</p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif;\"><br></p><h3 style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; color: rgb(52, 58, 64); letter-spacing: -0.14px;\">필수서류</h3><p style=\"\"><font face=\"Noto Sans KR, sans-serif\">{{document_list}}</font></p></div><div style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; margin: 20px 0px;\"><br></div>\r\n    \r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\">문의사항이 있으시면 회신 부탁드립니다.</p>\r\n    <p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\">감사합니다.</p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><br></p><p style=\"font-family: &quot;Noto Sans KR&quot;, sans-serif; text-align: center;\"><span style=\"letter-spacing: -0.14px;\">{{upload_link}}</span></p>\r\n</div>                        ', NULL, 1, 0, 1, '2025-08-01 17:59:35', '2025-08-01 18:00:27'),
                                                                                                                                                                                 (3, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '<div style=\"max-width: 600px; margin: 0px auto; padding: 20px;\">\r\n    <h2 style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\"><span style=\"font-size: 36px;\"><b>서류 제출 요청</b></span></h2><p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\">{{company_name}}<br></p>\r\n    <p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;\"=\"\">안녕하세요, {{supplier_name}} {{contact_person}}님</p>\r\n    <p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;\"=\"\">{{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.</p>\r\n    \r\n    <div style=\"background-color: rgb(248, 249, 250); padding: 20px; margin: 20px 0px; border-radius: 5px;\">\r\n        <h3 style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;\"=\"\">프로젝트 정보</h3>\r\n        <p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;\"=\"\"><strong>프로젝트명:</strong> {{project_name}}</p><p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;\"=\"\"><br></p><h3 style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" color:=\"\" rgb(52,=\"\" 58,=\"\" 64);=\"\" letter-spacing:=\"\" -0.14px;\"=\"\">요청 서류 목록</h3><p style=\"\">{{document_list}}<font face=\"Noto Sans KR, sans-serif\"></font></p><p style=\"\"><font face=\"Noto Sans KR, sans-serif\"><br></font></p><p style=\"\"><font face=\"Noto Sans KR, sans-serif\"><br></font></p></div><div style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" margin:=\"\" 20px=\"\" 0px;\"=\"\"><br></div>\r\n    \r\n    <p style=\"text-align: center; \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\">문의사항이 있으시면 회신 부탁드립니다.</p>\r\n    <p style=\"text-align: center; \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\">감사합니다.</p><p style=\"font-family: \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\"><br></p><p style=\"text-align: center; \" noto=\"\" sans=\"\" kr\",=\"\" sans-serif;=\"\" text-align:=\"\" center;\"=\"\"><span style=\"letter-spacing: -0.14px;\">{{upload_link}}</span></p>\r\n</div>                                                                                                                                                                                                                        ', NULL, 1, 1, 1, '2025-08-01 18:00:27', '2025-08-04 10:06:44');

-- --------------------------------------------------------

--
-- 테이블 구조 `form_fields`
--

CREATE TABLE `form_fields` (
                               `id` int(30) NOT NULL,
                               `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                               `field_name` varchar(100) NOT NULL COMMENT '필드명',
                               `field_label` varchar(255) NOT NULL COMMENT '필드 라벨',
                               `field_type` enum('text','number','date','checkbox','radio','select','textarea','signature') NOT NULL COMMENT '필드 타입',
                               `field_options` text DEFAULT NULL COMMENT '선택 옵션 (JSON)',
                               `is_required` tinyint(1) DEFAULT 0 COMMENT '필수 입력 여부',
                               `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
                               `validation_rules` varchar(500) DEFAULT NULL COMMENT '유효성 검사 규칙',
                               `default_value` varchar(255) DEFAULT NULL COMMENT '기본값',
                               `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                               `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `form_fields`
--

INSERT INTO `form_fields` (`id`, `category_id`, `field_name`, `field_label`, `field_type`, `field_options`, `is_required`, `display_order`, `validation_rules`, `default_value`, `status`, `date_created`) VALUES
                                                                                                                                                                                                               (80, 1, 'management_no', '관리번호', 'text', NULL, 1, 1, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (81, 1, 'requester', '의뢰처', 'text', NULL, 1, 2, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (82, 1, 'project_name', '공사명', 'text', NULL, 1, 3, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (83, 1, 'client_name', '발주자', 'text', NULL, 1, 4, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (84, 1, 'order_date', '수주일', 'date', NULL, 1, 5, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (85, 1, 'start_date', '착공일', 'date', NULL, 0, 6, NULL, NULL, 1, '2025-07-31 18:52:23'),
                                                                                                                                                                                                               (86, 1, 'signature', '작성자 서명', 'signature', NULL, 1, 7, NULL, NULL, 1, '2025-07-31 18:52:23');

-- --------------------------------------------------------

--
-- 테이블 구조 `form_templates`
--

CREATE TABLE `form_templates` (
                                  `id` int(30) NOT NULL,
                                  `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                                  `template_name` varchar(255) NOT NULL COMMENT '템플릿명',
                                  `template_html` longtext NOT NULL COMMENT 'HTML 템플릿',
                                  `template_css` text DEFAULT NULL COMMENT 'CSS 스타일',
                                  `template_js` text DEFAULT NULL COMMENT 'JavaScript 코드',
                                  `version` varchar(20) DEFAULT '1.0' COMMENT '템플릿 버전',
                                  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                  `created_by` int(30) NOT NULL COMMENT '생성자',
                                  `date_created` datetime DEFAULT current_timestamp(),
                                  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `item_list`
--

CREATE TABLE `item_list` (
                             `id` int(30) NOT NULL,
                             `name` varchar(250) NOT NULL,
                             `description` text NOT NULL,
                             `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT ' 1 = Active, 0 = Inactive',
                             `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `item_list`
--

INSERT INTO `item_list` (`id`, `name`, `description`, `status`, `date_created`) VALUES
                                                                                    (1, 'Item 1', 'Sample Item Only. Test 101', 1, '2021-09-08 10:17:19'),
                                                                                    (2, 'Item 102', 'Sample Only', 1, '2021-09-08 10:21:42'),
                                                                                    (3, 'Item 3', 'Sample product 103. 3x25 per boxes', 1, '2021-09-08 10:22:10');

-- --------------------------------------------------------

--
-- 테이블 구조 `notifications`
--

CREATE TABLE `notifications` (
                                 `id` int(30) NOT NULL,
                                 `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                 `type` varchar(50) NOT NULL COMMENT '알림 타입',
                                 `title` varchar(255) NOT NULL COMMENT '제목',
                                 `message` text NOT NULL COMMENT '내용',
                                 `is_read` tinyint(1) DEFAULT 0 COMMENT '읽음 여부',
                                 `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `notifications`
--

INSERT INTO `notifications` (`id`, `request_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
    (1, 9, 'completion', '서류 제출 완료', '(주)한국에서 모든 서류를 제출했습니다.', 0, '2025-08-05 10:48:20');

-- --------------------------------------------------------

--
-- 테이블 구조 `order_items`
--

CREATE TABLE `order_items` (
                               `po_id` int(30) NOT NULL,
                               `item_id` int(11) NOT NULL,
                               `unit` varchar(50) NOT NULL,
                               `unit_price` float NOT NULL,
                               `quantity` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `po_list`
--

CREATE TABLE `po_list` (
                           `id` int(30) NOT NULL,
                           `po_no` varchar(100) NOT NULL,
                           `supplier_id` int(30) NOT NULL,
                           `discount_percentage` float NOT NULL,
                           `discount_amount` float NOT NULL,
                           `tax_percentage` float NOT NULL,
                           `tax_amount` float NOT NULL,
                           `notes` text NOT NULL,
                           `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1= Approved, 2 = Denied',
                           `date_created` datetime NOT NULL DEFAULT current_timestamp(),
                           `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `request_documents`
--

CREATE TABLE `request_documents` (
                                     `id` int(30) NOT NULL,
                                     `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                     `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                                     `document_name` varchar(255) NOT NULL COMMENT '서류명',
                                     `is_required` tinyint(1) DEFAULT 0 COMMENT '필수 여부',
                                     `status` tinyint(1) DEFAULT 0 COMMENT '제출 상태 (0=미제출, 1=제출완료)',
                                     `file_name` varchar(255) DEFAULT NULL COMMENT '업로드된 파일명',
                                     `file_path` varchar(500) DEFAULT NULL COMMENT '파일 경로',
                                     `file_size` int(11) DEFAULT NULL COMMENT '파일 크기',
                                     `upload_method` enum('file_upload','web_form') DEFAULT 'file_upload' COMMENT '제출 방식',
                                     `form_data` longtext DEFAULT NULL COMMENT '웹폼 작성 데이터 (JSON)',
                                     `uploaded_at` datetime DEFAULT NULL COMMENT '업로드 시간',
                                     `date_created` datetime DEFAULT current_timestamp(),
                                     `file_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `request_documents`
--

INSERT INTO `request_documents` (`id`, `request_id`, `category_id`, `document_name`, `is_required`, `status`, `file_name`, `file_path`, `file_size`, `upload_method`, `form_data`, `uploaded_at`, `date_created`, `file_id`) VALUES
                                                                                                                                                                                                                                 (19, 10, 39, 'C', 1, 1, 'REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', 'documents/2025/08/REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', 93926, 'file_upload', NULL, '2025-08-05 11:05:30', '2025-08-05 10:51:20', NULL),
                                                                                                                                                                                                                                 (20, 10, 41, 'D', 1, 1, 'REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', 'documents/2025/08/REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', 27935, 'file_upload', NULL, '2025-08-05 10:56:19', '2025-08-05 10:51:20', NULL),
                                                                                                                                                                                                                                 (21, 10, 28, '크레인작업방지계획서', 1, 1, 'REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', 'documents/2025/08/REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', 268424, 'file_upload', NULL, '2025-08-05 10:52:20', '2025-08-05 10:51:20', NULL),
                                                                                                                                                                                                                                 (22, 10, 29, '안전보건대장 기본양식', 1, 1, 'REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', 'documents/2025/08/REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', 1338539, 'file_upload', NULL, '2025-08-05 11:12:26', '2025-08-05 10:51:20', NULL),
                                                                                                                                                                                                                                 (23, 10, 30, '작업일지', 1, 1, 'REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', 'documents/2025/08/REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', 27935, 'file_upload', NULL, '2025-08-05 10:52:46', '2025-08-05 10:51:20', NULL),
                                                                                                                                                                                                                                 (24, 10, 31, '안전교육 기록', 1, 1, 'REQ10_DOC24_에시화면_1754360996.docx', 'documents/2025/08/REQ10_DOC24_에시화면_1754360996.docx', 769699, 'file_upload', NULL, '2025-08-05 11:29:57', '2025-08-05 10:51:20', NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `review_credentials`
--

CREATE TABLE `review_credentials` (
                                      `id` int(30) NOT NULL,
                                      `request_id` int(30) NOT NULL,
                                      `csi_id` varchar(100) DEFAULT NULL,
                                      `csi_password` varchar(255) DEFAULT NULL,
                                      `csi_supervisor` varchar(100) DEFAULT NULL,
                                      `csi_supervisor_info` varchar(100) DEFAULT NULL,
                                      `csi_client` varchar(100) DEFAULT NULL,
                                      `csi_client_info` varchar(100) DEFAULT NULL,
                                      `kosha_id` varchar(100) DEFAULT NULL,
                                      `kosha_password` varchar(255) DEFAULT NULL,
                                      `kosha_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `review_organizations`
--

CREATE TABLE `review_organizations` (
                                        `id` int(30) NOT NULL,
                                        `org_name` varchar(255) NOT NULL COMMENT '기관명',
                                        `org_code` varchar(50) NOT NULL COMMENT '기관 코드',
                                        `contact_person` varchar(100) DEFAULT NULL COMMENT '담당자',
                                        `phone` varchar(50) DEFAULT NULL COMMENT '전화번호',
                                        `email` varchar(255) DEFAULT NULL COMMENT '이메일',
                                        `address` text DEFAULT NULL COMMENT '주소',
                                        `specialization` text DEFAULT NULL COMMENT '전문 분야',
                                        `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                        `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `review_organizations`
--

INSERT INTO `review_organizations` (`id`, `org_name`, `org_code`, `contact_person`, `phone`, `email`, `address`, `specialization`, `status`, `date_created`) VALUES
                                                                                                                                                                 (1, '중부제해예방관리원(주)', 'CSI', '관리자', NULL, 'info@csi.co.kr', NULL, NULL, 1, '2025-07-31 18:20:34'),
                                                                                                                                                                 (2, '안전보건공단', 'KOSHA', '담당자', NULL, 'contact@kosha.or.kr', NULL, NULL, 1, '2025-07-31 18:20:34');

-- --------------------------------------------------------

--
-- 테이블 구조 `supplier_list`
--

CREATE TABLE `supplier_list` (
                                 `id` int(30) NOT NULL,
                                 `name` varchar(250) NOT NULL,
                                 `address` text NOT NULL,
                                 `contact_person` text NOT NULL,
                                 `contact` varchar(50) NOT NULL,
                                 `email` varchar(150) NOT NULL,
                                 `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT ' 0 = Inactive, 1 = Active',
                                 `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `supplier_list`
--

INSERT INTO `supplier_list` (`id`, `name`, `address`, `contact_person`, `contact`, `email`, `status`, `date_created`) VALUES
    (3, '(주)한국', '용인시 처인구 이원로 601번길 84', 'ABC', '01025304351', 'gangdongmyung@gmail.com', 1, '2025-08-01 09:55:18');

-- --------------------------------------------------------

--
-- 테이블 구조 `system_info`
--

CREATE TABLE `system_info` (
                               `id` int(30) NOT NULL,
                               `meta_field` text NOT NULL,
                               `meta_value` text NOT NULL,
                               `smtp_host` varchar(255) DEFAULT NULL COMMENT 'SMTP 호스트',
                               `smtp_username` varchar(255) DEFAULT NULL COMMENT 'SMTP 사용자명',
                               `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP 비밀번호',
                               `smtp_port` int(11) DEFAULT 587 COMMENT 'SMTP 포트',
                               `smtp_secure` varchar(10) DEFAULT 'tls' COMMENT 'SMTP 보안 (tls/ssl)',
                               `smtp_from_email` varchar(255) DEFAULT NULL COMMENT '발신 이메일',
                               `smtp_from_name` varchar(255) DEFAULT NULL COMMENT '발신자명'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`, `smtp_host`, `smtp_username`, `smtp_password`, `smtp_port`, `smtp_secure`, `smtp_from_email`, `smtp_from_name`) VALUES
                                                                                                                                                                                 (1, 'name', '서류 관리 시스템', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (6, 'short_name', 'CDMS', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (11, 'logo', 'uploads/1754353532_68914f7c42536.png', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (13, 'user_avatar', 'uploads/user_avatar.jpg', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (14, 'cover', 'uploads/1631064360_sample_bg.jpg', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (15, 'company_name', '중부재해예방관리원', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (16, 'company_email', 'info@sampleco.com', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (17, 'company_address', 'Sample Address, 23rd St., Sample City, ####', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (18, 'smtp_host', 'sandbox.smtp.mailtrap.io', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (19, 'smtp_port', '587', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (20, 'smtp_username', 'de6ab441cf73d7', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (21, 'smtp_password', '77fe1a9732a623', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (22, 'smtp_secure', 'tls', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (23, 'smtp_from_name', '(주)중부재해예방관리원', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (24, 'smtp_from_email', 'gangdongmyung@naver.com', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (25, 'use_wasabi', 'true', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (26, 'wasabi_key', 'K7SYIGN2O65MA6OT5IW3', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (27, 'wasabi_secret', '/zuRB0zpToKmpBvK3U7EtE1kU01RbFF0VDQ2TTE0RjV3M1FUcHYyYjRoUXdYRDI3WGFFbWo3NG52TGtyUVdJaEdXNzM3M3N4YXBiTUlkZ0Q=', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (28, 'wasabi_bucket', 'jungbu-safety-docs', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (29, 'wasabi_region', 'ap-northeast-1', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (30, 'wasabi_endpoint', 'https://s3.ap-northeast-1.wasabisys.com', NULL, NULL, NULL, 587, 'tls', NULL, NULL),
                                                                                                                                                                                 (31, 'encryption_key', 'your-32-character-encryption-key-here', NULL, NULL, NULL, 587, 'tls', NULL, NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `uploaded_files`
--

CREATE TABLE `uploaded_files` (
                                  `id` int(11) NOT NULL,
                                  `request_id` int(11) NOT NULL,
                                  `document_id` int(11) NOT NULL,
                                  `document_name` varchar(255) DEFAULT NULL,
                                  `project_id` int(11) DEFAULT NULL,
                                  `original_name` varchar(255) NOT NULL,
                                  `stored_name` varchar(255) NOT NULL,
                                  `wasabi_key` text NOT NULL,
                                  `wasabi_bucket` varchar(100) DEFAULT NULL,
                                  `wasabi_region` varchar(50) DEFAULT 'ap-northeast-1',
                                  `wasabi_url` text DEFAULT NULL,
                                  `file_size` bigint(20) NOT NULL,
                                  `mime_type` varchar(100) DEFAULT NULL,
                                  `checksum` varchar(64) DEFAULT NULL,
                                  `uploaded_by` varchar(100) DEFAULT NULL,
                                  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
                                  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `uploaded_files`
--

INSERT INTO `uploaded_files` (`id`, `request_id`, `document_id`, `document_name`, `project_id`, `original_name`, `stored_name`, `wasabi_key`, `wasabi_bucket`, `wasabi_region`, `wasabi_url`, `file_size`, `mime_type`, `checksum`, `uploaded_by`, `uploaded_at`, `is_deleted`, `metadata`) VALUES
                                                                                                                                                                                                                                                                                                (15, 10, 21, '크레인작업방지계획서', NULL, 'text_logo_on_white (1) (1).png', 'REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', 'documents/2025/08/REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', 268424, 'image/png', NULL, 'admin', '2025-08-05 01:52:20', 0, NULL),
                                                                                                                                                                                                                                                                                                (16, 10, 23, '작업일지', NULL, '보안 체크리스트 문서.docx', 'REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', 'documents/2025/08/REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC23_%EB%B3%B4%EC%95%88_%EC%B2%B4%ED%81%AC%EB%A6%AC%EC%8A%A4%ED%8A%B8_%EB%AC%B8%EC%84%9C_1754358766.docx', 27935, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, 'admin', '2025-08-05 01:52:46', 0, NULL),
                                                                                                                                                                                                                                                                                                (17, 10, 20, 'D', NULL, '보안 체크리스트 문서.docx', 'REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', 'documents/2025/08/REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC20_%EB%B3%B4%EC%95%88_%EC%B2%B4%ED%81%AC%EB%A6%AC%EC%8A%A4%ED%8A%B8_%EB%AC%B8%EC%84%9C_1754358979.docx', 27935, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, 'admin', '2025-08-05 01:56:19', 0, NULL),
                                                                                                                                                                                                                                                                                                (18, 10, 19, 'C', NULL, 'KakaoTalk_20250502_094837196.png', 'REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', 'documents/2025/08/REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', 93926, 'image/png', NULL, 'admin', '2025-08-05 02:05:30', 0, NULL),
                                                                                                                                                                                                                                                                                                (19, 10, 22, '안전보건대장 기본양식', NULL, 'QR 안전교육 앱 - Java 웹 하이브리드 전환 기술 문서.docx', 'REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', 'documents/2025/08/REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC22_QR_%EC%95%88%EC%A0%84%EA%B5%90%EC%9C%A1_%EC%95%B1_-_Java_%EC%9B%B9_%ED%95%98%EC%9D%B4%EB%B8%8C%EB%A6%AC%EB%93%9C_%EC%A0%84_1754359944.docx', 1338539, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, 'admin', '2025-08-05 02:12:26', 0, NULL),
                                                                                                                                                                                                                                                                                                (20, 10, 24, '안전교육 기록', NULL, '에시화면.docx', 'REQ10_DOC24_에시화면_1754360996.docx', 'documents/2025/08/REQ10_DOC24_에시화면_1754360996.docx', 'jungbu-safety-docs', 'ap-northeast-1', 'https://s3.ap-northeast-1.wasabisys.com/jungbu-safety-docs/documents/2025/08/REQ10_DOC24_%EC%97%90%EC%8B%9C%ED%99%94%EB%A9%B4_1754360996.docx', 769699, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, 'admin', '2025-08-05 02:29:57', 0, NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `upload_logs`
--

CREATE TABLE `upload_logs` (
                               `id` int(30) NOT NULL,
                               `request_id` int(30) NOT NULL COMMENT '요청 ID',
                               `document_id` int(30) DEFAULT NULL COMMENT '서류 ID',
                               `action` varchar(50) NOT NULL COMMENT '액션 (upload, delete, replace, form_submit)',
                               `file_name` varchar(255) DEFAULT NULL COMMENT '파일명',
                               `file_size` int(11) DEFAULT NULL COMMENT '파일 크기',
                               `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
                               `user_agent` text DEFAULT NULL COMMENT '브라우저 정보',
                               `details` text DEFAULT NULL COMMENT '상세 정보',
                               `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `upload_logs`
--

INSERT INTO `upload_logs` (`id`, `request_id`, `document_id`, `action`, `file_name`, `file_size`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
                                                                                                                                                           (1, 4, 7, 'upload', 'REQ4_DOC7_보안_체크리스트_문서_1754281347.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:22:27'),
                                                                                                                                                           (2, 4, 7, 'delete', 'REQ4_DOC7_보안_체크리스트_문서_1754281347.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:22:31'),
                                                                                                                                                           (3, 4, 7, 'upload', 'REQ4_DOC7_보안_체크리스트_문서_1754281356.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:22:36'),
                                                                                                                                                           (4, 5, 8, 'upload', 'REQ5_DOC8_보안_체크리스트_문서_1754281698.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:28:18'),
                                                                                                                                                           (5, 5, 9, 'upload', 'REQ5_DOC9_에시화면_1754281702.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:28:22'),
                                                                                                                                                           (6, 5, 8, 'delete', 'REQ5_DOC8_보안_체크리스트_문서_1754281698.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:55:09'),
                                                                                                                                                           (7, 5, 9, 'delete', 'REQ5_DOC9_에시화면_1754281702.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 13:55:12'),
                                                                                                                                                           (8, 5, 8, 'upload', 'REQ5_DOC8_보안_체크리스트_문서_1754283787.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 14:03:07'),
                                                                                                                                                           (9, 5, 9, 'upload', 'REQ5_DOC9_에시화면_1754286483.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 14:48:03'),
                                                                                                                                                           (10, 6, 10, 'upload', 'REQ6_DOC10_보안_체크리스트_문서_1754290865.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 16:01:06'),
                                                                                                                                                           (11, 6, 11, 'upload', 'REQ6_DOC11_에시화면_1754290870.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 16:01:11'),
                                                                                                                                                           (12, 6, 10, 'delete', 'REQ6_DOC10_보안_체크리스트_문서_1754290865.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 16:11:58'),
                                                                                                                                                           (13, 6, 10, 'upload', 'REQ6_DOC10_2024년_산업재해현황_홈페이지__1754291533.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 16:12:14'),
                                                                                                                                                           (14, 6, 11, 'delete', 'REQ6_DOC11_에시화면_1754290870.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 17:19:57'),
                                                                                                                                                           (15, 6, 10, 'delete', 'REQ6_DOC10_2024년_산업재해현황_홈페이지__1754291533.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 17:20:00'),
                                                                                                                                                           (16, 6, 10, 'upload', 'REQ6_DOC10_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754295701.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 17:21:41'),
                                                                                                                                                           (17, 6, 10, 'delete', 'REQ6_DOC10_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754295701.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 17:25:11'),
                                                                                                                                                           (18, 6, 10, 'upload', 'REQ6_DOC10_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754295920.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 17:25:20'),
                                                                                                                                                           (19, 6, 11, 'upload', 'REQ6_DOC11_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754298345.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:05:47'),
                                                                                                                                                           (20, 7, 12, 'upload', 'REQ7_DOC12_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300742.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:45:43'),
                                                                                                                                                           (21, 7, 13, 'upload', 'REQ7_DOC13_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300749.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:45:49'),
                                                                                                                                                           (22, 8, 14, 'upload', 'REQ8_DOC14_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300979.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:49:41'),
                                                                                                                                                           (23, 8, 15, 'upload', 'REQ8_DOC15_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300987.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:49:47'),
                                                                                                                                                           (24, 8, 16, 'upload', 'REQ8_DOC16_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300993.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 18:49:54'),
                                                                                                                                                           (25, 8, 14, 'delete', 'REQ8_DOC14_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300979.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 19:03:17'),
                                                                                                                                                           (26, 8, 15, 'delete', 'REQ8_DOC15_REQ6_DOC10_2024년_산업재해현황_홈페이지_1754300987.hwp', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-04 19:03:24'),
                                                                                                                                                           (27, 9, 17, 'upload', 'REQ9_DOC17_text_logo_on_white__1___1__1754357333.png', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:28:53'),
                                                                                                                                                           (28, 9, 18, 'upload', 'REQ9_DOC18_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754357897.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:38:18'),
                                                                                                                                                           (29, 9, 17, 'delete', 'REQ9_DOC17_text_logo_on_white__1___1__1754357333.png', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:38:32'),
                                                                                                                                                           (30, 9, 17, 'upload', 'REQ9_DOC17_text_logo_on_white__1___1__1754357917.png', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:38:38'),
                                                                                                                                                           (31, 9, 0, 'complete_submission', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:48:20'),
                                                                                                                                                           (32, 10, 21, 'upload', 'REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:52:20'),
                                                                                                                                                           (33, 10, 23, 'upload', 'REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:52:46'),
                                                                                                                                                           (34, 10, 20, 'upload', 'REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 10:56:19'),
                                                                                                                                                           (35, 10, 19, 'upload', 'REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 11:05:30'),
                                                                                                                                                           (36, 10, 22, 'upload', 'REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 11:12:26'),
                                                                                                                                                           (37, 10, 24, 'upload', 'REQ10_DOC24_에시화면_1754360996.docx', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL, '2025-08-05 11:29:57');

-- --------------------------------------------------------

--
-- 테이블 구조 `upload_notifications`
--

CREATE TABLE `upload_notifications` (
                                        `id` int(11) NOT NULL,
                                        `request_id` int(11) NOT NULL COMMENT '문서 요청 ID',
                                        `document_id` int(11) NOT NULL COMMENT '문서 ID (request_documents.id)',
                                        `supplier_id` int(11) DEFAULT NULL COMMENT '공급업체 ID',
                                        `supplier_name` varchar(255) NOT NULL COMMENT '공급업체명',
                                        `document_name` varchar(255) NOT NULL COMMENT '문서명',
                                        `file_name` varchar(255) DEFAULT NULL COMMENT '업로드된 파일명',
                                        `uploaded_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '업로드 시간',
                                        `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '읽음 여부',
                                        `read_by` int(11) DEFAULT NULL COMMENT '읽은 사용자 ID',
                                        `read_at` datetime DEFAULT NULL COMMENT '읽은 시간'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `upload_notifications`
--

INSERT INTO `upload_notifications` (`id`, `request_id`, `document_id`, `supplier_id`, `supplier_name`, `document_name`, `file_name`, `uploaded_at`, `is_read`, `read_by`, `read_at`) VALUES
                                                                                                                                                                                         (21, 10, 21, 3, '(주)한국', '크레인작업방지계획서', 'REQ10_DOC21_text_logo_on_white__1___1__1754358739.png', '2025-08-05 10:52:20', 0, NULL, NULL),
                                                                                                                                                                                         (22, 10, 23, 3, '(주)한국', '작업일지', 'REQ10_DOC23_보안_체크리스트_문서_1754358766.docx', '2025-08-05 10:52:46', 0, NULL, NULL),
                                                                                                                                                                                         (23, 10, 20, 3, '(주)한국', 'D', 'REQ10_DOC20_보안_체크리스트_문서_1754358979.docx', '2025-08-05 10:56:19', 0, NULL, NULL),
                                                                                                                                                                                         (24, 10, 19, 3, '(주)한국', 'C', 'REQ10_DOC19_KakaoTalk_20250502_094837196_1754359530.png', '2025-08-05 11:05:30', 1, 1, '2025-08-05 11:11:27'),
                                                                                                                                                                                         (25, 10, 22, 3, '(주)한국', '안전보건대장 기본양식', 'REQ10_DOC22_QR_안전교육_앱_-_Java_웹_하이브리드_전_1754359944.docx', '2025-08-05 11:12:26', 0, NULL, NULL),
                                                                                                                                                                                         (26, 10, 24, 3, '(주)한국', '안전교육 기록', 'REQ10_DOC24_에시화면_1754360996.docx', '2025-08-05 11:29:57', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `users`
--

CREATE TABLE `users` (
                         `id` int(50) NOT NULL,
                         `firstname` varchar(250) NOT NULL,
                         `lastname` varchar(250) NOT NULL,
                         `username` text NOT NULL,
                         `password` text NOT NULL,
                         `avatar` text DEFAULT NULL,
                         `last_login` datetime DEFAULT NULL,
                         `type` tinyint(1) NOT NULL DEFAULT 0,
                         `date_added` datetime NOT NULL DEFAULT current_timestamp(),
                         `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `password`, `avatar`, `last_login`, `type`, `date_added`, `date_updated`) VALUES
                                                                                                                                              (1, 'Adminstrator', 'Admin', 'admin', '0192023a7bbd73250516f069df18b500', 'uploads/1624240500_avatar.png', NULL, 1, '2021-01-20 14:02:37', '2021-06-21 09:55:07'),
                                                                                                                                              (3, 'Mike ', 'Williams', 'mwilliams', 'a88df23ac492e6e2782df6586a0c645f', 'uploads/1630999200_avatar5.png', NULL, 2, '2021-09-07 15:20:40', NULL),
                                                                                                                                              (5, '강', '공명', 'user', '6ad14ba9986e3615423dfca256d04e3f', NULL, NULL, 2, '2025-07-31 16:38:27', NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `workflow_status`
--

CREATE TABLE `workflow_status` (
                                   `id` int(30) NOT NULL,
                                   `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                   `current_step` enum('created','documents_requested','in_progress','under_review','completed','rejected') NOT NULL COMMENT '현재 단계',
                                   `step_name` varchar(100) NOT NULL COMMENT '단계명',
                                   `step_description` text DEFAULT NULL COMMENT '단계 설명',
                                   `started_at` datetime DEFAULT NULL COMMENT '시작 시간',
                                   `completed_at` datetime DEFAULT NULL COMMENT '완료 시간',
                                   `assigned_to` int(30) DEFAULT NULL COMMENT '담당자 ID',
                                   `notes` text DEFAULT NULL COMMENT '진행 노트',
                                   `is_current` tinyint(1) DEFAULT 0 COMMENT '현재 단계 여부',
                                   `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `workflow_status`
--

INSERT INTO `workflow_status` (`id`, `request_id`, `current_step`, `step_name`, `step_description`, `started_at`, `completed_at`, `assigned_to`, `notes`, `is_current`, `created_at`) VALUES
                                                                                                                                                                                          (17, 10, 'created', '요청 생성', '서류 요청이 생성되었습니다.', '2025-08-05 10:51:20', NULL, 1, NULL, 0, '2025-08-05 10:51:20'),
                                                                                                                                                                                          (18, 10, '', '진행중', '첫 번째 서류가 업로드되어 진행중 상태로 변경되었습니다.', '2025-08-05 10:52:20', NULL, NULL, NULL, 1, '2025-08-05 10:52:20');

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `access_tokens`
--
ALTER TABLE `access_tokens`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `document_categories`
--
ALTER TABLE `document_categories`
    ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- 테이블의 인덱스 `document_costs`
--
ALTER TABLE `document_costs`
    ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- 테이블의 인덱스 `document_cost_details`
--
ALTER TABLE `document_cost_details`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `document_requests`
--
ALTER TABLE `document_requests`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD UNIQUE KEY `upload_token` (`upload_token`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `document_requests_ibfk_2` (`created_by`),
  ADD KEY `idx_upload_token` (`upload_token`);

--
-- 테이블의 인덱스 `document_request_details`
--
ALTER TABLE `document_request_details`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `document_targets`
--
ALTER TABLE `document_targets`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `document_uploads`
--
ALTER TABLE `document_uploads`
    ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- 테이블의 인덱스 `document_writers`
--
ALTER TABLE `document_writers`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `email_logs`
--
ALTER TABLE `email_logs`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `email_templates`
--
ALTER TABLE `email_templates`
    ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `form_fields`
--
ALTER TABLE `form_fields`
    ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- 테이블의 인덱스 `form_templates`
--
ALTER TABLE `form_templates`
    ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `form_templates_ibfk_2` (`created_by`);

--
-- 테이블의 인덱스 `item_list`
--
ALTER TABLE `item_list`
    ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `notifications`
--
ALTER TABLE `notifications`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `order_items`
--
ALTER TABLE `order_items`
    ADD KEY `po_id` (`po_id`),
  ADD KEY `item_no` (`item_id`);

--
-- 테이블의 인덱스 `po_list`
--
ALTER TABLE `po_list`
    ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- 테이블의 인덱스 `request_documents`
--
ALTER TABLE `request_documents`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_document_file` (`file_id`);

--
-- 테이블의 인덱스 `review_credentials`
--
ALTER TABLE `review_credentials`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `review_organizations`
--
ALTER TABLE `review_organizations`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `org_code` (`org_code`);

--
-- 테이블의 인덱스 `supplier_list`
--
ALTER TABLE `supplier_list`
    ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `system_info`
--
ALTER TABLE `system_info`
    ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `uploaded_files`
--
ALTER TABLE `uploaded_files`
    ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_wasabi_key` (`wasabi_key`(255)),
  ADD KEY `idx_uploaded_at` (`uploaded_at`),
  ADD KEY `fk_uploaded_document` (`document_id`);

--
-- 테이블의 인덱스 `upload_logs`
--
ALTER TABLE `upload_logs`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- 테이블의 인덱스 `upload_notifications`
--
ALTER TABLE `upload_notifications`
    ADD PRIMARY KEY (`id`),
  ADD KEY `idx_upload_notif_request` (`request_id`),
  ADD KEY `idx_upload_notif_read` (`is_read`),
  ADD KEY `idx_upload_notif_time` (`uploaded_at`),
  ADD KEY `idx_upload_notif_unread` (`is_read`,`uploaded_at`),
  ADD KEY `fk_upload_notif_supplier` (`supplier_id`),
  ADD KEY `fk_upload_notif_user` (`read_by`);

--
-- 테이블의 인덱스 `users`
--
ALTER TABLE `users`
    ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `workflow_status`
--
ALTER TABLE `workflow_status`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `workflow_status_ibfk_2` (`assigned_to`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `access_tokens`
--
ALTER TABLE `access_tokens`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `document_categories`
--
ALTER TABLE `document_categories`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- 테이블의 AUTO_INCREMENT `document_costs`
--
ALTER TABLE `document_costs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- 테이블의 AUTO_INCREMENT `document_cost_details`
--
ALTER TABLE `document_cost_details`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 테이블의 AUTO_INCREMENT `document_requests`
--
ALTER TABLE `document_requests`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 테이블의 AUTO_INCREMENT `document_request_details`
--
ALTER TABLE `document_request_details`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 테이블의 AUTO_INCREMENT `document_targets`
--
ALTER TABLE `document_targets`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 테이블의 AUTO_INCREMENT `document_uploads`
--
ALTER TABLE `document_uploads`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `document_writers`
--
ALTER TABLE `document_writers`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 테이블의 AUTO_INCREMENT `email_logs`
--
ALTER TABLE `email_logs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `email_templates`
--
ALTER TABLE `email_templates`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `form_fields`
--
ALTER TABLE `form_fields`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- 테이블의 AUTO_INCREMENT `form_templates`
--
ALTER TABLE `form_templates`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `item_list`
--
ALTER TABLE `item_list`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `notifications`
--
ALTER TABLE `notifications`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 테이블의 AUTO_INCREMENT `po_list`
--
ALTER TABLE `po_list`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `request_documents`
--
ALTER TABLE `request_documents`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 테이블의 AUTO_INCREMENT `review_credentials`
--
ALTER TABLE `review_credentials`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `review_organizations`
--
ALTER TABLE `review_organizations`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `supplier_list`
--
ALTER TABLE `supplier_list`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `system_info`
--
ALTER TABLE `system_info`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- 테이블의 AUTO_INCREMENT `uploaded_files`
--
ALTER TABLE `uploaded_files`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 테이블의 AUTO_INCREMENT `upload_logs`
--
ALTER TABLE `upload_logs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- 테이블의 AUTO_INCREMENT `upload_notifications`
--
ALTER TABLE `upload_notifications`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
    MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 테이블의 AUTO_INCREMENT `workflow_status`
--
ALTER TABLE `workflow_status`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 덤프된 테이블의 제약사항
--

--
-- 테이블의 제약사항 `access_tokens`
--
ALTER TABLE `access_tokens`
    ADD CONSTRAINT `access_tokens_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_categories`
--
ALTER TABLE `document_categories`
    ADD CONSTRAINT `document_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `document_costs`
--
ALTER TABLE `document_costs`
    ADD CONSTRAINT `document_costs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_cost_details`
--
ALTER TABLE `document_cost_details`
    ADD CONSTRAINT `document_cost_details_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_requests`
--
ALTER TABLE `document_requests`
    ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `document_requests_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `document_request_details`
--
ALTER TABLE `document_request_details`
    ADD CONSTRAINT `document_request_details_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_targets`
--
ALTER TABLE `document_targets`
    ADD CONSTRAINT `document_targets_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_uploads`
--
ALTER TABLE `document_uploads`
    ADD CONSTRAINT `document_uploads_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `request_documents` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `document_writers`
--
ALTER TABLE `document_writers`
    ADD CONSTRAINT `document_writers_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `form_fields`
--
ALTER TABLE `form_fields`
    ADD CONSTRAINT `form_fields_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `form_templates`
--
ALTER TABLE `form_templates`
    ADD CONSTRAINT `form_templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form_templates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `order_items`
--
ALTER TABLE `order_items`
    ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`po_id`) REFERENCES `po_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `po_list`
--
ALTER TABLE `po_list`
    ADD CONSTRAINT `po_list_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `request_documents`
--
ALTER TABLE `request_documents`
    ADD CONSTRAINT `fk_document_file` FOREIGN KEY (`file_id`) REFERENCES `uploaded_files` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `request_documents_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- 테이블의 제약사항 `review_credentials`
--
ALTER TABLE `review_credentials`
    ADD CONSTRAINT `review_credentials_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `uploaded_files`
--
ALTER TABLE `uploaded_files`
    ADD CONSTRAINT `fk_uploaded_document` FOREIGN KEY (`document_id`) REFERENCES `request_documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uploaded_request` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `upload_notifications`
--
ALTER TABLE `upload_notifications`
    ADD CONSTRAINT `fk_upload_notif_request` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_upload_notif_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_list` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_upload_notif_user` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- 테이블의 제약사항 `workflow_status`
--
ALTER TABLE `workflow_status`
    ADD CONSTRAINT `workflow_status_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_status_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
