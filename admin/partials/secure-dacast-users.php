<?php
if (!defined('WPINC')) {
    die;
}

// Processar formulário de adição de usuário
if (isset($_POST['add_user']) && check_admin_referer('secure_dacast_add_user')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'secure_dacast_authorized_users';
    
    $cpf = sanitize_text_field($_POST['cpf']);
    $email = sanitize_email($_POST['email']);
    
    // Validar CPF
    $access_control = new Secure_Dacast_Access_Control();
if (!$access_control::validate_cpf($cpf)) {
        echo '<div class="error"><p>CPF inválido!</p></div>';
    } else {
        // Tentar inserir usuário
        $result = $wpdb->insert(
            $table_name,
            array(
                'cpf' => $cpf,
                'email' => $email,
                'status' => 1
            ),
            array('%s', '%s', '%d')
        );
        
        if ($result === false) {
            echo '<div class="error"><p>Erro ao adicionar usuário. CPF ou email já cadastrado.</p></div>';
        } else {
            echo '<div class="updated"><p>Usuário autorizado adicionado com sucesso!</p></div>';
        }
    }
}

// Processar remoção de usuário
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (check_admin_referer('delete_user_' . $_GET['id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'secure_dacast_authorized_users';
        
        $wpdb->delete(
            $table_name,
            array('id' => $_GET['id']),
            array('%d')
        );
        
        echo '<div class="updated"><p>Usuário removido com sucesso!</p></div>';
    }
}
?>

<div class="wrap">
    <h1>Gerenciar Usuários Autorizados</h1>
    
    <!-- Formulário de adição de usuário -->
    <div class="card">
        <h2>Adicionar Novo Usuário Autorizado</h2>
        <form method="post" action="">
            <?php wp_nonce_field('secure_dacast_add_user'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="cpf">CPF</label></th>
                    <td>
                        <input type="text" name="cpf" id="cpf" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="email">Email</label></th>
                    <td>
                        <input type="email" name="email" id="email" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="add_user" class="button button-primary" value="Adicionar Usuário">
            </p>
        </form>
    </div>

    <!-- Lista de usuários autorizados -->
    <div class="card">
        <h2>Usuários Autorizados</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'secure_dacast_authorized_users';
        $users = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>CPF</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Último Acesso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo esc_html($user->cpf); ?></td>
                    <td><?php echo esc_html($user->email); ?></td>
                    <td><?php echo $user->status ? 'Ativo' : 'Inativo'; ?></td>
                    <td><?php echo $user->last_access ? esc_html($user->last_access) : 'Nunca'; ?></td>
                    <td>
                        <?php
                        $delete_url = wp_nonce_url(
                            add_query_arg(
                                array('action' => 'delete', 'id' => $user->id),
                                admin_url('admin.php?page=secure-dacast-users')
                            ),
                            'delete_user_' . $user->id
                        );
                        ?>
                        <a href="<?php echo $delete_url; ?>" class="button button-small" 
                           onclick="return confirm('Tem certeza que deseja remover este usuário?');">
                            Remover
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Máscara para CPF
    $('#cpf').mask('000.000.000-00');
});
</script>