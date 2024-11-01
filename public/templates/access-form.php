<!DOCTYPE html>
<html>
<head>
  
    <title>Acesso Protegido</title>
    <?php wp_head(); ?>
    <style>
        .secure-dacast-access-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .secure-dacast-access-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="secure-dacast-access-form">
        <h2>Acesso Protegido</h2>
        <?php if (!is_user_logged_in()): ?>
            <?php wp_login_form(); ?>
        <?php else: ?>
            <form id="cpf-verification-form">
                <div class="form-group">
                    <label for="cpf">Digite seu CPF:</label>
                    <input type="text" id="cpf" name="cpf" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary">Verificar</button>
                </div>
                <div class="error-message"></div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#cpf-verification-form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'verify_cpf',
                    cpf: $('#cpf').val(),
                    nonce: '<?php echo wp_create_nonce('secure_dacast_cpf_verify'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        $('.error-message').text(response.data);
                    }
                }
            });
        });
    });
    </script>
    <?php wp_footer(); ?>
</body>
</html>