<?php
if (!defined('WPINC')) {
    die;
}

$session_manager = new Secure_Dacast_Session_Manager();
$active_sessions = $session_manager->get_active_sessions();
?>

<div class="wrap">
    <h1>Sessões Ativas</h1>
    
    <div class="secure-dacast-sessions-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>CPF</th>
                    <th>Email</th>
                    <th>IP</th>
                    <th>Navegador</th>
                    <th>Última Atividade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($active_sessions)) : ?>
                    <tr>
                        <td colspan="6">Nenhuma sessão ativa no momento.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($active_sessions as $session) : ?>
                        <tr>
                            <td><?php echo esc_html($session->cpf); ?></td>
                            <td><?php echo esc_html($session->email); ?></td>
                            <td><?php echo esc_html($session->ip_address); ?></td>
                            <td><?php echo esc_html($session->user_agent); ?></td>
                            <td><?php echo esc_html(
                                human_time_diff(
                                    strtotime($session->last_activity),
                                    current_time('timestamp')
                                ) . ' atrás'
                            ); ?></td>
                            <td>
                                <button class="button button-small end-session" 
                                        data-session="<?php echo esc_attr($session->session_token); ?>">
                                    Encerrar Sessão
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.end-session').on('click', function() {
        var button = $(this);
        var session_token = button.data('session');
        
        if (confirm('Tem certeza que deseja encerrar esta sessão?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'end_dacast_session',
                    session_token: session_token,
                    nonce: '<?php echo wp_create_nonce('end_session_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            if ($('tbody tr').length === 0) {
                                $('tbody').html('<tr><td colspan="6">Nenhuma sessão ativa no momento.</td></tr>');
                            }
                        });
                    } else {
                        alert('Erro ao encerrar sessão: ' + response.data);
                    }
                }
            });
        }
    });
});
</script>