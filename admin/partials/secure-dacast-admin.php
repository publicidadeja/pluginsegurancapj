<?php
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>Secure Dacast</h1>

    <div class="secure-dacast-admin-dashboard">
        <div class="secure-dacast-card">
            <h2>Visão Geral</h2>
            <?php
            global $wpdb;
            $users_table = $wpdb->prefix . 'secure_dacast_authorized_users';
            $sessions_table = $wpdb->prefix . 'secure_dacast_sessions';

            // Contagem de usuários ativos
            $total_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE status = 1");

            // Contagem de sessões ativas
            $timeout = get_option('secure_dacast_settings')['session_timeout'] ?? 30;
            $expiry_time = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));
            $active_sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table WHERE last_activity > %s",
                $expiry_time
            ));

            // Páginas protegidas
            $protected_pages = get_option('secure_dacast_protected_pages', array());
            $total_protected = count($protected_pages);
            ?>

            <div class="secure-dacast-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($total_users); ?></span>
                    <span class="stat-label">Usuários Autorizados</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($active_sessions); ?></span>
                    <span class="stat-label">Sessões Ativas</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($total_protected); ?></span>
                    <span class="stat-label">Páginas Protegidas</span>
                </div>
            </div>
        </div>

        <div class="secure-dacast-card">
            <h2>Links Rápidos</h2>
            <div class="secure-dacast-quick-links">
                <a href="<?php echo admin_url('admin.php?page=secure-dacast-users'); ?>" class="button button-primary">
                    Gerenciar Usuários
                </a>
                <a href="<?php echo admin_url('admin.php?page=secure-dacast-sessions'); ?>" class="button button-secondary">
                    Ver Sessões Ativas
                </a>
                <a href="<?php echo admin_url('admin.php?page=secure-dacast-settings'); ?>" class="button button-secondary">
                    Configurações
                </a>
            </div>
        </div>

        <?php if ($active_sessions > 0) : ?>
            <div class="secure-dacast-card">
                <h2>Últimas Sessões Ativas</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>CPF</th>
                            <th>Email</th>
                            <th>IP</th>
                            <th>Última Atividade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_sessions = $wpdb->get_results($wpdb->prepare("
                            SELECT s.*, u.cpf, u.email 
                            FROM $sessions_table s
                            JOIN $users_table u ON s.user_id = u.id
                            WHERE s.last_activity > %s
                            ORDER BY s.last_activity DESC
                            LIMIT 5
                        ", $expiry_time));

                        foreach ($recent_sessions as $session) :
                        ?>
                            <tr>
                                <td><?php echo esc_html($session->cpf); ?></td>
                                <td><?php echo esc_html($session->email); ?></td>
                                <td><?php echo esc_html($session->ip_address); ?></td>
                                <td><?php echo esc_html(human_time_diff(strtotime($session->last_activity), current_time('timestamp'))) . ' atrás'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.secure-dacast-admin-dashboard {
    margin: 20px 0;
}

.secure-dacast-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 20px;
}

.secure-dacast-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.secure-dacast-stats {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}

.stat-box {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
    flex: 1;
    margin: 0 10px;
}

.stat-box:first-child {
    margin-left: 0;
}

.stat-box:last-child {
    margin-right: 0;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.stat-label {
    display: block;
    margin-top: 5px;
    color: #646970;
}

.secure-dacast-quick-links {
    display: flex;
    gap: 10px;
}

.secure-dacast-quick-links .button {
    flex: 1;
    text-align: center;
    padding: 8px 12px;
    height: auto;
}
</style>

<?php
if (!defined('WPINC')) {
    die;
}

// Define as configurações padrão
$default_settings = array(
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'watermark_enabled' => true,
    'screenshot_protection' => true,
    'concurrent_sessions_allowed' => false
);

// Obtém as configurações salvas ou usa as padrão se não existirem
$security_settings = wp_parse_args(
    get_option('secure_dacast_security_settings', array()),
    $default_settings
);
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="secure-dacast-admin-container">
        <form method="post" action="options.php">
            <?php
            settings_fields('secure_dacast_options');
            do_settings_sections('secure_dacast_options');
            ?>

            <div class="secure-dacast-section">
                <h2>Páginas Protegidas</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Selecione as páginas para proteger:</th>
                        <td>
                            <?php
                            $protected_pages = get_option('secure_dacast_protected_pages', array());
                            $pages = get_pages();
                            foreach ($pages as $page) {
                                printf(
                                    '<label><input type="checkbox" name="secure_dacast_protected_pages[]" value="%d" %s> %s</label><br>',
                                    $page->ID,
                                    in_array($page->ID, (array)$protected_pages) ? 'checked' : '',
                                    esc_html($page->post_title)
                                );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="secure-dacast-section">
                <h2>Configurações de Segurança</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Tempo máximo de sessão (minutos):</th>
                        <td>
                            <input type="number" 
                                   name="secure_dacast_security_settings[session_timeout]" 
                                   value="<?php echo esc_attr($security_settings['session_timeout']); ?>"
                                   min="5" 
                                   max="120">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tentativas máximas de login:</th>
                        <td>
                            <input type="number" 
                                   name="secure_dacast_security_settings[max_login_attempts]"
                                   value="<?php echo esc_attr($security_settings['max_login_attempts']); ?>"
                                   min="3" 
                                   max="10">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Proteções:</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_security_settings[watermark_enabled]"
                                       value="1" 
                                       <?php checked(isset($security_settings['watermark_enabled']) ? $security_settings['watermark_enabled'] : false); ?>>
                                Habilitar marca d'água
                            </label><br>
                            
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_security_settings[screenshot_protection]"
                                       value="1" 
                                       <?php checked(isset($security_settings['screenshot_protection']) ? $security_settings['screenshot_protection'] : false); ?>>
                                Proteção contra capturas de tela
                            </label><br>
                            
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_security_settings[concurrent_sessions_allowed]"
                                       value="1" 
                                       <?php checked(isset($security_settings['concurrent_sessions_allowed']) ? $security_settings['concurrent_sessions_allowed'] : false); ?>>
                                Permitir sessões simultâneas
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Salvar Configurações'); ?>
        </form>
    </div>
</div>