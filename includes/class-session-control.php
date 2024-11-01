<?php
class Secure_Dacast_Session_Control {
    private $heartbeat_interval = 30; // segundos

    public function init() {
        add_action('wp_ajax_secure_dacast_heartbeat', array($this, 'handle_heartbeat'));
        add_action('wp_ajax_secure_dacast_check_session', array($this, 'check_active_session'));
        add_action('wp_login', array($this, 'handle_new_login'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_heartbeat_script'));
    }

    public function enqueue_heartbeat_script() {
        if (is_user_logged_in()) {
            wp_enqueue_script(
                'secure-dacast-heartbeat',
                SECURE_DACAST_PLUGIN_URL . 'public/js/heartbeat.js',
                array('jquery'),
                SECURE_DACAST_VERSION,
                true
            );

            wp_localize_script('secure-dacast-heartbeat', 'secureDacastHeartbeat', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('secure_dacast_heartbeat'),
                'interval' => $this->heartbeat_interval * 1000,
                'login_url' => wp_login_url(get_permalink())
            ));
        }
    }

    public function handle_heartbeat() {
        check_ajax_referer('secure_dacast_heartbeat', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuário não autenticado');
        }

        $current_session = get_user_meta($user_id, 'secure_dacast_active_session', true);
        $session_id = $_POST['session_id'];

        if ($current_session && $current_session !== $session_id) {
            wp_send_json_error('Sessão inválida');
        }

        update_user_meta($user_id, 'secure_dacast_last_heartbeat', time());
        wp_send_json_success();
    }

    public function check_active_session() {
        check_ajax_referer('secure_dacast_heartbeat', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuário não autenticado');
        }

        $current_session = get_user_meta($user_id, 'secure_dacast_active_session', true);
        
        if (!$current_session || !$this->check_session_status($user_id)) {
            wp_send_json_error('Sessão encerrada');
        }

        wp_send_json_success();
    }

    public function handle_new_login($user_login, $user) {
        $session_id = wp_generate_password(32, false);
        $old_session = get_user_meta($user->ID, 'secure_dacast_active_session', true);

        if ($old_session) {
            // Força logout da sessão anterior
            $this->terminate_session($user->ID, $old_session);
        }

        update_user_meta($user->ID, 'secure_dacast_active_session', $session_id);
        update_user_meta($user->ID, 'secure_dacast_last_heartbeat', time());

        // Registra a nova sessão
        $this->log_session($user->ID, $session_id, 'new_session');
    }

    public function terminate_session($user_id, $session_id) {
        // Registra o término da sessão
        $this->log_session($user_id, $session_id, 'terminated');
        
        // Limpa os dados da sessão
        delete_user_meta($user_id, 'secure_dacast_active_session');
        delete_user_meta($user_id, 'secure_dacast_last_heartbeat');
        
        // Força limpeza do cache da sessão
        wp_cache_delete($user_id, 'user_meta');
    }

    private function log_session($user_id, $session_id, $action) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'secure_dacast_session_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }

    public function check_session_status($user_id) {
        $last_heartbeat = get_user_meta($user_id, 'secure_dacast_last_heartbeat', true);
        $current_session = get_user_meta($user_id, 'secure_dacast_active_session', true);
        $session_timeout = $this->heartbeat_interval * 2;

        if (!$current_session || !$last_heartbeat || (time() - $last_heartbeat) > $session_timeout) {
            $this->terminate_session($user_id, $current_session);
            return false;
        }

        return true;
    }
}