<?php
class Secure_Dacast_Content_Protection {
    public function init() {
        add_filter('the_content', array($this, 'protect_content'));
        add_action('wp_head', array($this, 'add_protection_headers'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_protection_scripts'));
    }

    public function protect_content($content) {
        if (!$this->should_protect_page()) {
            return $content;
        }

        if (!is_user_logged_in()) {
            return $this->get_login_form();
        }

        $user_id = get_current_user_id();
        if (!$this->verify_user_access($user_id)) {
            return $this->get_access_denied_message();
        }

        // Adiciona marca d'água dinâmica
        $content = $this->add_watermark($content, $user_id);

        // Encapsula o conteúdo com proteções
        return $this->wrap_protected_content($content);
    }

    private function should_protect_page() {
        $protected_pages = get_option('secure_dacast_protected_pages', array());
        return in_array(get_the_ID(), $protected_pages);
    }

    private function verify_user_access($user_id) {
        // Verifica token de acesso
        $token = get_user_meta($user_id, 'secure_dacast_access_token', true);
        $expiry = get_user_meta($user_id, 'secure_dacast_token_expiry', true);

        if (!$token || !$expiry || $expiry < time()) {
            return false;
        }

        // Verifica sessão ativa
        $session_control = new Secure_Dacast_Session_Control();
        return $session_control->check_session_status($user_id);
    }

    private function add_watermark($content, $user_id) {
        $user = get_userdata($user_id);
        $watermark_text = sprintf(
            'ID: %s - CPF: %s - %s - %s',
            $user_id,
            get_user_meta($user_id, 'cpf', true),
            $user->user_email,
            current_time('Y-m-d H:i:s')
        );

        // Adiciona div com marca d'água
        $watermark_html = sprintf(
            '<div class="secure-dacast-watermark" data-user="%s">%s</div>',
            esc_attr($user_id),
            esc_html($watermark_text)
        );

        return $watermark_html . $content;
    }

    private function wrap_protected_content($content) {
        $wrapped_content = sprintf(
            '<div class="secure-dacast-protected-content" data-protection="enabled">
                <div class="secure-dacast-content-wrapper">%s</div>
            </div>',
            $content
        );

        return $wrapped_content;
    }

    public function add_protection_headers() {
        if ($this->should_protect_page()) {
            // Adiciona headers de proteção
            header('X-Frame-Options: DENY');
            header('Content-Security-Policy: frame-ancestors \'none\'');
            header('X-Content-Type-Options: nosniff');
        }
    }

    public function enqueue_protection_scripts() {
        if ($this->should_protect_page()) {
            wp_enqueue_script(
                'secure-dacast-protection',
                SECURE_DACAST_PLUGIN_URL . 'public/js/content-protection.js',
                array('jquery'),
                SECURE_DACAST_VERSION,
                true
            );

            wp_localize_script('secure-dacast-protection', 'secureDacastProtection', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('secure_dacast_protection')
            ));

            wp_enqueue_style(
                'secure-dacast-protection',
                SECURE_DACAST_PLUGIN_URL . 'public/css/content-protection.css',
                array(),
                SECURE_DACAST_VERSION
            );
        }
    }

    private function get_login_form() {
        ob_start();
        ?>
        <div class="secure-dacast-login-required">
            <h3><?php _e('Acesso Restrito', 'secure-dacast-access'); ?></h3>
            <p><?php _e('Por favor, faça login para acessar este conteúdo.', 'secure-dacast-access'); ?></p>
            <?php wp_login_form(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_access_denied_message() {
        return sprintf(
            '<div class="secure-dacast-access-denied">
                <h3>%s</h3>
                <p>%s</p>
            </div>',
            __('Acesso Negado', 'secure-dacast-access'),
            __('Você não tem permissão para acessar este conteúdo.', 'secure-dacast-access')
        );
    }
}