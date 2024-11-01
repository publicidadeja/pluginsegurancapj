<?php
// Crie um novo arquivo: admin/partials/secure-dacast-admin-manage.php

if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap">
    <h1>Gerenciamento de Acesso Dacast</h1>
    
    <div class="nav-tab-wrapper">
        <a href="#pages" class="nav-tab nav-tab-active">Páginas Protegidas</a>
        <a href="#users" class="nav-tab">Usuários</a>
        <a href="#logs" class="nav-tab">Logs de Acesso</a>
    </div>

    <div class="tab-content">
        <!-- Páginas Protegidas -->
        <div id="pages" class="tab-pane active">
            <h2>Configurar Proteção de Páginas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th>Status</th>
                        <th>Proteções Ativas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $protected_pages = get_option('secure_dacast_protected_pages', array());
                    $pages = get_pages();
                    foreach ($pages as $page) {
                        $is_protected = in_array($page->ID, (array)$protected_pages);
                        ?>
                        <tr>
                            <td><?php echo esc_html($page->post_title); ?></td>
                            <td><?php echo $is_protected ? '<span class="protected">Protegida</span>' : 'Não protegida'; ?></td>
                            <td>
                                <?php if ($is_protected): ?>
                                    <ul>
                                        <li>✓ Autenticação CPF</li>
                                        <li>✓ DRM</li>
                                        <li>✓ Marca d'água</li>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button toggle-protection" data-page-id="<?php echo $page->ID; ?>">
                                    <?php echo $is_protected ? 'Remover Proteção' : 'Proteger'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.toggle-protection').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var pageId = button.data('page-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_page_protection',
                page_id: pageId,
                nonce: '<?php echo wp_create_nonce('secure_dacast_toggle'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
});
</script>