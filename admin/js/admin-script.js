(function($) {
    'use strict';

    // Quando o documento estiver pronto
    $(document).ready(function() {
        
        // Inicialização de máscaras e validações
        if($('#cpf_field').length) {
            $('#cpf_field').mask('000.000.000-00');
        }

        // Função para validar CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]/g, '');
            
            if(cpf.length !== 11) return false;
            
            // Validação do CPF
            let soma = 0;
            let resto;
            
            if(cpf === '00000000000') return false;
            
            for(let i = 1; i <= 9; i++) {
                soma = soma + parseInt(cpf.substring(i-1, i)) * (11 - i);
            }
            
            resto = (soma * 10) % 11;
            if((resto === 10) || (resto === 11)) resto = 0;
            if(resto !== parseInt(cpf.substring(9, 10))) return false;
            
            soma = 0;
            for(let i = 1; i <= 10; i++) {
                soma = soma + parseInt(cpf.substring(i-1, i)) * (12 - i);
            }
            
            resto = (soma * 10) % 11;
            if((resto === 10) || (resto === 11)) resto = 0;
            if(resto !== parseInt(cpf.substring(10, 11))) return false;
            
            return true;
        }

        // Função para validar email
        function validarEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Função para mostrar mensagens
        function mostrarMensagem(mensagem, tipo) {
            const $mensagem = $('<div>')
                .addClass('notice')
                .addClass(tipo === 'erro' ? 'notice-error' : 'notice-success')
                .addClass('is-dismissible')
                .html(`<p>${mensagem}</p>`);

            $('.wrap h1').after($mensagem);

            // Auto-remover após 5 segundos
            setTimeout(function() {
                $mensagem.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Handler para salvar configurações
        $('#secure-dacast-settings').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');

            // Desabilitar botão durante o processo
            $submitButton.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_secure_dacast_settings',
                    nonce: $('#secure_dacast_nonce').val(),
                    formData: $form.serialize()
                },
                success: function(response) {
                    if(response.success) {
                        mostrarMensagem('Configurações salvas com sucesso!', 'sucesso');
                    } else {
                        mostrarMensagem(response.data || 'Erro ao salvar configurações.', 'erro');
                    }
                },
                error: function() {
                    mostrarMensagem('Erro de conexão. Tente novamente.', 'erro');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        });

        // Handler para adicionar novo usuário
        $('#add-user-form').on('submit', function(e) {
            e.preventDefault();

            const cpf = $('#cpf_field').val();
            const email = $('#email_field').val();

            // Validações
            if(!validarCPF(cpf)) {
                mostrarMensagem('CPF inválido', 'erro');
                return;
            }

            if(!validarEmail(email)) {
                mostrarMensagem('Email inválido', 'erro');
                return;
            }

            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');

            $submitButton.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'add_secure_dacast_user',
                    nonce: $('#secure_dacast_user_nonce').val(),
                    cpf: cpf,
                    email: email
                },
                success: function(response) {
                    if(response.success) {
                        mostrarMensagem('Usuário adicionado com sucesso!', 'sucesso');
                        $form[0].reset();
                        // Atualizar lista de usuários se existir
                        if(typeof atualizarListaUsuarios === 'function') {
                            atualizarListaUsuarios();
                        }
                    } else {
                        mostrarMensagem(response.data || 'Erro ao adicionar usuário.', 'erro');
                    }
                },
                error: function() {
                    mostrarMensagem('Erro de conexão. Tente novamente.', 'erro');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        });

        // Handler para remover usuário
        $('.remove-user').on('click', function(e) {
            e.preventDefault();
            
            if(!confirm('Tem certeza que deseja remover este usuário?')) {
                return;
            }

            const userId = $(this).data('user-id');
            const $button = $(this);

            $button.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'remove_secure_dacast_user',
                    nonce: $('#secure_dacast_remove_nonce').val(),
                    user_id: userId
                },
                success: function(response) {
                    if(response.success) {
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                        mostrarMensagem('Usuário removido com sucesso!', 'sucesso');
                    } else {
                        mostrarMensagem(response.data || 'Erro ao remover usuário.', 'erro');
                    }
                },
                error: function() {
                    mostrarMensagem('Erro de conexão. Tente novamente.', 'erro');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);