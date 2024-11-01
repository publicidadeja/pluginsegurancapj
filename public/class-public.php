<?php
class Secure_Dacast_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Hooks principais
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'protect_content'));
        add_action('init', array($this, 'start_session'));
        
        // Filtros de conteúdo
        add_filter('the_content', array($this, 'filter_protected_content'));
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function enqueue_styles() {
        if ($this->is_protected_page()) {
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'css/public-style.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    public function enqueue_scripts() {
        if ($this->is_protected_page()) {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/public-script.js',
                array('jquery'),
                $this->version,
                true
            );

            wp_localize_script(
                $this->plugin_name,
                'secureDacastPublic',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('secure_dacast_public'),
                    'messages' => array(
                        'access_denied' => __('Acesso negado. Por favor, faça login.', 'secure-dacast'),
                        'error' => __('Ocorreu um erro. Tente novamente.', 'secure-dacast')
                    )
                )
            );
        }
    }

    private function is_protected_page() {
        if (!is_singular()) return false;
        
        $protected_pages = get_option('secure_dacast_protected_pages', array());
        return in_array(get_the_ID(), (array)$protected_pages);
    }

    public function protect_content() {
        if ($this->is_protected_page()) {
            if (!is_user_logged_in()) {
                auth_redirect();
            }

            if (!$this->check_user_permissions()) {
                wp_die(
                    __('Você não tem permissão para acessar este conteúdo.', 'secure-dacast'),
                    __('Acesso Negado', 'secure-dacast'),
                    array('response' => 403)
                );
            }
        }
    }

    public function filter_protected_content($content) {
        if ($this->is_protected_page()) {
            if (!is_user_logged_in()) {
                return $this->get_login_form();
            }

            if (!$this->check_user_permissions()) {
                return sprintf(
                    '<div class="secure-dacast-message error">%s</div>',
                    __('Você não tem permissão para visualizar este conteúdo.', 'secure-dacast')
                );
            }

            // Aplica proteção adicional ao conteúdo
            $content = $this->apply_content_protection($content);
        }

        return $content;
    }

    private function check_user_permissions() {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        $security_settings = get_option('secure_dacast_security_settings', array());
        $allowed_roles = isset($security_settings['allowed_roles']) ? $security_settings['allowed_roles'] : array('administrator');
        
        $user = wp_get_current_user();
        return array_intersect($allowed_roles, (array)$user->roles);
    }

    private function get_login_form() {
        ob_start();
        ?>
        <div class="secure-dacast-login-form">
            <h3><?php _e('Acesso Restrito', 'secure-dacast'); ?></h3>
            <p><?php _e('Faça login para acessar este conteúdo.', 'secure-dacast'); ?></p>
            <?php 
                wp_login_form(array(
                    'redirect' => get_permalink()
                )); 
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function apply_content_protection($content) {
        // Desabilita seleção de texto
        $content = '<div class="secure-dacast-protected" oncontextmenu="return false;">' . $content . '</div>';

        // Adiciona marca d'água
        $watermark = sprintf(
            '<div class="secure-dacast-watermark">%s</div>',
            get_bloginfo('name')
        );

        return $watermark . $content;
    }

    // Handlers AJAX públicos
    public function handle_content_access() {
        check_ajax_referer('secure_dacast_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Usuário não autenticado', 'secure-dacast'));
        }

        if (!$this->check_user_permissions()) {
            wp_send_json_error(__('Permissão negada', 'secure-dacast'));
        }

        // Implementar lógica de acesso ao conteúdo
        wp_send_json_success(array(
            'message' => __('Acesso concedido', 'secure-dacast')
        ));
    }

    // Utilitários
    private function log_access_attempt($success = false) {
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'];
        $page_id = get_the_ID();
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'ip' => $ip,
            'page_id' => $page_id,
            'success' => $success
        );

        $logs = get_option('secure_dacast_access_logs', array());
        array_unshift($logs, $log_entry);
        
        // Mantém apenas os últimos 1000 registros
        $logs = array_slice($logs, 0, 1000);
        
        update_option('secure_dacast_access_logs', $logs);
    }
}