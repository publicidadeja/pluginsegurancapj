<?php
class Secure_Dacast_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Hooks para área administrativa
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Hook para front-end
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Outros hooks
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Ajax handlers
        add_action('wp_ajax_save_secure_dacast_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_add_secure_dacast_user', array($this, 'handle_add_user'));
        add_action('wp_ajax_remove_secure_dacast_user', array($this, 'handle_remove_user'));
    }

    public function enqueue_admin_assets() {
        // Estilos Admin
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'css/admin-style.css',
            array(),
            $this->version,
            'all'
        );

        // Scripts Admin
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'js/admin-script.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name . '-admin',
            'secureDacastAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('secure_dacast_admin'),
                'messages' => array(
                    'success' => __('Operação realizada com sucesso!', 'secure-dacast'),
                    'error' => __('Ocorreu um erro. Tente novamente.', 'secure-dacast')
                )
            )
        );
    }

    public function enqueue_public_assets() {
        if ($this->is_protected_page()) {
            wp_enqueue_style(
                $this->plugin_name . '-public',
                plugin_dir_url(dirname(__FILE__)) . 'public/css/content-protection.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    private function is_protected_page() {
        if (!is_page()) return false;
        
        $protected_pages = get_option('secure_dacast_protected_pages', array());
        return in_array(get_the_ID(), (array)$protected_pages);
    }

    public function add_plugin_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Secure Dacast', 'secure-dacast'),
            __('Secure Dacast', 'secure-dacast'),
            'manage_options',
            'secure-dacast',
            array($this, 'display_plugin_setup_page'),
            'dashicons-lock',
            65
        );

        // Submenus
        $submenus = array(
            'settings' => array(
                'title' => __('Configurações', 'secure-dacast'),
                'capability' => 'manage_options',
                'function' => 'display_plugin_settings_page'
            ),
            'logs' => array(
                'title' => __('Logs', 'secure-dacast'),
                'capability' => 'manage_options',
                'function' => 'display_plugin_logs_page'
            )
        );

        foreach ($submenus as $slug => $submenu) {
            add_submenu_page(
                'secure-dacast',
                $submenu['title'],
                $submenu['title'],
                $submenu['capability'],
                'secure-dacast-' . $slug,
                array($this, $submenu['function'])
            );
        }
    }

    public function register_settings() {
        $settings = array(
            'secure_dacast_protected_pages',
            'secure_dacast_security_settings',
            'secure_dacast_api_settings'
        );

        foreach ($settings as $setting) {
            register_setting('secure_dacast_options', $setting, array(
                'sanitize_callback' => array($this, 'sanitize_' . $setting)
            ));
        }
    }

    // Páginas de administração
    public function display_plugin_setup_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'secure-dacast'));
        }
        include_once 'partials/secure-dacast-admin-display.php';
    }

    public function display_plugin_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'secure-dacast'));
        }
        include_once 'partials/secure-dacast-admin-settings.php';
    }

    public function display_plugin_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'secure-dacast'));
        }
        include_once 'partials/secure-dacast-admin-logs.php';
    }

    // Handlers AJAX
    public function handle_save_settings() {
        check_ajax_referer('secure_dacast_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        // Implementar lógica de salvamento
        wp_send_json_success('Configurações salvas');
    }

    public function handle_add_user() {
        check_ajax_referer('secure_dacast_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        // Implementar lógica de adição de usuário
        wp_send_json_success('Usuário adicionado');
    }

    public function handle_remove_user() {
        check_ajax_referer('secure_dacast_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        // Implementar lógica de remoção de usuário
        wp_send_json_success('Usuário removido');
    }

    // Funções de sanitização
    public function sanitize_secure_dacast_protected_pages($input) {
        return array_map('absint', (array)$input);
    }

    public function sanitize_secure_dacast_security_settings($input) {
        return array_map('sanitize_text_field', (array)$input);
    }

    public function sanitize_secure_dacast_api_settings($input) {
        return array_map('sanitize_text_field', (array)$input);
    }
}