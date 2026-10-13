-- ScamShield — Full MVP Database Schema
-- MariaDB / MySQL 10.4+ / 8.0+
-- Character set: utf8mb4, collation: utf8mb4_unicode_ci
-- Run: php database/setup.php   (creates DB + user, then sources this file)
-- Or:  SOURCE /path/to/schema.sql  inside MySQL CLI after creating the DB.
--
-- All ENUM values are narrow and controlled. Expand only with a schema migration.
-- All user-supplied content stored via PDO prepared statements — never concatenated.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    display_name  VARCHAR(120)     NOT NULL,
    email         VARCHAR(254)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- analyses
-- Stores heuristic analysis results. Input is stored in minimised
-- form only (first 500 chars) to avoid retaining full message bodies.
-- user_id is NULL for anonymous analysis (if enabled by a future decision).
-- ============================================================
CREATE TABLE IF NOT EXISTS analyses (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED  NULL DEFAULT NULL,
    input_type      ENUM('url','sms','upi') NOT NULL DEFAULT 'url',
    input_excerpt   VARCHAR(500)  NOT NULL DEFAULT '',  -- truncated, not raw full input
    risk_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    risk_band       ENUM('low','moderate','high','critical') NOT NULL DEFAULT 'low',
    rules_version   VARCHAR(20)   NOT NULL DEFAULT '1.4',
    result_json     JSON          NOT NULL,             -- full findings, tokens, audit log
    threat_status   ENUM('not_checked','no_match','match','unavailable') NOT NULL DEFAULT 'not_checked',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_analyses_user (user_id),
    KEY idx_analyses_created (created_at),
    CONSTRAINT fk_analyses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- incidents
-- ============================================================
CREATE TABLE IF NOT EXISTS incidents (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    public_reference   CHAR(12)      NOT NULL,    -- e.g. SS-2026-XXXXXX
    user_id            INT UNSIGNED  NOT NULL,
    incident_type      VARCHAR(80)   NOT NULL,
    incident_at        DATETIME      NULL DEFAULT NULL,
    description        TEXT          NOT NULL,
    channel            VARCHAR(200)  NOT NULL DEFAULT '',  -- phone/email/URL used by scammer
    status             ENUM('submitted','under_review','info_needed','closed') NOT NULL DEFAULT 'submitted',
    assigned_admin_id  INT UNSIGNED  NULL DEFAULT NULL,
    analysis_id        INT UNSIGNED  NULL DEFAULT NULL,  -- optional link to triggering analysis
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_incidents_ref (public_reference),
    KEY idx_incidents_user (user_id),
    KEY idx_incidents_status (status),
    CONSTRAINT fk_incidents_user  FOREIGN KEY (user_id)           REFERENCES users(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_incidents_admin FOREIGN KEY (assigned_admin_id) REFERENCES users(id)    ON DELETE SET NULL,
    CONSTRAINT fk_incidents_analysis FOREIGN KEY (analysis_id)    REFERENCES analyses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- evidence_files
-- Files are stored outside the web root. This table holds metadata only.
-- ============================================================
CREATE TABLE IF NOT EXISTS evidence_files (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    incident_id      INT UNSIGNED  NOT NULL,
    uploaded_by      INT UNSIGNED  NOT NULL,
    storage_key      VARCHAR(120)  NOT NULL,    -- random server-generated filename
    original_name    VARCHAR(255)  NOT NULL,    -- sanitised display name (no path)
    detected_mime    VARCHAR(100)  NOT NULL,
    size_bytes       INT UNSIGNED  NOT NULL,
    sha256           CHAR(64)      NULL DEFAULT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evidence_key (storage_key),
    KEY idx_evidence_incident (incident_id),
    CONSTRAINT fk_evidence_incident FOREIGN KEY (incident_id)  REFERENCES incidents(id) ON DELETE RESTRICT,
    CONSTRAINT fk_evidence_uploader FOREIGN KEY (uploaded_by)  REFERENCES users(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- incident_status_history
-- Append-only log of status changes. Application must never UPDATE/DELETE rows.
-- ============================================================
CREATE TABLE IF NOT EXISTS incident_status_history (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    incident_id  INT UNSIGNED  NOT NULL,
    changed_by   INT UNSIGNED  NOT NULL,
    old_status   VARCHAR(30)   NOT NULL,
    new_status   VARCHAR(30)   NOT NULL,
    reason       VARCHAR(500)  NOT NULL DEFAULT '',
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hist_incident (incident_id),
    CONSTRAINT fk_hist_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE RESTRICT,
    CONSTRAINT fk_hist_changer  FOREIGN KEY (changed_by)  REFERENCES users(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- audit_logs
-- Append-only. Application must never UPDATE/DELETE rows here.
-- actor_user_id is NULL for system/unauthenticated events.
-- metadata_json must NOT contain passwords, tokens, or full file contents.
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    actor_user_id    INT UNSIGNED  NULL DEFAULT NULL,
    action           VARCHAR(80)   NOT NULL,   -- e.g. 'login', 'status_change', 'evidence_download'
    target_type      VARCHAR(60)   NULL DEFAULT NULL,  -- e.g. 'incident', 'user'
    target_id        INT UNSIGNED  NULL DEFAULT NULL,
    metadata_json    JSON          NULL DEFAULT NULL,  -- redacted context
    ip_address       VARCHAR(45)   NULL DEFAULT NULL,  -- IPv4/IPv6, stored for abuse prevention only
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_actor  (actor_user_id),
    KEY idx_audit_target (target_type, target_id),
    KEY idx_audit_action (action),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
