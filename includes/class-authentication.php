<?php
class Secure_Dacast_Authentication {
    public function init() {
        add_action('init', array($this, 'start_session'));
        add_action('wp_login', array($this, 'user_login_handler'), 10, 2);
        add_action('wp_logout', array($this, 'user_logout_handler'));
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function validate_cpf($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Calcula os dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    public function generate_access_token($user_id) {
        $token = wp_generate_password(32, false);
        $expiry = time() + (30 * 60); // 30 minutos
        
        update_user_meta($user_id, 'secure_dacast_access_token', $token);
        update_user_meta($user_id, 'secure_dacast_token_expiry', $expiry);
        
        return $token;
    }

    public function verify_ip($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_ip_blocks';
        $blocked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE ip = %s AND attempts >= 5 AND block_expires > %d",
                $ip,
                time()
            )
        );
        
        return $blocked == 0;
    }

    public function user_login_handler($user_login, $user) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!$this->verify_ip($ip)) {
            wp_logout();
            wp_die('Seu IP está temporariamente bloqueado devido a múltiplas tentativas de login mal sucedidas.');
        }

        $this->generate_access_token($user->ID);
        $this->log_access($user->ID, $ip, 'login');
    }

    public function user_logout_handler() {
        $user_id = get_current_user_id();
        if ($user_id) {
            delete_user_meta($user_id, 'secure_dacast_access_token');
            $this->log_access($user_id, $_SERVER['REMOTE_ADDR'], 'logout');
        }
    }

    private function log_access($user_id, $ip, $action) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_access_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'ip_address' => $ip,
                'action' => $action,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
}