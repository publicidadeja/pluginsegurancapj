<?php
class Secure_Dacast_Session_Manager {
    private $table_sessions;
    
    public function __construct() {
        global $wpdb;
        $this->table_sessions = $wpdb->prefix . 'secure_dacast_sessions';
    }

    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_sessions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_token varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(255) NOT NULL,
            last_activity timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY session_token (session_token),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function create_session($user_id) {
    global $wpdb;
    
    // Limpa qualquer sessão antiga primeiro
    $this->clean_expired_sessions();
    
    // Verifica se já existe uma sessão ativa
    $active_session = $this->get_active_session($user_id);
    if ($active_session) {
        return array(
            'success' => false,
            'message' => 'Sessão ativa encontrada'
        );
    }
    
    // Cria nova sessão
    $session_token = wp_hash(uniqid('secure_dacast_', true));
    
    $result = $wpdb->insert(
        $this->table_sessions,
        array(
            'user_id' => $user_id,
            'session_token' => $session_token,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'last_activity' => current_time('mysql', 1)
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        return array(
            'success' => false,
            'message' => 'Erro ao criar sessão'
        );
    }
    
    return array(
        'success' => true,
        'session_token' => $session_token
    );
}
  
  private function log_concurrent_access_attempt($user_id) {
    global $wpdb;
    // Implementar log da tentativa
}

private function notify_admin_concurrent_access($user_id) {
    $admin_email = get_option('admin_email');
    $user = get_userdata($user_id);
    
    $subject = 'Tentativa de acesso simultâneo detectada';
    $message = sprintf('Usuário %s tentou acessar com uma sessão já ativa.', $user->user_email);
    
    wp_mail($admin_email, $subject, $message);
}

    public function get_active_session($user_id) {
    global $wpdb;
    
    // Define o tempo limite de sessão (30 minutos por padrão)
    $timeout = get_option('secure_dacast_settings')['session_timeout'] ?? 30;
    $expiry_time = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));
    
    // Limpa sessões expiradas primeiro
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$this->table_sessions} 
        WHERE user_id = %d AND last_activity <= %s",
        $user_id,
        $expiry_time
    ));
    
    // Agora verifica se existe sessão ativa
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->table_sessions} 
        WHERE user_id = %d 
        AND last_activity > %s
        LIMIT 1",
        $user_id,
        $expiry_time
    ));
}

    public function update_session($session_token) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_sessions,
            array('last_activity' => current_time('mysql', 1)),
            array('session_token' => $session_token),
            array('%s'),
            array('%s')
        );
    }

    public function end_session($session_token) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_sessions,
            array('session_token' => $session_token),
            array('%s')
        );
    }

    public function clean_expired_sessions() {
        global $wpdb;
        
        $timeout = get_option('secure_dacast_settings')['session_timeout'] ?? 30;
        $expiry_time = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_sessions} WHERE last_activity < %s",
            $expiry_time
        ));
    }

    public function get_active_sessions() {
        global $wpdb;
        
        $timeout = get_option('secure_dacast_settings')['session_timeout'] ?? 30;
        $expiry_time = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.cpf, u.email 
            FROM {$this->table_sessions} s
            JOIN {$wpdb->prefix}secure_dacast_authorized_users u ON s.user_id = u.id
            WHERE s.last_activity > %s
            ORDER BY s.last_activity DESC",
            $expiry_time
        ));
    }
}