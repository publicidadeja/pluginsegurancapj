(function($) {
    'use strict';

    // Inicialização quando o documento estiver pronto
    $(document).ready(function() {
        initSecureDacast();
    });

    function initSecureDacast() {
        bindFormSubmission();
        initTooltips();
        handleVisibilityToggles();
    }

    // Gerenciamento de submissão de formulários
    function bindFormSubmission() {
        $('.secure-dacast-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const $message = $form.find('.secure-dacast-message');
            
            // Desabilita o botão durante o processo
            $submitButton.prop('disabled', true);
            
            // Coleta dados do formulário
            const formData = new FormData($form[0]);
            formData.append('action', 'secure_dacast_process_form');
            formData.append('nonce', secure_dacast_vars.nonce);

            // Requisição AJAX
            $.ajax({
                url: secure_dacast_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showMessage($message, response.data.message, 'success');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        }
                    } else {
                        showMessage($message, response.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage($message, 'Ocorreu um erro. Tente novamente.', 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        });
    }

    // Exibição de mensagens
    function showMessage($element, message, type) {
        $element
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();

        setTimeout(function() {
            $element.fadeOut();
        }, 5000);
    }

    // Inicialização de tooltips
    function initTooltips() {
        $('.secure-dacast-tooltip').tooltip({
            position: {
                my: "center bottom-20",
                at: "center top",
                using: function(position, feedback) {
                    $(this).css(position);
                    $("<div>")
                        .addClass("arrow")
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
    }

    // Gerenciamento de toggles de visibilidade
    function handleVisibilityToggles() {
        $('.secure-dacast-toggle').on('click', function(e) {
            e.preventDefault();
            
            const targetId = $(this).data('target');
            const $target = $('#' + targetId);
            
            if ($target.length) {
                $target.slideToggle();
                $(this).toggleClass('active');
            }
        });
    }

    // Validação de formulários
    function validateForm($form) {
        let isValid = true;
        const $requiredFields = $form.find('[required]');

        $requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        return isValid;
    }

    // Função de utilidade para sanitização básica
    function sanitizeInput(input) {
        return $('<div>').text(input).html();
    }

    // Exporta funções públicas se necessário
    window.secureDacast = {
        showMessage: showMessage,
        validateForm: validateForm
    };

})(jQuery);