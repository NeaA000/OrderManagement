-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- 생성 시간: 25-07-31 12:49
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
                                       `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
                                       `is_required` tinyint(1) DEFAULT 0 COMMENT '필수 서류 여부',
                                       `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                       `description` text DEFAULT NULL,
                                       `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `document_categories`
--

INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`, `description`, `date_created`) VALUES
                                                                                                                                                    (1, NULL, '안전관리계획서', 1, 1, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (2, NULL, '유해위험방지계획서', 1, 2, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (3, NULL, '교육시설안전성평가', 1, 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (4, NULL, '철도보호지구안전성평가', 1, 4, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (5, NULL, '소규모안전관리계획서', 1, 5, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (6, NULL, '기본', 1, 6, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (7, NULL, '설계', 1, 7, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (8, NULL, '공사안전보건대장', 1, 8, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (9, NULL, '적정성평가', 1, 9, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (10, NULL, '기타', 1, 10, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (25, 1, '안전점검계획서', 2, 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (26, 2, '굴착작업방지계획서', 2, 1, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (27, 2, '고소작업방지계획서', 2, 2, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (28, 2, '크레인작업방지계획서', 2, 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (29, 8, '안전보건대장 기본양식', 2, 1, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (30, 8, '작업일지', 2, 2, 1, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (31, 8, '안전교육 기록', 2, 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (32, 10, '사업자등록증', 2, 1, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (33, 10, '건설업면허증', 2, 2, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (34, 10, '보험가입증명서', 2, 3, 0, 1, NULL, '2025-07-31 18:52:23'),
                                                                                                                                                    (35, 25, '기본안전', 3, 1, 0, 1, '', '2025-07-31 19:45:39');

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
                                     `created_by` int(30) NOT NULL COMMENT '생성자 ID',
                                     `date_created` datetime DEFAULT current_timestamp(),
                                     `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
                                                                                                                                                                                 (1, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n\r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n\r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n\r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:20:34', NULL),
                                                                                                                                                                                 (2, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n\r\n{{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n\r\n제출 기한: {{due_date}}\r\n업로드 링크: {{upload_link}}\r\n\r\n감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:45:00', NULL),
                                                                                                                                                                                 (3, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n\r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n\r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n\r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:46:52', NULL),
                                                                                                                                                                                 (4, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n     \r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n     \r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n     \r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:49:34', NULL),
                                                                                                                                                                                 (5, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n     \r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n     \r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n     \r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:49:48', NULL),
                                                                                                                                                                                 (6, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n     \r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n     \r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n     \r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:50:47', NULL),
                                                                                                                                                                                 (7, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n     \r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n     \r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n     \r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:50:59', NULL),
                                                                                                                                                                                 (8, '서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}', '안녕하세요 {{supplier_name}} 담당자님,\r\n     \r\n     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.\r\n     \r\n     제출 기한: {{due_date}}\r\n     업로드 링크: {{upload_link}}\r\n     \r\n     감사합니다.', NULL, 1, 1, 1, '2025-07-31 18:51:30', NULL);

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

--
-- 테이블의 덤프 데이터 `order_items`
--

INSERT INTO `order_items` (`po_id`, `item_id`, `unit`, `unit_price`, `quantity`) VALUES
                                                                                     (1, 1, 'boxes', 15000, 10),
                                                                                     (1, 2, 'pcs', 17999.9, 6),
                                                                                     (2, 1, 'pcs', 3788.99, 10);

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

--
-- 테이블의 덤프 데이터 `po_list`
--

INSERT INTO `po_list` (`id`, `po_no`, `supplier_id`, `discount_percentage`, `discount_amount`, `tax_percentage`, `tax_amount`, `notes`, `status`, `date_created`, `date_updated`) VALUES
                                                                                                                                                                                      (1, 'PO-94619964639', 1, 2, 5159.99, 12, 30959.9, 'Sample Purchase Order Only', 1, '2021-09-08 15:20:57', '2021-09-08 15:59:56'),
                                                                                                                                                                                      (2, 'PO-92093417806', 2, 1, 378.899, 12, 4546.79, 'Sample', 1, '2021-09-08 15:49:55', '2025-07-31 17:01:31');

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
                                     `date_created` datetime DEFAULT current_timestamp()
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
                                                                                                                          (1, 'Supplier 101', 'Sample Address Only', 'George Wilson', '09123459879', 'supplier101@gmail.com', 1, '2021-09-08 09:46:45'),
                                                                                                                          (2, 'Supplier 102', 'Supplier 102 Address, 23rd St, Sample City, Test Province, ####', 'Samantha Lou', '09332145889', 'sLou@supplier102.com', 1, '2021-09-08 10:25:12');

-- --------------------------------------------------------

--
-- 테이블 구조 `system_info`
--

CREATE TABLE `system_info` (
                               `id` int(30) NOT NULL,
                               `meta_field` text NOT NULL,
                               `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
                                                                 (1, 'name', '건설업 서류 관리 시스템'),
                                                                 (6, 'short_name', 'CDMS'),
                                                                 (11, 'logo', 'uploads/1631064180_sample_compaby_logo.jpg'),
                                                                 (13, 'user_avatar', 'uploads/user_avatar.jpg'),
                                                                 (14, 'cover', 'uploads/1631064360_sample_bg.jpg'),
                                                                 (15, 'company_name', '중부재해예방관리원'),
                                                                 (16, 'company_email', 'info@sampleco.com'),
                                                                 (17, 'company_address', 'Sample Address, 23rd St., Sample City, ####');

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
-- 테이블의 인덱스 `document_requests`
--
ALTER TABLE `document_requests`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD UNIQUE KEY `upload_token` (`upload_token`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `document_requests_ibfk_2` (`created_by`);

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
  ADD KEY `category_id` (`category_id`);

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
-- 테이블의 인덱스 `upload_logs`
--
ALTER TABLE `upload_logs`
    ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

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
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 테이블의 AUTO_INCREMENT `document_costs`
--
ALTER TABLE `document_costs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- 테이블의 AUTO_INCREMENT `document_requests`
--
ALTER TABLE `document_requests`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `email_logs`
--
ALTER TABLE `email_logs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `email_templates`
--
ALTER TABLE `email_templates`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `po_list`
--
ALTER TABLE `po_list`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `request_documents`
--
ALTER TABLE `request_documents`
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
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `system_info`
--
ALTER TABLE `system_info`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 테이블의 AUTO_INCREMENT `upload_logs`
--
ALTER TABLE `upload_logs`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
    MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 테이블의 AUTO_INCREMENT `workflow_status`
--
ALTER TABLE `workflow_status`
    MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

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
-- 테이블의 제약사항 `document_requests`
--
ALTER TABLE `document_requests`
    ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `document_requests_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

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
    ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `request_documents_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

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
