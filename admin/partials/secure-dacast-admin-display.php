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