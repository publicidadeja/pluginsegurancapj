<?php
if (!defined('WPINC')) {
    die;
}

// Define as configurações padrão
$default_settings = array(
    'session_timeout' => 30,
    'max_attempts' => 5,
    'block_duration' => 60,
    'watermark_enabled' => true,
    'screenshot_protection' => true,
    'watermark_text' => '{cpf} - {email} - {datetime}',
    'notify_access' => false,
    'notify_email' => get_option('admin_email')
);

// Obtém as configurações salvas e mescla com as padrão
$settings = wp_parse_args(
    get_option('secure_dacast_settings', array()),
    $default_settings
);
?>

<div class="wrap">
    <h1>Configurações do Secure Dacast</h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('secure_dacast_options');
        ?>

        <div class="secure-dacast-settings-container">
            <!-- Configurações Gerais -->
            <div class="secure-dacast-section">
                <h2>Configurações Gerais</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Tempo de Sessão (minutos)</th>
                        <td>
                            <input type="number" 
                                   name="secure_dacast_settings[session_timeout]" 
                                   value="<?php echo esc_attr($settings['session_timeout']); ?>"
                                   min="5" 
                                   max="1440">
                            <p class="description">Tempo máximo de uma sessão ativa (5-1440 minutos)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tentativas Máximas</th>
                        <td>
                            <input type="number" 
                                   name="secure_dacast_settings[max_attempts]" 
                                   value="<?php echo esc_attr($settings['max_attempts']); ?>"
                                   min="3" 
                                   max="10">
                            <p class="description">Número máximo de tentativas de acesso antes do bloqueio</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Duração do Bloqueio (minutos)</th>
                        <td>
                            <input type="number" 
                                   name="secure_dacast_settings[block_duration]" 
                                   value="<?php echo esc_attr($settings['block_duration']); ?>"
                                   min="15" 
                                   max="1440">
                            <p class="description">Tempo de bloqueio após exceder tentativas máximas</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Configurações de Proteção -->
            <div class="secure-dacast-section">
                <h2>Configurações de Proteção</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Proteções de Conteúdo</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_settings[watermark_enabled]" 
                                       value="1" 
                                       <?php checked($settings['watermark_enabled'], true); ?>>
                                Habilitar Marca d'água
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_settings[screenshot_protection]" 
                                       value="1" 
                                       <?php checked($settings['screenshot_protection'], true); ?>>
                                Proteção contra Captura de Tela
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Texto da Marca d'água</th>
                        <td>
                            <input type="text" 
                                   name="secure_dacast_settings[watermark_text]" 
                                   value="<?php echo esc_attr($settings['watermark_text']); ?>"
                                   class="large-text">
                            <p class="description">
                                Variáveis disponíveis: {cpf}, {email}, {datetime}, {ip}
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Configurações de Notificações -->
            <div class="secure-dacast-section">
                <h2>Configurações de Notificações</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Notificações de Acesso</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="secure_dacast_settings[notify_access]" 
                                       value="1" 
                                       <?php checked($settings['notify_access'], true); ?>>
                                Notificar tentativas de acesso suspeitas
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email para Notificações</th>
                        <td>
                            <input type="email" 
                                   name="secure_dacast_settings[notify_email]" 
                                   value="<?php echo esc_attr($settings['notify_email']); ?>"
                                   class="regular-text">
                            <p class="description">Email para receber notificações de segurança</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button('Salvar Configurações'); ?>
    </form>
</div>

<style>
.secure-dacast-settings-container {
    max-width: 900px;
}

.secure-dacast-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.secure-dacast-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
}

.description {
    font-style: italic;
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Atualiza campos dependentes
    function updateDependentFields() {
        var watermarkEnabled = $('input[name="secure_dacast_settings[watermark_enabled]"]').is(':checked');
        $('input[name="secure_dacast_settings[watermark_text]"]').closest('tr').toggle(watermarkEnabled);
    }

    // Inicializa e adiciona listener
    $('input[name="secure_dacast_settings[watermark_enabled]"]').on('change', updateDependentFields);
    updateDependentFields();
});
</script>