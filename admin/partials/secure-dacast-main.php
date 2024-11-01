<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap">
    <h1>Secure Dacast - Controle de Acesso</h1>

    <div class="secure-dacast-admin-container">
        <div class="secure-dacast-section">
            <h2>Páginas Protegidas</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('secure_dacast_options');
                $protected_pages = get_option('secure_dacast_protected_pages', array());
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Selecione as páginas para proteger:</th>
                        <td>
                            <?php
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
                <?php submit_button('Salvar Páginas Protegidas'); ?>
            </form>
        </div>

        <div class="secure-dacast-section">
            <h2>Status do Sistema</h2>
            <?php
            global $wpdb;
            $users_table = $wpdb->prefix . 'secure_dacast_authorized_users';
            $total_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table");
            $active_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE status = 1");
            ?>
            <div class="secure-dacast-status">
                <p>Total de Usuários Autorizados: <strong><?php echo $total_users; ?></strong></p>
                <p>Usuários Ativos: <strong><?php echo $active_users; ?></strong></p>
                <p>Páginas Protegidas: <strong><?php echo count($protected_pages); ?></strong></p>
            </div>
        </div>
    </div>
</div>

<style>
.secure-dacast-admin-container {
    margin-top: 20px;
}
.secure-dacast-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.secure-dacast-status {
    background: #f8f9fa;
    padding: 15px;
    border-left: 4px solid #007cba;
}
</style>