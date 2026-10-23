-- ScamShield — Hosted Database Schema
-- For InfinityFree / MySQL 8.0 / MariaDB 11.4
--
-- IMPORT INSTRUCTIONS:
--   1. Create database and user in InfinityFree control panel (Databases section).
--   2. Open phpMyAdmin from your InfinityFree control panel.
--   3. Select the database you created from the left panel.
--   4. Click Import → Choose File → select this file → click Go.
--   5. All 6 tables will be created. Verify under the Structure tab.
--
-- DIFFERENCES from local schema.sql:
--   - SET sql_mode removed: InfinityFree shared MySQL may reject custom sql_mode
--     in phpMyAdmin imports. The default MySQL 8.0 sql_mode is already strict.
--   - SET time_zone removed: requires SUPER privilege; not available on shared hosting.
--   - All logic, constraints, and data types are identical to the local schema.
--
-- SECURITY NOTES:
--   - Never share this file publicly; it reveals your table structure.
--   - Grant only SELECT, INSERT, UPDATE, DELETE — never GRANT ALL.
--   - The application user must NOT have DROP, ALTER, or CREATE privileges.

SET NAMES utf8mb4;

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
-- ============================================================
CREATE TABLE IF NOT EXISTS analyses (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED  NULL DEFAULT NULL,
    input_type      ENUM('url','sms','upi') NOT NULL DEFAULT 'url',
    input_excerpt   VARCHAR(500)  NOT NULL DEFAULT '',
    risk_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    risk_band       ENUM('low','moderate','high','critical') NOT NULL DEFAULT 'low',
    rules_version   VARCHAR(20)   NOT NULL DEFAULT '1.4',
    result_json     JSON          NOT NULL,
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
    public_reference   CHAR(12)      NOT NULL,
    user_id            INT UNSIGNED  NOT NULL,
    incident_type      VARCHAR(80)   NOT NULL,
    incident_at        DATETIME      NULL DEFAULT NULL,
    description        TEXT          NOT NULL,
    channel            VARCHAR(200)  NOT NULL DEFAULT '',
    status             ENUM('submitted','under_review','info_needed','closed') NOT NULL DEFAULT 'submitted',
    assigned_admin_id  INT UNSIGNED  NULL DEFAULT NULL,
    analysis_id        INT UNSIGNED  NULL DEFAULT NULL,
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_incidents_ref (public_reference),
    KEY idx_incidents_user (user_id),
    KEY idx_incidents_status (status),
    CONSTRAINT fk_incidents_user     FOREIGN KEY (user_id)           REFERENCES users(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_incidents_admin    FOREIGN KEY (assigned_admin_id) REFERENCES users(id)    ON DELETE SET NULL,
    CONSTRAINT fk_incidents_analysis FOREIGN KEY (analysis_id)       REFERENCES analyses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- evidence_files
-- ============================================================
CREATE TABLE IF NOT EXISTS evidence_files (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    incident_id      INT UNSIGNED  NOT NULL,
    uploaded_by      INT UNSIGNED  NOT NULL,
    storage_key      VARCHAR(120)  NOT NULL,
    original_name    VARCHAR(255)  NOT NULL,
    detected_mime    VARCHAR(100)  NOT NULL,
    size_bytes       INT UNSIGNED  NOT NULL,
    sha256           CHAR(64)      NULL DEFAULT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evidence_key (storage_key),
    KEY idx_evidence_incident (incident_id),
    CONSTRAINT fk_evidence_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE RESTRICT,
    CONSTRAINT fk_evidence_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- incident_status_history
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
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    actor_user_id    INT UNSIGNED  NULL DEFAULT NULL,
    action           VARCHAR(80)   NOT NULL,
    target_type      VARCHAR(60)   NULL DEFAULT NULL,
    target_id        INT UNSIGNED  NULL DEFAULT NULL,
    metadata_json    JSON          NULL DEFAULT NULL,
    ip_address       VARCHAR(45)   NULL DEFAULT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_actor  (actor_user_id),
    KEY idx_audit_target (target_type, target_id),
    KEY idx_audit_action (action),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
