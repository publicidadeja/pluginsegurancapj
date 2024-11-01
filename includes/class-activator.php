<?php

class Secure_Dacast_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de usuários autorizados
        $table_users = $wpdb->prefix . 'secure_dacast_authorized_users';
        $sql_users = "CREATE TABLE IF NOT EXISTS $table_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cpf varchar(14) NOT NULL,
            email varchar(100) NOT NULL,
            status tinyint(1) NOT NULL DEFAULT 1,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_access timestamp NULL DEFAULT NULL,
            notes text,
            PRIMARY KEY  (id),
            UNIQUE KEY cpf (cpf),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        // Tabela de tentativas de acesso
        $table_attempts = $wpdb->prefix . 'secure_dacast_access_attempts';
        $sql_attempts = "CREATE TABLE IF NOT EXISTS $table_attempts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            attempt_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            cpf varchar(14) NOT NULL,
            email varchar(100) NOT NULL,
            success tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address),
            KEY attempt_time (attempt_time)
        ) $charset_collate;";

        // Tabela de sessões
        $table_sessions = $wpdb->prefix . 'secure_dacast_sessions';
        $sql_sessions = "CREATE TABLE IF NOT EXISTS $table_sessions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_token varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(255) NOT NULL,
            last_activity timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY session_token (session_token),
            KEY user_id (user_id),
            KEY last_activity (last_activity)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Cria ou atualiza as tabelas
        dbDelta($sql_users);
        dbDelta($sql_attempts);
        dbDelta($sql_sessions);

        // Adiciona índices adicionais para melhor performance
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_status ON $table_users (status)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_last_access ON $table_users (last_access)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_ip_time ON $table_attempts (ip_address, attempt_time)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_session_active ON $table_sessions (user_id, last_activity)");

        // Insere configurações padrão se não existirem
        if (!get_option('secure_dacast_version')) {
            add_option('secure_dacast_version', SECURE_DACAST_VERSION);
        }

        // Marca o tempo da última ativação
        update_option('secure_dacast_last_activated', current_time('mysql'));
    }
}