-- ================================================
-- 🏗️ 건설업 서류 관리 시스템 - 새 테이블 추가
-- ================================================
-- 기존 Purchase Order DB는 그대로 유지하고 새로운 테이블만 추가
-- 테스트 완료 후 기존 테이블(po_list, item_list, order_items)은 삭제 예정

-- ================================================
-- ✅ 기존 테이블 그대로 유지
-- ================================================
-- supplier_list ✅ (의뢰처 = 공급업체)
-- users ✅ (사용자 관리)
-- system_info ✅ (시스템 설정)
-- po_list ✅ (기존 구매주문 - 당분간 유지)
-- item_list ✅ (기존 품목 - 당분간 유지)
-- order_items ✅ (기존 주문품목 - 당분간 유지)

-- ================================================
-- 🆕 새로운 테이블 생성 (기존 테이블과 독립적)
-- ================================================

-- 📋 서류 분류 관리 (대중소 분류) - 새 테이블
CREATE TABLE `document_categories` (
                                       `id` int(30) NOT NULL AUTO_INCREMENT,
                                       `parent_id` int(30) DEFAULT NULL COMMENT '상위 분류 ID',
                                       `name` varchar(255) NOT NULL COMMENT '분류명',
                                       `level` tinyint(1) NOT NULL COMMENT '분류 레벨 (1=대, 2=중, 3=소)',
                                       `display_order` int(11) DEFAULT 0 COMMENT '표시 순서',
                                       `is_required` tinyint(1) DEFAULT 0 COMMENT '필수 서류 여부',
                                       `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                       `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                       PRIMARY KEY (`id`),
                                       KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📧 서류 요청 관리 - 새 테이블
CREATE TABLE `document_requests` (
                                     `id` int(30) NOT NULL AUTO_INCREMENT,
                                     `request_no` varchar(50) NOT NULL COMMENT '요청번호 (REQ-YYYYMMDD-001)',
                                     `supplier_id` int(30) NOT NULL COMMENT '의뢰처 ID',
                                     `project_name` varchar(255) NOT NULL COMMENT '프로젝트명',
                                     `due_date` date NOT NULL COMMENT '제출 기한',
                                     `additional_notes` text COMMENT '추가 요청사항',
                                     `upload_token` varchar(100) NOT NULL COMMENT '업로드 토큰',
                                     `status` tinyint(1) DEFAULT 0 COMMENT '상태 (0=대기, 1=진행중, 2=완료)',
                                     `email_sent_at` datetime DEFAULT NULL COMMENT '이메일 전송 시간',
                                     `created_by` int(30) NOT NULL COMMENT '생성자 ID',
                                     `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                     `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                     PRIMARY KEY (`id`),
                                     UNIQUE KEY `request_no` (`request_no`),
                                     UNIQUE KEY `upload_token` (`upload_token`),
                                     KEY `supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📄 요청된 서류 목록 (기존 order_items 대체)
CREATE TABLE `request_documents` (
                                     `id` int(30) NOT NULL AUTO_INCREMENT,
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
                                     `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                     PRIMARY KEY (`id`),
                                     KEY `request_id` (`request_id`),
                                     KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📋 동적 폼 필드 정의 (웹에서 직접 작성할 서류용)
CREATE TABLE `form_fields` (
                               `id` int(30) NOT NULL AUTO_INCREMENT,
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
                               `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                               PRIMARY KEY (`id`),
                               KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📈 업로드 로그 (보안 및 추적용)
CREATE TABLE `upload_logs` (
                               `id` int(30) NOT NULL AUTO_INCREMENT,
                               `request_id` int(30) NOT NULL COMMENT '요청 ID',
                               `document_id` int(30) DEFAULT NULL COMMENT '서류 ID',
                               `action` varchar(50) NOT NULL COMMENT '액션 (upload, delete, replace, form_submit)',
                               `file_name` varchar(255) DEFAULT NULL COMMENT '파일명',
                               `file_size` int(11) DEFAULT NULL COMMENT '파일 크기',
                               `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
                               `user_agent` text COMMENT '브라우저 정보',
                               `details` text DEFAULT NULL COMMENT '상세 정보',
                               `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                               PRIMARY KEY (`id`),
                               KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📊 시스템 알림
CREATE TABLE `notifications` (
                                 `id` int(30) NOT NULL AUTO_INCREMENT,
                                 `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                 `type` varchar(50) NOT NULL COMMENT '알림 타입',
                                 `title` varchar(255) NOT NULL COMMENT '제목',
                                 `message` text NOT NULL COMMENT '내용',
                                 `is_read` tinyint(1) DEFAULT 0 COMMENT '읽음 여부',
                                 `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                                 PRIMARY KEY (`id`),
                                 KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📧 이메일 발송 로그
CREATE TABLE `email_logs` (
                              `id` int(30) NOT NULL AUTO_INCREMENT,
                              `request_id` int(30) NOT NULL COMMENT '요청 ID',
                              `recipient_email` varchar(255) NOT NULL COMMENT '수신자 이메일',
                              `subject` varchar(500) NOT NULL COMMENT '제목',
                              `content` longtext NOT NULL COMMENT '내용',
                              `status` enum('pending','sent','failed') DEFAULT 'pending' COMMENT '발송 상태',
                              `sent_at` datetime DEFAULT NULL COMMENT '발송 시간',
                              `error_message` text DEFAULT NULL COMMENT '오류 메시지',
                              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (`id`),
                              KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 🔗 3. 외래키 제약조건 추가
-- ================================================

-- request_documents 테이블 외래키
ALTER TABLE `request_documents`
    ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `request_documents_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- form_fields 테이블 외래키
ALTER TABLE `form_fields`
    ADD CONSTRAINT `form_fields_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- document_requests 테이블 외래키
ALTER TABLE `document_requests`
    ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `document_requests_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- document_categories 자체 참조 외래키
ALTER TABLE `document_categories`
    ADD CONSTRAINT `document_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- ================================================
-- 📊 4. 기본 데이터 삽입
-- ================================================

-- 시스템 정보 업데이트
UPDATE `system_info` SET `meta_value` = '건설업 서류 관리 시스템' WHERE `meta_field` = 'name';
UPDATE `system_info` SET `meta_value` = 'CDMS' WHERE `meta_field` = 'short_name';

-- 📋 서류 분류 기본 데이터 (3단계 분류)

-- 🔸 대분류 1: 안전관리계획서
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (1, NULL, '안전관리계획서', 1, 1, 1, 1);

-- 🔸 중분류 1-1: 안전관리계획서 하위
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
                                                                                                                     (2, 1, '기본 안전관리계획서', 2, 1, 1, 1),
                                                                                                                     (3, 1, '위험성평가서', 2, 2, 1, 1),
                                                                                                                     (4, 1, '안전점검계획서', 2, 3, 0, 1);

-- 🔸 대분류 2: 유해위험방지계획서
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (5, NULL, '유해위험방지계획서', 1, 2, 1, 1);

-- 🔸 중분류 2-1: 유해위험방지계획서 하위
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
                                                                                                                     (6, 5, '굴착작업방지계획서', 2, 1, 1, 1),
                                                                                                                     (7, 5, '고소작업방지계획서', 2, 2, 1, 1),
                                                                                                                     (8, 5, '크레인작업방지계획서', 2, 3, 0, 1);

-- 🔸 대분류 3: 교육시설안전성평가
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (9, NULL, '교육시설안전성평가', 1, 3, 0, 1);

-- 🔸 대분류 4: 절도보호지구안전성평가
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (10, NULL, '절도보호지구안전성평가', 1, 4, 0, 1);

-- 🔸 대분류 5: 수주모안전관리계획서
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (11, NULL, '수주모안전관리계획서', 1, 5, 0, 1);

-- 🔸 대분류 6: 기타 서류
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (12, NULL, '기타', 1, 6, 0, 1);

-- 🔸 중분류 6-1: 기타 하위
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
                                                                                                                     (13, 12, '기본', 2, 1, 0, 1),
                                                                                                                     (14, 12, '설계', 2, 2, 0, 1),
                                                                                                                     (15, 12, '공사', 2, 3, 0, 1);

-- 🔸 소분류 예시 (공사안전보건계획 하위)
INSERT INTO `document_categories` (`id`, `parent_id`, `name`, `level`, `display_order`, `is_required`, `status`) VALUES
    (16, 15, '착정성평가', 3, 1, 0, 1);

-- 📋 동적 폼 필드 예시 (안전관리계획서용)
INSERT INTO `form_fields` (`category_id`, `field_name`, `field_label`, `field_type`, `is_required`, `display_order`) VALUES
                                                                                                                         (2, 'management_no', '관리번호', 'text', 1, 1),
                                                                                                                         (2, 'requester', '의뢰처', 'text', 1, 2),
                                                                                                                         (2, 'project_name', '공사명', 'text', 1, 3),
                                                                                                                         (2, 'client_name', '발주자', 'text', 1, 4),
                                                                                                                         (2, 'order_date', '수주일', 'date', 1, 5),
                                                                                                                         (2, 'start_date', '착공일', 'date', 0, 6),
                                                                                                                         (2, 'has_safety_plan', '안전관리계획서', 'checkbox', 0, 7),
                                                                                                                         (2, 'has_risk_prevention', '유해위험방지계획서', 'checkbox', 0, 8),
                                                                                                                         (2, 'signature', '작성자 서명', 'signature', 1, 9);

-- AUTO_INCREMENT 값 설정
ALTER TABLE `document_categories` AUTO_INCREMENT = 17;
ALTER TABLE `form_fields` AUTO_INCREMENT = 1;
ALTER TABLE `request_documents` AUTO_INCREMENT = 1;
ALTER TABLE `document_requests` AUTO_INCREMENT = 1;
ALTER TABLE `upload_logs` AUTO_INCREMENT = 1;
ALTER TABLE `notifications` AUTO_INCREMENT = 1;
ALTER TABLE `email_logs` AUTO_INCREMENT = 1;

-- ================================================
-- 🚨 추가 필수 테이블 (누락 부분 보완)
-- ================================================

-- 📋 동적 폼 템플릿 관리 (서류별 폼 레이아웃 저장)
CREATE TABLE `form_templates` (
                                  `id` int(30) NOT NULL AUTO_INCREMENT,
                                  `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                                  `template_name` varchar(255) NOT NULL COMMENT '템플릿명',
                                  `template_html` longtext NOT NULL COMMENT 'HTML 템플릿',
                                  `template_css` text DEFAULT NULL COMMENT 'CSS 스타일',
                                  `template_js` text DEFAULT NULL COMMENT 'JavaScript 코드',
                                  `version` varchar(20) DEFAULT '1.0' COMMENT '템플릿 버전',
                                  `is_active` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                  `created_by` int(30) NOT NULL COMMENT '생성자',
                                  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                  `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                  PRIMARY KEY (`id`),
                                  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📊 작성비용 관리 (VAT 별도 테이블)
CREATE TABLE `document_costs` (
                                  `id` int(30) NOT NULL AUTO_INCREMENT,
                                  `category_id` int(30) NOT NULL COMMENT '서류 분류 ID',
                                  `base_cost` decimal(10,2) DEFAULT 0.00 COMMENT '기본 작성비용',
                                  `vat_percentage` decimal(5,2) DEFAULT 10.00 COMMENT 'VAT 비율',
                                  `vat_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'VAT 금액',
                                  `total_cost` decimal(10,2) DEFAULT 0.00 COMMENT '총 비용',
                                  `cost_type` enum('fixed','variable','negotiable') DEFAULT 'fixed' COMMENT '비용 타입',
                                  `effective_date` date NOT NULL COMMENT '적용 시작일',
                                  `expiry_date` date DEFAULT NULL COMMENT '적용 종료일',
                                  `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                  PRIMARY KEY (`id`),
                                  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 🔐 토큰 관리 (업로드 링크 보안 강화)
CREATE TABLE `access_tokens` (
                                 `id` int(30) NOT NULL AUTO_INCREMENT,
                                 `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                 `token` varchar(100) NOT NULL COMMENT '토큰',
                                 `token_type` enum('upload','view','admin') DEFAULT 'upload' COMMENT '토큰 타입',
                                 `expires_at` datetime NOT NULL COMMENT '만료 시간',
                                 `is_used` tinyint(1) DEFAULT 0 COMMENT '사용 여부',
                                 `used_at` datetime DEFAULT NULL COMMENT '사용 시간',
                                 `ip_address` varchar(45) DEFAULT NULL COMMENT '사용된 IP',
                                 `user_agent` text DEFAULT NULL COMMENT '사용된 브라우저',
                                 `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                                 PRIMARY KEY (`id`),
                                 UNIQUE KEY `token` (`token`),
                                 KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📧 이메일 템플릿 관리
CREATE TABLE `email_templates` (
                                   `id` int(30) NOT NULL AUTO_INCREMENT,
                                   `template_name` varchar(100) NOT NULL COMMENT '템플릿명',
                                   `template_type` enum('request_notification','reminder','completion','rejection') NOT NULL COMMENT '템플릿 타입',
                                   `subject` varchar(500) NOT NULL COMMENT '제목 템플릿',
                                   `content` longtext NOT NULL COMMENT '내용 템플릿',
                                   `variables` text DEFAULT NULL COMMENT '사용 가능한 변수 (JSON)',
                                   `is_html` tinyint(1) DEFAULT 1 COMMENT 'HTML 형식 여부',
                                   `is_default` tinyint(1) DEFAULT 0 COMMENT '기본 템플릿 여부',
                                   `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                   `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                   `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 🏢 검토 기관 정보 (CSI 등)
CREATE TABLE `review_organizations` (
                                        `id` int(30) NOT NULL AUTO_INCREMENT,
                                        `org_name` varchar(255) NOT NULL COMMENT '기관명',
                                        `org_code` varchar(50) NOT NULL COMMENT '기관 코드',
                                        `contact_person` varchar(100) DEFAULT NULL COMMENT '담당자',
                                        `phone` varchar(50) DEFAULT NULL COMMENT '전화번호',
                                        `email` varchar(255) DEFAULT NULL COMMENT '이메일',
                                        `address` text DEFAULT NULL COMMENT '주소',
                                        `specialization` text DEFAULT NULL COMMENT '전문 분야',
                                        `status` tinyint(1) DEFAULT 1 COMMENT '활성 상태',
                                        `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
                                        PRIMARY KEY (`id`),
                                        UNIQUE KEY `org_code` (`org_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 📋 업무 진행 상태 세분화
CREATE TABLE `workflow_status` (
                                   `id` int(30) NOT NULL AUTO_INCREMENT,
                                   `request_id` int(30) NOT NULL COMMENT '요청 ID',
                                   `current_step` enum('created','documents_requested','in_progress','under_review','completed','rejected') NOT NULL COMMENT '현재 단계',
                                   `step_name` varchar(100) NOT NULL COMMENT '단계명',
                                   `step_description` text DEFAULT NULL COMMENT '단계 설명',
                                   `started_at` datetime DEFAULT NULL COMMENT '시작 시간',
                                   `completed_at` datetime DEFAULT NULL COMMENT '완료 시간',
                                   `assigned_to` int(30) DEFAULT NULL COMMENT '담당자 ID',
                                   `notes` text DEFAULT NULL COMMENT '진행 노트',
                                   `is_current` tinyint(1) DEFAULT 0 COMMENT '현재 단계 여부',
                                   `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                                   PRIMARY KEY (`id`),
                                   KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 🔗 추가 외래키 제약조건
-- ================================================

-- form_templates 외래키
ALTER TABLE `form_templates`
    ADD CONSTRAINT `form_templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form_templates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- document_costs 외래키
ALTER TABLE `document_costs`
    ADD CONSTRAINT `document_costs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE CASCADE;

-- access_tokens 외래키
ALTER TABLE `access_tokens`
    ADD CONSTRAINT `access_tokens_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

-- workflow_status 외래키
ALTER TABLE `workflow_status`
    ADD CONSTRAINT `workflow_status_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_status_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ================================================
-- 📊 추가 기본 데이터 삽입
-- ================================================

-- 작성비용 기본 데이터
INSERT INTO `document_costs` (`category_id`, `base_cost`, `vat_percentage`, `vat_amount`, `total_cost`, `effective_date`) VALUES
                                                                                                                              (2, 220000.00, 10.00, 22000.00, 242000.00, '2025-01-01'), -- 기본 안전관리계획서
                                                                                                                              (3, 350000.00, 10.00, 35000.00, 385000.00, '2025-01-01'), -- 위험성평가서
                                                                                                                              (6, 130000.00, 10.00, 13000.00, 143000.00, '2025-01-01'), -- 굴착작업방지계획서
                                                                                                                              (16, 700000.00, 10.00, 70000.00, 770000.00, '2025-01-01'); -- 착정성평가

-- 검토 기관 기본 데이터
INSERT INTO `review_organizations` (`org_name`, `org_code`, `contact_person`, `email`) VALUES
                                                                                           ('중부제해예방관리원(주)', 'CSI', '관리자', 'info@csi.co.kr'),
                                                                                           ('안전보건공단', 'KOSHA', '담당자', 'contact@kosha.or.kr');

-- 이메일 템플릿 기본 데이터
INSERT INTO `email_templates` (`template_name`, `template_type`, `subject`, `content`, `is_default`) VALUES
    ('서류 요청 알림', 'request_notification', '[{{company_name}}] 서류 제출 요청 - {{project_name}}',
     '안녕하세요 {{supplier_name}} 담당자님,

     {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.

     제출 기한: {{due_date}}
     업로드 링크: {{upload_link}}

     감사합니다.', 1);

-- AUTO_INCREMENT 값 설정
ALTER TABLE `form_templates` AUTO_INCREMENT = 1;
ALTER TABLE `document_costs` AUTO_INCREMENT = 1;
ALTER TABLE `access_tokens` AUTO_INCREMENT = 1;
ALTER TABLE `email_templates` AUTO_INCREMENT = 1;
ALTER TABLE `review_organizations` AUTO_INCREMENT = 1;
ALTER TABLE `workflow_status` AUTO_INCREMENT = 1;

-- ================================================
-- ✅ 완전한 점진적 전환 SQL 완료!
-- ================================================
--
-- 🎯 실행 후 상태:
-- ✅ 기존 테이블 그대로 유지 (po_list, item_list, order_items)
-- ✅ 새로운 서류 관리 테이블 추가 완료
-- ✅ 기존 시스템 정상 작동 보장
-- ✅ 새로운 기능 개발 준비 완료
--
-- 📋 다음 단계:
-- 1. 이 SQL 실행 → 기존 시스템 + 새 테이블 공존
-- 2. 새 기능 개발 및 테스트
-- 3. 테스트 완료 후 아래 명령으로 구 테이블 정리:
--    DROP TABLE order_items;
--    DROP TABLE po_list;
--    DROP TABLE item_list;
--
-- 🚀 이제 안전하게 새로운 건설업 서류 관리 시스템을 구축할 수 있습니다!