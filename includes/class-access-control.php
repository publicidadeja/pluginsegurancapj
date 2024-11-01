<?php
class Secure_Dacast_Access_Control {
    public function init() {
        add_action('wp_ajax_verify_dacast_access', array($this, 'verify_access'));
        add_action('wp_ajax_nopriv_verify_dacast_access', array($this, 'verify_access'));
        add_action('template_redirect', array($this, 'check_page_access'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));add_action('wp_ajax_end_dacast_session', array($this, 'end_session'));
    }
  
  public function end_session() {
    check_ajax_referer('end_session_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $session_token = sanitize_text_field($_POST['session_token']);
    $session_manager = new Secure_Dacast_Session_Manager();
    
    if ($session_manager->end_session($session_token)) {
        wp_send_json_success('Sessão encerrada com sucesso');
    } else {
        wp_send_json_error('Erro ao encerrar sessão');
    }
}

    public function enqueue_scripts() {
        if ($this->is_protected_page()) {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'jquery-mask',
                'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js',
                array('jquery'),
                '1.14.16',
                true
            );
        }
    }

    private function is_protected_page() {
        if (!is_page()) return false;
        $page_id = get_the_ID();
        $protected_pages = get_option('secure_dacast_protected_pages', array());
        return in_array($page_id, (array)$protected_pages);
    }

    public function check_page_access() {
        if (!$this->is_protected_page()) {
            return;
        }

        if (!$this->verify_access_cookie()) {
            // Buffer de saída para garantir que nada seja enviado antes
            ob_start();
            
            // Inclui o formulário de verificação
            include_once SECURE_DACAST_PLUGIN_DIR . 'public/templates/verification-form.php';
            
            // Pega o conteúdo do buffer e limpa
            $content = ob_get_clean();
            
            // Exibe o formulário
            echo $content;
            exit;
        }

        // Se chegou aqui, o usuário está autorizado
        add_filter('the_content', array($this, 'apply_content_protection'));
    }

    private function verify_access_cookie() {
        if (!isset($_COOKIE['secure_dacast_access'])) {
            return false;
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['secure_dacast_access_token']) || 
            $_SESSION['secure_dacast_access_token'] !== $_COOKIE['secure_dacast_access']) {
            return false;
        }

        return true;
    }

    public function verify_access() {
    check_ajax_referer('secure_dacast_verify', 'nonce');

    $cpf = sanitize_text_field($_POST['cpf']);
    $email = sanitize_email($_POST['email']);

    if (!self::validate_cpf($cpf)) {
        wp_send_json_error('CPF inválido');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'secure_dacast_authorized_users';
    
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE cpf = %s AND email = %s AND status = 1",
        $cpf,
        $email
    ));

    if ($user) {
        // Verifica sessões ativas
        $session_manager = new Secure_Dacast_Session_Manager();
        $active_session = $session_manager->get_active_session($user->id);
        
        if ($active_session) {
            // Notifica o admin sobre tentativa de acesso simultâneo
            $admin_email = get_option('admin_email');
            $subject = 'Tentativa de acesso simultâneo detectada';
            $message = sprintf(
                'Tentativa de acesso simultâneo:\nUsuário: %s\nCPF: %s\nIP: %s\nData/Hora: %s',
                $email,
                $cpf,
                $_SERVER['REMOTE_ADDR'],
                current_time('mysql')
            );
            wp_mail($admin_email, $subject, $message);
            
            wp_send_json_error('Este usuário já está com uma sessão ativa em outro dispositivo. Por motivos de segurança, apenas um acesso simultâneo é permitido.');
            return;
        }

        // Cria nova sessão
        $session_result = $session_manager->create_session($user->id);
        if (!$session_result['success']) {
            wp_send_json_error($session_result['message']);
            return;
        }
        $session_token = $session_result['session_token'];

        // Atualiza último acesso
        $wpdb->update(
            $table_name,
            array(
                'last_access' => current_time('mysql'),
                'last_ip' => $_SERVER['REMOTE_ADDR']
            ),
            array('id' => $user->id),
            array('%s', '%s'),
            array('%d')
        );

        // Define cookie de acesso com httponly e secure
        setcookie(
            'secure_dacast_access',
            $session_token,
            [
                'expires' => time() + (12 * HOUR_IN_SECONDS),
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );

        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['secure_dacast_access_token'] = $session_token;
        $_SESSION['secure_dacast_user_data'] = array(
            'id' => $user->id,
            'cpf' => $cpf,
            'email' => $email,
            'time' => time(),
            'ip' => $_SERVER['REMOTE_ADDR']
        );

        wp_send_json_success('Acesso autorizado');
    } else {
        wp_send_json_error('Usuário não autorizado ou dados incorretos');
    }
}

    public function apply_content_protection($content) {
    if (!session_id()) {
        session_start();
    }

    $user_data = isset($_SESSION['secure_dacast_user_data']) ? $_SESSION['secure_dacast_user_data'] : array();
    $cpf = isset($user_data['cpf']) ? $user_data['cpf'] : '';
    $email = isset($user_data['email']) ? $user_data['email'] : '';

    $watermark = sprintf(
        'CPF: %s - Email: %s - Data: %s',
        $cpf,
        $email,
        current_time('Y-m-d H:i:s')
    );

    $protected_content = sprintf(
        '<div class="secure-dacast-protected">
            <div class="watermark">%s</div>
            %s
        </div>',
        esc_html($watermark),
        $content
    );

    return $protected_content . $this->get_protection_scripts();
}

    private function get_protection_scripts() {
    ob_start();
    ?>
    <style>
    .secure-dacast-protected {
        position: relative;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 24px;
        opacity: 0.2;
        pointer-events: none;
        z-index: 9999;
        white-space: nowrap;
    }
    </style>
    <script>
    (function() {
        // Proteções contra cópia e print screen
        function addProtection() {
            // Desabilita clique direito
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            // Desabilita teclas de atalho
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey && e.keyCode === 80) || // Ctrl+P
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
                    (e.keyCode === 123) || // F12
                    (e.keyCode === 44)) { // Print Screen
                    e.preventDefault();
                    return false;
                }
            });

            // Desabilita print screen
            document.addEventListener('keyup', function(e) {
                if (e.keyCode === 44) {
                    e.preventDefault();
                    return false;
                }
            });

            // Desabilita drag and drop
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });

            // Desabilita seleção de texto
            document.addEventListener('selectstart', function(e) {
                if (!e.target.closest('input')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Adiciona marca d'água dinâmica
            setInterval(function() {
                document.querySelectorAll('.watermark').forEach(function(el) {
                    el.style.opacity = (Math.random() * 0.3 + 0.1).toString();
                });
            }, 3000);
        }

        // Adiciona proteções imediatamente e após carregamento do DOM
        addProtection();
        document.addEventListener('DOMContentLoaded', addProtection);
    })();
    </script>
    <?php
    return ob_get_clean();
}
    public static function validate_cpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }

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
}