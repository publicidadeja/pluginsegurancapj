(function($) {
    'use strict';

    // Função para verificar status da sessão
    function checkSessionStatus() {
        $.ajax({
            url: secureDacastHeartbeat.ajax_url,
            type: 'POST',
            data: {
                action: 'secure_dacast_check_session',
                nonce: secureDacastHeartbeat.nonce,
                session_id: window.secureDacastSessionId
            },
            success: function(response) {
                if (!response.success) {
                    // Sessão inválida - redireciona para login
                    window.location.href = secureDacastHeartbeat.login_url;
                }
            }
        });
    }

    // Verifica a sessão a cada 10 segundos
    setInterval(checkSessionStatus, 10000);

    // Inicializa heartbeat
    function initHeartbeat() {
        window.secureDacastSessionId = Math.random().toString(36).substring(2);
        
        setInterval(function() {
            $.ajax({
                url: secureDacastHeartbeat.ajax_url,
                type: 'POST',
                data: {
                    action: 'secure_dacast_heartbeat',
                    nonce: secureDacastHeartbeat.nonce,
                    session_id: window.secureDacastSessionId
                },
                success: function(response) {
                    if (!response.success) {
                        window.location.href = secureDacastHeartbeat.login_url;
                    }
                }
            });
        }, secureDacastHeartbeat.interval);
    }

    initHeartbeat();
})(jQuery);