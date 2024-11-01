<?php
class Secure_Dacast_Monitoring {
    public function init() {
        add_action('admin_init', array($this, 'setup_monitoring'));
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'), 10, 2);
    }

    public function setup_monitoring() {
        if (!wp_next_scheduled('secure_dacast_monitoring_check')) {
            wp_schedule_event(time(), 'hourly', 'secure_dacast_monitoring_check');
        }
        
        add_action('secure_dacast_monitoring_check', array($this, 'check_suspicious_activities'));
    }

    public function log_failed_login($username) {
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $table_name = $wpdb->prefix . 'secure_dacast_security_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => 'failed_login',
                'user_login' => $username,
                'ip_address' => $ip,
                'timestamp' => current_time('mysql'),
                'details' => json_encode(array(
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ))
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        // Verifica tentativas consecutivas
        $this->check_failed_attempts($ip);
    }

    public function log_successful_login($user_login, $user) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_security_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => 'successful_login',
                'user_login' => $user_login,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => current_time('mysql'),
                'user_id' => $user->ID,
                'details' => json_encode(array(
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ))
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    private function check_failed_attempts($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_security_logs';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE ip_address = %s 
            AND event_type = 'failed_login' 
            AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            $ip
        ));

        if ($count >= 5) {
            $this->block_ip($ip);
            $this->send_alert('multiple_failed_attempts', array(
                'ip' => $ip,
                'attempts' => $count
            ));
        }
    }

    private function block_ip($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_ip_blocks';
        
        $wpdb->insert(
            $table_name,
            array(
                'ip' => $ip,
                'block_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'reason' => 'multiple_failed_attempts'
            ),
            array('%s', '%s', '%s')
        );
    }

    public function check_suspicious_activities() {
        global $wpdb;
        
        // Verifica acessos simultâneos
        $this->check_concurrent_sessions();
        
        // Verifica tentativas de acesso em horários suspeitos
        $this->check_odd_hour_access();
        
        // Verifica acessos de IPs diferentes para mesmo usuário
        $this->check_multiple_ip_access();
    }

    private function send_alert($type, $data) {
        $admin_email = get_option('admin_email');
        $subject = sprintf('[Secure Dacast] Alerta de Segurança: %s', $type);
        
        $message = $this->format_alert_message($type, $data);
        
        wp_mail($admin_email, $subject, $message);
    }

    private function format_alert_message($type, $data) {
        $message = "Um alerta de segurança foi detectado:\n\n";
        
        switch ($type) {
            case 'multiple_failed_attempts':
                $message .= sprintf(
                    "Múltiplas tentativas de login falhas detectadas.\nIP: %s\nTentativas: %d\n",
                    $data['ip'],
                    $data['attempts']
                );
                break;
            // Adicione outros casos conforme necessário
        }
        
        $message .= "\nData/Hora: " . current_time('mysql');
        
        return $message;
    }

    private function check_concurrent_sessions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_session_logs';
        
        $results = $wpdb->get_results(
            "SELECT user_id, COUNT(DISTINCT session_id) as session_count 
            FROM $table_name 
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            GROUP BY user_id 
            HAVING session_count > 1"
        );

        foreach ($results as $result) {
            $this->send_alert('concurrent_sessions', array(
                'user_id' => $result->user_id,
                'session_count' => $result->session_count
            ));
        }
    }

    private function check_odd_hour_access() {
        // Implementar verificação de acessos em horários suspeitos
        // Por exemplo, acessos entre 2h e 5h da manhã
    }

    private function check_multiple_ip_access() {
        // Implementar verificação de múltiplos IPs para mesmo usuário
        // em curto período de tempo
    }
}