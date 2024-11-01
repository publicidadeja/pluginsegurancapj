<!DOCTYPE html>
<html>
<head>
    <title>Acesso Restrito</title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .secure-dacast-verification {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            width: 400px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff; /* Blue */
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue */
        }

        #verification-message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="secure-dacast-verification">
        <h2>Acesso Restrito</h2>
        <form id="dacast-access-form">
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <button type="button" id="verify-access">Verificar Acesso</button>
            </div>
            <div id="verification-message"></div>
        </form>
    </div>

    <?php
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-mask');
    wp_print_scripts('jquery');
    wp_print_scripts('jquery-mask');
    ?>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#cpf').mask('000.000.000-00');

        $('#verify-access').on('click', function() {
            var $message = $('#verification-message');
            var cpf = $('#cpf').val();
            var email = $('#email').val();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'verify_dacast_access',
                    cpf: cpf,
                    email: email,
                    nonce: '<?php echo wp_create_nonce('secure_dacast_verify'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('error').addClass('success')
                            .text('Acesso autorizado! Redirecionando...')
                            .show();
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $message.removeClass('success').addClass('error')
                            .text(response.data)
                            .show();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error')
                        .text('Erro ao verificar acesso. Tente novamente.')
                        .show();
                }
            });
        });
    });
    </script>
    <?php wp_footer(); ?>
</body>
</html>