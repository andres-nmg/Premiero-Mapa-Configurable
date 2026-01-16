<?php
/*
Plugin Name: BSMap - Mapa de Tiendas Beanstalk Foods
Description: Muestra un mapa interactivo con tiendas, distribuidores y venta online.
Version: 2.0
Author: Premiero
Author URI: https://www.premiero.es
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   1) CPT: Tiendas
   ============================================================ */
function bsmap_register_cpt() {
    $labels = array(
        'name'               => 'Tiendas',
        'singular_name'      => 'Tienda',
        'menu_name'          => 'Mapa Tiendas',
        'add_new'            => 'Añadir Tienda',
        'add_new_item'       => 'Añadir nueva tienda',
        'edit_item'          => 'Editar tienda',
        'new_item'           => 'Nueva tienda',
        'view_item'          => 'Ver tienda',
        'search_items'       => 'Buscar tienda',
        'not_found'          => 'No se encontraron tiendas',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-location',
        'supports'      => array('title'),
    );

    register_post_type('bsmap_tienda', $args);
}
add_action('init', 'bsmap_register_cpt');

/* ============================================================
   1.1) CPT: Promociones
   ============================================================ */
function bsmap_register_promo_cpt() {
    $labels = array(
        'name'               => 'Promociones',
        'singular_name'      => 'Promoción',
        'menu_name'          => 'Promociones',
        'add_new'            => 'Añadir Promoción',
        'add_new_item'       => 'Añadir nueva promoción',
        'edit_item'          => 'Editar promoción',
        'new_item'           => 'Nueva promoción',
        'view_item'          => 'Ver promoción',
        'search_items'       => 'Buscar promoción',
        'not_found'          => 'No se encontraron promociones',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-megaphone',
        'supports'      => array('title', 'thumbnail'),
    );

    register_post_type('bsmap_promo', $args);
}
add_action('init', 'bsmap_register_promo_cpt');

/* ============================================================
   2) Metabox: campos tienda
   ============================================================ */
function bsmap_add_meta_box() {
    add_meta_box('bsmap_info', 'Datos de la Tienda', 'bsmap_render_meta_box', 'bsmap_tienda', 'normal', 'default');
}
add_action('add_meta_boxes', 'bsmap_add_meta_box');

function bsmap_add_promo_meta_box() {
    add_meta_box('bsmap_promo_info', 'Datos de la Promoción', 'bsmap_render_promo_meta_box', 'bsmap_promo', 'normal', 'default');
}
add_action('add_meta_boxes', 'bsmap_add_promo_meta_box');

function bsmap_render_promo_meta_box($post) {
    $active    = get_post_meta($post->ID, 'bsmap_promo_active', true);
    $image_id  = get_post_meta($post->ID, 'bsmap_promo_image_id', true);
    $start     = get_post_meta($post->ID, 'bsmap_promo_start', true);
    $end       = get_post_meta($post->ID, 'bsmap_promo_end', true);
    $thumb_id  = get_post_thumbnail_id($post->ID);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    if (!$image_url && $thumb_id) {
        $image_url = wp_get_attachment_image_url($thumb_id, 'medium');
    }
    ?>
    <table class="form-table">
        <tr>
            <th><label>Activa</label></th>
            <td>
                <label>
                    <input type="checkbox" name="bsmap_promo_active" value="1" <?php checked($active, '1'); ?>>
                    Disponible
                </label>
            </td>
        </tr>
        <tr>
            <th><label>Imagen de promoción</label></th>
            <td>
                <div class="bsmap-promo-image-wrap">
                    <?php if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:160px;height:auto;display:block;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden" name="bsmap_promo_image_id" value="<?php echo esc_attr($image_id); ?>">
                    <button type="button" class="button bsmap-promo-image-upload">Subir/Seleccionar imagen</button>
                    <button type="button" class="button bsmap-promo-image-remove" <?php disabled(!$image_id); ?>>Quitar imagen</button>
                </div>
            </td>
        </tr>
        <tr>
            <th><label>Fecha inicio</label></th>
            <td><input type="date" name="bsmap_promo_start" value="<?php echo esc_attr($start); ?>"></td>
        </tr>
        <tr>
            <th><label>Fecha fin</label></th>
            <td><input type="date" name="bsmap_promo_end" value="<?php echo esc_attr($end); ?>"></td>
        </tr>
    </table>
    <?php
}

function bsmap_render_meta_box($post) {
    $direccion = get_post_meta($post->ID, 'bsmap_direccion', true);
    $lat       = get_post_meta($post->ID, 'bsmap_lat', true);
    $lng       = get_post_meta($post->ID, 'bsmap_lng', true);
    $categoria = get_post_meta($post->ID, 'bsmap_categoria', true);
    $web       = get_post_meta($post->ID, 'bsmap_web', true);
    $insta     = get_post_meta($post->ID, 'bsmap_instagram', true);
    $zona      = get_post_meta($post->ID, 'bsmap_zona', true); // NUEVO
    $veganuary = get_post_meta($post->ID, 'bsmap_veganuary_2x1', true);
    $promo_id  = get_post_meta($post->ID, 'bsmap_promo_id', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label>Dirección</label></th>
            <td><input type="text" name="bsmap_direccion" value="<?php echo esc_attr($direccion); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label>Latitud</label></th>
            <td><input type="text" name="bsmap_lat" value="<?php echo esc_attr($lat); ?>" class="regular-text" placeholder="40.4168"></td>
        </tr>
        <tr>
            <th><label>Longitud</label></th>
            <td><input type="text" name="bsmap_lng" value="<?php echo esc_attr($lng); ?>" class="regular-text" placeholder="-3.7038"></td>
        </tr>
        <tr>
            <th><label>Categoría</label></th>
            <td>
                <select name="bsmap_categoria">
                    <option value="">Seleccionar...</option>
                    <option value="distribuidor" <?php selected($categoria, 'distribuidor'); ?>>Distribuidor</option>
                    <option value="online" <?php selected($categoria, 'online'); ?>>Venta Online</option>
                    <option value="fisica" <?php selected($categoria, 'fisica'); ?>>Tienda Física</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label>Web</label></th>
            <td><input type="url" name="bsmap_web" value="<?php echo esc_attr($web); ?>" class="regular-text" placeholder="https://..."></td>
        </tr>
        <tr>
            <th><label>Instagram</label></th>
            <td><input type="url" name="bsmap_instagram" value="<?php echo esc_attr($insta); ?>" class="regular-text" placeholder="https://instagram.com/..."></td>
        </tr>
        <tr>
            <th><label>Zona de actuación / Envío</label></th>
            <td><input type="text" name="bsmap_zona" value="<?php echo esc_attr($zona); ?>" class="regular-text" placeholder="Ej.: España peninsular, Europa, Cataluña..."></td>
        </tr>
        <tr>
            <th><label>Promoción</label></th>
            <td>
                <label>
                    <input type="checkbox" name="bsmap_veganuary_2x1" value="1" <?php checked($veganuary, '1'); ?>>
                    Disponible
                </label>
            </td>
        </tr>
        <tr>
            <th><label>Promoción activa</label></th>
            <td>
                <?php
                $active_promos = bsmap_get_active_promos();
                if (empty($active_promos)) {
                    echo '<select class="regular-text" disabled><option>No hay promociones activas</option></select>';
                } else {
                    echo '<select name="bsmap_promo_id" class="regular-text">';
                    echo '<option value="">Seleccionar...</option>';
                    foreach ($active_promos as $promo) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($promo['id']),
                            selected((string)$promo_id, (string)$promo['id'], false),
                            esc_html($promo['name'])
                        );
                    }
                    echo '</select>';
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}

function bsmap_save_meta_box($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    $post_type = get_post_type($post_id);
    if ( $post_type !== 'bsmap_tienda' ) return;

    $fields = [
        'bsmap_direccion','bsmap_lat','bsmap_lng',
        'bsmap_categoria','bsmap_web','bsmap_instagram','bsmap_zona'
    ];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    $veganuary = isset($_POST['bsmap_veganuary_2x1']) ? '1' : '0';
    update_post_meta($post_id, 'bsmap_veganuary_2x1', $veganuary);
    if (isset($_POST['bsmap_promo_id'])) {
        $promo_id = absint($_POST['bsmap_promo_id']);
        update_post_meta($post_id, 'bsmap_promo_id', $promo_id);
    }
}
add_action('save_post', 'bsmap_save_meta_box');

function bsmap_save_promo_meta_box($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( get_post_type($post_id) !== 'bsmap_promo' ) return;

    $active = isset($_POST['bsmap_promo_active']) ? '1' : '0';
    update_post_meta($post_id, 'bsmap_promo_active', $active);

    if (isset($_POST['bsmap_promo_image_id'])) {
        update_post_meta($post_id, 'bsmap_promo_image_id', absint($_POST['bsmap_promo_image_id']));
    }
    if (isset($_POST['bsmap_promo_start'])) {
        update_post_meta($post_id, 'bsmap_promo_start', sanitize_text_field($_POST['bsmap_promo_start']));
    }
    if (isset($_POST['bsmap_promo_end'])) {
        update_post_meta($post_id, 'bsmap_promo_end', sanitize_text_field($_POST['bsmap_promo_end']));
    }
}
add_action('save_post', 'bsmap_save_promo_meta_box');

function bsmap_is_promo_active($promo_id) {
    $active = get_post_meta($promo_id, 'bsmap_promo_active', true) === '1';
    if (!$active) return false;
    $start = get_post_meta($promo_id, 'bsmap_promo_start', true);
    $end   = get_post_meta($promo_id, 'bsmap_promo_end', true);
    $today = current_time('Y-m-d');
    if ($start && $today < $start) return false;
    if ($end && $today > $end) return false;
    return true;
}

function bsmap_get_active_promos() {
    $posts = get_posts([
        'post_type'   => 'bsmap_promo',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);
    $promos = [];
    foreach ($posts as $p) {
        if (!bsmap_is_promo_active($p->ID)) continue;
        $image_id = get_post_meta($p->ID, 'bsmap_promo_image_id', true);
        if (!$image_id) {
            $image_id = get_post_thumbnail_id($p->ID);
        }
        $promos[] = [
            'id'    => $p->ID,
            'name'  => $p->post_title,
            'image' => $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '',
        ];
    }
    return $promos;
}

function bsmap_promo_admin_assets($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'bsmap_promo') return;
    wp_enqueue_media();
    $base = plugin_dir_url(__FILE__) . 'assets/';
    wp_enqueue_script('bsmap-promo-admin', $base . 'bsmap-promo-admin.js', ['jquery'], '1.0', true);
}
add_action('admin_enqueue_scripts', 'bsmap_promo_admin_assets');

/* ============================================================
   2.1) Columnas en listado de tiendas (admin)
   ============================================================ */
function bsmap_add_admin_columns($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        if ($key === 'date') {
            $new['bsmap_promo'] = 'Promoción';
        }
        $new[$key] = $label;
    }
    if (!isset($new['bsmap_promo'])) {
        $new['bsmap_promo'] = 'Promoción';
    }
    return $new;
}
add_filter('manage_bsmap_tienda_posts_columns', 'bsmap_add_admin_columns');

function bsmap_render_admin_columns($column, $post_id) {
    if ($column !== 'bsmap_promo') return;
    $has_promo = get_post_meta($post_id, 'bsmap_veganuary_2x1', true) === '1';
    $promo_id = absint(get_post_meta($post_id, 'bsmap_promo_id', true));
    $promo_title = $promo_id ? get_the_title($promo_id) : '';
    $display = ($has_promo && $promo_title !== '') ? $promo_title : '—';
    printf(
        '<span class="bsmap-promo-data" data-promo-enabled="%s" data-promo-id="%s">%s</span>',
        esc_attr($has_promo ? '1' : '0'),
        esc_attr($promo_id),
        esc_html($display)
    );
}
add_action('manage_bsmap_tienda_posts_custom_column', 'bsmap_render_admin_columns', 10, 2);

function bsmap_sortable_admin_columns($columns) {
    $columns['bsmap_promo'] = 'bsmap_promo';
    return $columns;
}
add_filter('manage_edit-bsmap_tienda_sortable_columns', 'bsmap_sortable_admin_columns');

function bsmap_admin_promo_filter() {
    global $typenow;
    if ($typenow !== 'bsmap_tienda') return;
    $value = isset($_GET['bsmap_promo_filter']) ? sanitize_text_field($_GET['bsmap_promo_filter']) : '';
    ?>
    <select name="bsmap_promo_filter">
        <option value=""><?php esc_html_e('Todas las promociones', 'bsmap'); ?></option>
        <option value="with" <?php selected($value, 'with'); ?>>Con promoción</option>
        <option value="without" <?php selected($value, 'without'); ?>>Sin promoción</option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'bsmap_admin_promo_filter');

function bsmap_admin_promo_query($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    $post_type = $query->get('post_type');
    if ($post_type !== 'bsmap_tienda') return;

    $orderby = $query->get('orderby');
    if ($orderby === 'bsmap_promo') {
        $query->set('meta_key', 'bsmap_promo_id');
        $query->set('orderby', 'meta_value_num');
    }

    if (empty($_GET['bsmap_promo_filter'])) return;
    $filter = sanitize_text_field($_GET['bsmap_promo_filter']);
    if ($filter === 'with') {
        $query->set('meta_query', [
            [
                'key'     => 'bsmap_veganuary_2x1',
                'value'   => '1',
                'compare' => '='
            ],
            [
                'key'     => 'bsmap_promo_id',
                'value'   => 0,
                'compare' => '>'
            ]
        ]);
    } elseif ($filter === 'without') {
        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key'     => 'bsmap_veganuary_2x1',
                'value'   => '1',
                'compare' => '!='
            ],
            [
                'key'     => 'bsmap_promo_id',
                'value'   => 0,
                'compare' => '<='
            ],
            [
                'key'     => 'bsmap_promo_id',
                'compare' => 'NOT EXISTS'
            ],
        ]);
    }
}
add_action('pre_get_posts', 'bsmap_admin_promo_query');

function bsmap_quick_edit_fields($column_name, $post_type) {
    if ($column_name !== 'bsmap_promo' || $post_type !== 'bsmap_tienda') return;
    $active_promos = bsmap_get_active_promos();
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="alignleft">
                <input type="checkbox" name="bsmap_veganuary_2x1" value="1">
                <span class="checkbox-title">Promoción</span>
            </label>
            <label class="alignleft" style="margin-left:12px;">
                <span class="title">Promoción activa</span>
                <select name="bsmap_promo_id">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($active_promos as $promo): ?>
                        <option value="<?php echo esc_attr($promo['id']); ?>"><?php echo esc_html($promo['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'bsmap_quick_edit_fields', 10, 2);

function bsmap_bulk_edit_fields($column_name, $post_type) {
    if ($column_name !== 'bsmap_promo' || $post_type !== 'bsmap_tienda') return;
    $active_promos = bsmap_get_active_promos();
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="alignleft">
                <span class="title">Estado promoción</span>
                <select name="bsmap_bulk_promo_state">
                    <option value="">No cambiar</option>
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </label>
            <label class="alignleft" style="margin-left:12px;">
                <span class="title">Promoción activa</span>
                <select name="bsmap_bulk_promo_id">
                    <option value="">No cambiar</option>
                    <option value="0">Sin promoción</option>
                    <?php foreach ($active_promos as $promo): ?>
                        <option value="<?php echo esc_attr($promo['id']); ?>"><?php echo esc_html($promo['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('bulk_edit_custom_box', 'bsmap_bulk_edit_fields', 10, 2);

function bsmap_quick_edit_js() {
    global $typenow;
    if ($typenow !== 'bsmap_tienda') return;
    ?>
    <script>
    jQuery(function($){
        if (typeof inlineEditPost === "undefined") return;
        const $wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $wp_inline_edit.apply(this, arguments);
            const postId = typeof(id) === "object" ? this.getId(id) : id;
            if (!postId) return;
            const $row = $("#post-" + postId);
            const $edit = $("#edit-" + postId);
            const $data = $row.find(".bsmap-promo-data");
            if (!$data.length) return;
            const enabled = String($data.data("promo-enabled")) === "1";
            const promoId = String($data.data("promo-id") || "");
            $edit.find('input[name="bsmap_veganuary_2x1"]').prop("checked", enabled);
            $edit.find('select[name="bsmap_promo_id"]').val(promoId);
        };
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'bsmap_quick_edit_js');

function bsmap_handle_bulk_edit($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( get_post_type($post_id) !== 'bsmap_tienda' ) return;
    if ( !isset($_REQUEST['bulk_edit']) ) return;

    if ( isset($_REQUEST['bsmap_bulk_promo_state']) && $_REQUEST['bsmap_bulk_promo_state'] !== '' ) {
        $state = sanitize_text_field($_REQUEST['bsmap_bulk_promo_state']);
        if ($state === '1' || $state === '0') {
            update_post_meta($post_id, 'bsmap_veganuary_2x1', $state);
        }
    }

    if ( isset($_REQUEST['bsmap_bulk_promo_id']) && $_REQUEST['bsmap_bulk_promo_id'] !== '' ) {
        $promo_id = absint($_REQUEST['bsmap_bulk_promo_id']);
        update_post_meta($post_id, 'bsmap_promo_id', $promo_id);
    }
}
add_action('save_post', 'bsmap_handle_bulk_edit', 20);

/* ============================================================
   3) Shortcode [bsmap] — layout con sidebar (forzado para Elementor)
   ============================================================ */
function bsmap_shortcode() {
    ob_start(); ?>
    <div id="bsmap-wrapper" class="bsmap">
        <div id="bsmap-sidebar">
            <h2 class="bsmap-title">Encuentra tu tienda</h2>

            <form id="bsmap-search">
                <input id="bsmap-search-input" type="text" placeholder="Buscar tienda, dirección o zona…">
                <button type="submit">Buscar</button>
            </form>

            <div class="bsmap-promos" hidden>
                <div class="bsmap-promos-list"></div>
            </div>

            <div class="bsmap-filters">
                <h3>Filtrar por tipo</h3>
                <button data-category="todos" class="active">Todos</button>
                <button data-category="distribuidor">Distribuidor</button>
                <button data-category="online">Venta Online</button>
                <button data-category="fisica">Tienda Física</button>
            </div>

            <div id="bsmap-legend">
                <h4>Colores del mapa</h4>
                <ul>
                    <li><span class="bsmap-color-distribuidor"></span>Distribuidor</li>
                    <li><span class="bsmap-color-online"></span>Venta Online</li>
                    <li><span class="bsmap-color-fisica"></span>Tienda Física</li>
                </ul>
            </div>
        </div>

        <div id="bsmap-container"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bsmap', 'bsmap_shortcode');

/* ============================================================
   4) Enqueue assets (solo en páginas con [bsmap])
   ============================================================ */
function bsmap_enqueue_assets() {
    if ( ! is_singular() ) return;
    global $post;
    if ( ! $post || ! has_shortcode( $post->post_content, 'bsmap' ) ) return;

    // Leaflet (CDN)
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], null );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true );

    // Plugin assets
    $base = plugin_dir_url(__FILE__) . 'assets/';
    
    // Fuente Barlow Semi Condensed
    wp_enqueue_style(
        'bsmap-fonts',
        'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;700&display=swap',
        [],
        null
    );
    wp_enqueue_style( 'bsmap-css', $base . 'bsmap.css', [], '1.5' );
    wp_enqueue_script( 'bsmap-js', $base . 'bsmap.js', ['leaflet-js'], '1.5', true );

    // Datos: tiendas
    $tiendas = [];
    $posts = get_posts([
        'post_type'   => 'bsmap_tienda',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);
    foreach ($posts as $p) {
    $tiendas[] = [
        'nombre'     => $p->post_title,
        'direccion'  => get_post_meta($p->ID, 'bsmap_direccion', true),
        'lat'        => get_post_meta($p->ID, 'bsmap_lat', true),
        'lng'        => get_post_meta($p->ID, 'bsmap_lng', true),
        'categoria'  => get_post_meta($p->ID, 'bsmap_categoria', true),
        'web'        => get_post_meta($p->ID, 'bsmap_web', true),
        'instagram'  => get_post_meta($p->ID, 'bsmap_instagram', true),
        'zona'       => get_post_meta($p->ID, 'bsmap_zona', true), // <-- añade esto
        'promo_id'   => absint(get_post_meta($p->ID, 'bsmap_promo_id', true)),
        'veganuary_2x1' => (get_post_meta($p->ID, 'bsmap_veganuary_2x1', true) === '1'),
    ];
}
    $promos = bsmap_get_active_promos();
    wp_localize_script( 'bsmap-js', 'bsmap_data', ['tiendas' => $tiendas, 'promos' => $promos] );
}
add_action('wp_enqueue_scripts', 'bsmap_enqueue_assets');

/* ============================================================
   5) Importador CSV con geocodificación (Nominatim)
   ============================================================ */
function bsmap_register_import_page() {
    add_submenu_page(
        'edit.php?post_type=bsmap_tienda',
        'Importar CSV',
        'Importar CSV',
        'manage_options',
        'bsmap_import_csv',
        'bsmap_import_csv_page'
    );
}
add_action('admin_menu', 'bsmap_register_import_page');

/* ============================================================
   6) Columnas en listado de promociones (admin)
   ============================================================ */
function bsmap_add_promo_admin_columns($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['bsmap_promo_active'] = 'Activa';
            $new['bsmap_promo_start'] = 'Inicio';
            $new['bsmap_promo_end'] = 'Fin';
        }
    }
    if (!isset($new['bsmap_promo_active'])) {
        $new['bsmap_promo_active'] = 'Activa';
        $new['bsmap_promo_start'] = 'Inicio';
        $new['bsmap_promo_end'] = 'Fin';
    }
    return $new;
}
add_filter('manage_bsmap_promo_posts_columns', 'bsmap_add_promo_admin_columns');

function bsmap_render_promo_admin_columns($column, $post_id) {
    if ($column === 'bsmap_promo_active') {
        echo bsmap_is_promo_active($post_id) ? 'Si' : 'No';
        return;
    }
    if ($column === 'bsmap_promo_start') {
        $start = get_post_meta($post_id, 'bsmap_promo_start', true);
        echo $start ? esc_html(date_i18n('d/m/Y', strtotime($start))) : '—';
        return;
    }
    if ($column === 'bsmap_promo_end') {
        $end = get_post_meta($post_id, 'bsmap_promo_end', true);
        echo $end ? esc_html(date_i18n('d/m/Y', strtotime($end))) : '—';
        return;
    }
}
add_action('manage_bsmap_promo_posts_custom_column', 'bsmap_render_promo_admin_columns', 10, 2);

function bsmap_import_csv_page() {
    ?>
    <div class="wrap">
        <h1>Importar Tiendas desde CSV</h1>
        <p>Cabecera esperada: <code>nombre,direccion,lat,lng,categoria,web,instagram,zona,veganuary_2x1</code></p>
        <p>Si faltan coordenadas, se geocodificarán con OpenStreetMap (Nominatim).</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('bsmap_import_csv_nonce', 'bsmap_import_csv_nonce_field'); ?>
            <input type="file" name="bsmap_csv" accept=".csv" required />
            <p><input type="submit" name="bsmap_import_submit" class="button button-primary" value="Importar"></p>
        </form>
    </div>
    <?php
    if (isset($_POST['bsmap_import_submit'])) {
        bsmap_handle_csv_import();
    }
}

function bsmap_handle_csv_import() {
    if (!isset($_POST['bsmap_import_csv_nonce_field']) ||
        !wp_verify_nonce($_POST['bsmap_import_csv_nonce_field'], 'bsmap_import_csv_nonce')) {
        echo '<div class="notice notice-error"><p>Error de seguridad.</p></div>';
        return;
    }

    if (!isset($_FILES['bsmap_csv']) || $_FILES['bsmap_csv']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>Error al subir el archivo CSV.</p></div>';
        return;
    }

    // ⚙️ Parámetros de seguridad / rendimiento
    @set_time_limit(300); // 5 min
    $HTTP_TIMEOUT           = 15;   // timeout por petición
    $SLEEP_BETWEEN_REQUESTS = 1.2;  // segundos entre geocodificaciones
    $MAX_GEOCODES_PER_RUN   = 25;   // límite de geocodificaciones por import

    $file   = $_FILES['bsmap_csv']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        echo '<div class="notice notice-error"><p>No se pudo leer el archivo.</p></div>';
        return;
    }

    // Cabeceras esperadas (7 u 8 columnas)
    $header = fgetcsv($handle, 2000, ',');
    if (!$header) {
        echo '<div class="notice notice-error"><p>CSV vacío o cabecera inválida.</p></div>';
        fclose($handle);
        return;
    }
    // Normaliza cabeceras
    $header = array_map(function($h){ return strtolower(trim($h)); }, $header);

    // Soportar variantes: con o sin "zona" y "veganuary_2x1"
    $has_zona = in_array('zona', $header, true);
    $has_veganuary = in_array('veganuary_2x1', $header, true);

    // Mapeo de índices
    $idx = array_flip($header);
    foreach (['nombre','direccion','lat','lng','categoria','web','instagram'] as $col) {
        if (!isset($idx[$col])) {
            echo '<div class="notice notice-error"><p>Falta la columna obligatoria: <code>'.$col.'</code>.</p></div>';
            fclose($handle);
            return;
        }
    }

    $created = 0; $updated = 0; $geocoded = 0; $skipped_geo = 0; $errors = [];
    $geo_count_this_run = 0;

    // Helper: categoría normalizada
    $norm_cat = function($s){
        $s = strtolower(trim((string)$s));
        if (strpos($s,'distribuidor') !== false) return 'distribuidor';
        if (strpos($s,'online') !== false)       return 'online';
        if (strpos($s,'fisica') !== false || strpos($s,'física') !== false) return 'fisica';
        if (in_array($s, ['distribuidor','online','fisica'], true)) return $s;
        return '';
    };

    // Helper: dirección “suficientemente específica” para geocodificar
    $is_specific_address = function($dir){
        $dir = trim((string)$dir);
        // Al menos 2 palabras y > 6 caracteres (evita sólo "España", "Madrid", etc.)
        if (mb_strlen($dir) < 7) return false;
        if (str_word_count($dir) < 2) return false;
        return true;
    };

    // Helper: geocodificación con manejo de 429 y excepciones
    $geocode = function($address) use ($HTTP_TIMEOUT) {
        $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);
        $args = [
            'timeout'    => $HTTP_TIMEOUT,
            'user-agent' => 'BSMap/1.3 (+https://www.premiero.es)'
        ];
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code == 429) {
            return ['error' => '429'];
        }
        if ($code < 200 || $code >= 300) {
            return ['error' => 'HTTP '.$code];
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data[0])) {
            return ['error' => 'sin_resultados'];
        }
        return ['lat' => $data[0]['lat'], 'lng' => $data[0]['lon']];
    };

    $rownum = 1; // ya leímos cabecera
    while (($data = fgetcsv($handle, 4000, ',')) !== false) {
        $rownum++;

        // Alinear columnas (por si faltan/ sobran)
        $row = [];
        foreach ($idx as $k => $pos) {
            $row[$k] = isset($data[$pos]) ? trim((string)$data[$pos]) : '';
        }
        if ($has_zona && !isset($row['zona'])) $row['zona'] = '';
        if ($has_veganuary && !isset($row['veganuary_2x1'])) $row['veganuary_2x1'] = '';

        // Limpieza básica
        foreach ($row as $k => $v) {
            // reemplaza comillas raras / espacios no-break
            $v = str_replace(["\xC2\xA0", "“","”","’"], [' ','"','"',"'"], $v);
            $row[$k] = trim($v);
        }

        $nombre    = $row['nombre'];
        $direccion = $row['direccion'];
        $lat       = $row['lat'];
        $lng       = $row['lng'];
        $categoria = $norm_cat($row['categoria']);
        $web       = $row['web'];
        $insta     = $row['instagram'];
        $zona      = $has_zona ? $row['zona'] : '';
        $veganuary = $has_veganuary ? $row['veganuary_2x1'] : '';

        if ($nombre === '') {
            $errors[] = "Fila {$rownum}: sin nombre → omitida.";
            continue;
        }

        // Geocodificar si faltan coords y la dirección es suficientemente específica
        if (($lat === '' || $lng === '') && $direccion !== '' && $is_specific_address($direccion)) {
            if ($geo_count_this_run < $MAX_GEOCODES_PER_RUN) {
                $res = $geocode($direccion);
                if (isset($res['error'])) {
                    if ($res['error'] === '429') {
                        // Espera y reintenta una vez
                        sleep(2);
                        $res = $geocode($direccion);
                    }
                }
                if (isset($res['lat'], $res['lng'])) {
                    $lat = $res['lat'];
                    $lng = $res['lng'];
                    $geocoded++;
                    $geo_count_this_run++;
                    // respetar TOS
                    usleep((int)($SLEEP_BETWEEN_REQUESTS * 1e6));
                } else {
                    $skipped_geo++;
                }
            } else {
                // límite alcanzado: no romper la importación
                $skipped_geo++;
            }
        }

        // Crear o actualizar por nombre
        $existing = get_page_by_title($nombre, OBJECT, 'bsmap_tienda');
        if ($existing) {
            $post_id = $existing->ID;
            $updated++;
        } else {
            $post_id = wp_insert_post([
                'post_title'  => sanitize_text_field($nombre),
                'post_type'   => 'bsmap_tienda',
                'post_status' => 'publish'
            ]);
            if (is_wp_error($post_id)) {
                $errors[] = "Fila {$rownum}: error al crear la tienda ({$post_id->get_error_message()})";
                continue;
            }
            $created++;
        }

        // Guardar metadatos
        update_post_meta($post_id, 'bsmap_direccion',  sanitize_text_field($direccion));
        update_post_meta($post_id, 'bsmap_lat',         sanitize_text_field($lat));
        update_post_meta($post_id, 'bsmap_lng',         sanitize_text_field($lng));
        update_post_meta($post_id, 'bsmap_categoria',   sanitize_text_field($categoria));
        update_post_meta($post_id, 'bsmap_web',         esc_url_raw($web));
        update_post_meta($post_id, 'bsmap_instagram',   esc_url_raw($insta));
        if ($has_zona) {
            update_post_meta($post_id, 'bsmap_zona',    sanitize_text_field($zona));
        }
        if ($has_veganuary) {
            $flag = strtolower(trim((string)$veganuary));
            $is_yes = in_array($flag, ['1','si','sí','true','yes','y','x'], true);
            update_post_meta($post_id, 'bsmap_veganuary_2x1', $is_yes ? '1' : '0');
        }
    }

    fclose($handle);

    // Resumen
    echo '<div class="notice notice-success"><p><strong>Importación completada</strong></p>
        <ul>
          <li>'.intval($created).' tiendas creadas</li>
          <li>'.intval($updated).' tiendas actualizadas</li>
          <li>'.intval($geocoded).' direcciones geocodificadas</li>
          <li>'.intval($skipped_geo).' direcciones no geocodificadas (ambigua o límite alcanzado)</li>
        </ul></div>';

    if (!empty($errors)) {
        echo '<div class="notice notice-warning"><p><strong>Avisos</strong></p><ul style="margin-left:16px">';
        foreach ($errors as $msg) {
            echo '<li>'.esc_html($msg).'</li>';
        }
        echo '</ul></div>';
    }
}


function bsmap_geocode_address($address) {
    if (empty($address)) return false;
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);
    $response = wp_remote_get($url, [
        'timeout'    => 12,
        'user-agent' => 'BSMap/1.3 (+https://www.premiero.es)'
    ]);
    if (is_wp_error($response)) return false;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data[0])) return false;
    return ['lat' => $data[0]['lat'], 'lng' => $data[0]['lon']];
}
