<?php
/*
Plugin Name: Premiero Mapa Configurable
Plugin URI: https://github.com/andres-nmg/premiero-mapa-configurable/
Description: Crea mapas interactivos de ubicaciones con campos, textos y colores configurables.
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: Premiero
Author URI: https://premiero.es
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Update URI: https://github.com/andres-nmg/premiero-mapa-configurable/
Text Domain: premiero-mapa-configurable
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PCM_VERSION', '1.0.0' );
define( 'PCM_POST_TYPE', 'map_location' );
define( 'PCM_OPTION', 'pcm_settings' );
define( 'PCM_PLUGIN_SLUG', 'premiero-mapa-configurable' );
define( 'PCM_REPOSITORY_URL', 'https://github.com/andres-nmg/premiero-mapa-configurable/' );
define( 'PCM_RELEASE_API', 'https://api.github.com/repos/andres-nmg/premiero-mapa-configurable/releases/latest' );
define( 'PCM_RELEASE_ASSET', 'premiero-mapa-configurable.zip' );

require_once __DIR__ . '/includes/class-premiero-mapa-updater.php';
Premiero_Mapa_Updater::init();

/**
 * Ajustes y utilidades
 */
function pcm_default_settings() {
    return array(
        'singular'          => 'Ubicación',
        'plural'            => 'Ubicaciones',
        'map_title'         => '',
        'search_placeholder'=> '',
        'extra_fields'      => array(
            array( 'enabled' => '0', 'label' => '', 'type' => 'text' ),
            array( 'enabled' => '0', 'label' => '', 'type' => 'text' ),
            array( 'enabled' => '0', 'label' => '', 'type' => 'text' ),
        ),
        'colors'            => array(
            'sidebar_bg'    => '#234E52',
            'sidebar_text'  => '#FFFFFF',
            'body_text'     => '#374151',
            'accent'        => '#2B7A78',
            'accent_text'   => '#FFFFFF',
            'marker'        => '#E76F51',
            'popup_title'   => '#234E52',
            'button_bg'     => '#2B7A78',
            'button_text'   => '#FFFFFF',
            'map_bg'        => '#E5E7EB',
        ),
    );
}

function pcm_get_settings() {
    $saved = get_option( PCM_OPTION, array() );
    $saved = is_array( $saved ) ? $saved : array();
    $defaults = pcm_default_settings();
    $settings = wp_parse_args( $saved, $defaults );
    $settings['colors'] = wp_parse_args(
        isset( $saved['colors'] ) && is_array( $saved['colors'] ) ? $saved['colors'] : array(),
        $defaults['colors']
    );

    $fields = isset( $saved['extra_fields'] ) && is_array( $saved['extra_fields'] )
        ? array_values( $saved['extra_fields'] )
        : $defaults['extra_fields'];
    for ( $i = 0; $i < 3; $i++ ) {
        $settings['extra_fields'][ $i ] = wp_parse_args(
            isset( $fields[ $i ] ) && is_array( $fields[ $i ] ) ? $fields[ $i ] : array(),
            $defaults['extra_fields'][ $i ]
        );
    }

    return $settings;
}

function pcm_sanitize_settings( $input ) {
    $current = pcm_get_settings();
    $input = is_array( $input ) ? $input : array();

    if ( isset( $input['settings_tab'] ) && 'general' === $input['settings_tab'] ) {
        $current['singular'] = isset( $input['singular'] ) ? sanitize_text_field( $input['singular'] ) : $current['singular'];
        $current['plural'] = isset( $input['plural'] ) ? sanitize_text_field( $input['plural'] ) : $current['plural'];
        $current['map_title'] = isset( $input['map_title'] ) ? sanitize_text_field( $input['map_title'] ) : '';
        $current['search_placeholder'] = isset( $input['search_placeholder'] ) ? sanitize_text_field( $input['search_placeholder'] ) : '';

        $allowed_types = array( 'text', 'textarea', 'url', 'email', 'tel' );
        for ( $i = 0; $i < 3; $i++ ) {
            $field = isset( $input['extra_fields'][ $i ] ) && is_array( $input['extra_fields'][ $i ] )
                ? $input['extra_fields'][ $i ]
                : array();
            $type = isset( $field['type'] ) && in_array( $field['type'], $allowed_types, true )
                ? $field['type']
                : 'text';
            $current['extra_fields'][ $i ] = array(
                'enabled' => isset( $field['enabled'] ) ? '1' : '0',
                'label'   => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
                'type'    => $type,
            );
        }
    }

    if ( isset( $input['settings_tab'] ) && 'colors' === $input['settings_tab'] ) {
        foreach ( pcm_default_settings()['colors'] as $key => $default ) {
            $color = isset( $input['colors'][ $key ] ) ? sanitize_hex_color( $input['colors'][ $key ] ) : '';
            $current['colors'][ $key ] = $color ? $color : $default;
        }
    }

    $current['singular'] = $current['singular'] ? $current['singular'] : 'Ubicación';
    $current['plural'] = $current['plural'] ? $current['plural'] : 'Ubicaciones';
    unset( $current['settings_tab'] );
    return $current;
}

function pcm_register_settings() {
    register_setting(
        'pcm_settings_group',
        PCM_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'pcm_sanitize_settings',
            'default'           => pcm_default_settings(),
        )
    );
}
add_action( 'admin_init', 'pcm_register_settings' );

function pcm_label( $form = 'singular', $lowercase = false ) {
    $settings = pcm_get_settings();
    $label = 'plural' === $form ? $settings['plural'] : $settings['singular'];
    if ( ! $lowercase ) {
        return $label;
    }
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $label ) : strtolower( $label );
}

/**
 * Migra una sola vez los datos de la versión anterior sin exponer su
 * terminología en la interfaz nueva.
 */
function pcm_maybe_migrate_legacy_data() {
    if ( PCM_VERSION === get_option( 'premiero_pcm_migration_version' ) ) {
        return;
    }

    $old_prefix = 'bs' . 'map_';
    $old_post_type = $old_prefix . implode( '', array( 'ti', 'enda' ) );
    $posts = get_posts(
        array(
            'post_type'      => $old_post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    foreach ( $posts as $post_id ) {
        wp_update_post( array( 'ID' => $post_id, 'post_type' => PCM_POST_TYPE ) );
        $mapping = array(
            'direccion' => 'pcm_address',
            'lat'       => 'pcm_lat',
            'lng'       => 'pcm_lng',
            'web'       => 'pcm_web',
            'zona'      => 'pcm_extra_1',
            'instagram' => 'pcm_extra_2',
            'categoria' => 'pcm_extra_3',
        );
        foreach ( $mapping as $old_key => $new_key ) {
            $value = get_post_meta( $post_id, $old_prefix . $old_key, true );
            if ( '' !== $value && '' === get_post_meta( $post_id, $new_key, true ) ) {
                update_post_meta( $post_id, $new_key, $value );
            }
        }
    }

    if ( $posts && false === get_option( PCM_OPTION, false ) ) {
        $settings = pcm_default_settings();
        $settings['extra_fields'] = array(
            array( 'enabled' => '1', 'label' => 'Zona', 'type' => 'text' ),
            array( 'enabled' => '1', 'label' => 'Instagram', 'type' => 'url' ),
            array( 'enabled' => '1', 'label' => 'Tipo', 'type' => 'text' ),
        );
        update_option( PCM_OPTION, $settings );
    }

    update_option( 'premiero_pcm_migration_version', PCM_VERSION );
}
add_action( 'init', 'pcm_maybe_migrate_legacy_data', 1 );

/**
 * Tipo de contenido: ubicaciones
 */
function pcm_register_location_type() {
    $singular = pcm_label();
    $plural = pcm_label( 'plural' );
    $singular_lower = pcm_label( 'singular', true );
    $plural_lower = pcm_label( 'plural', true );

    register_post_type(
        PCM_POST_TYPE,
        array(
            'labels' => array(
                'name'                  => $plural,
                'singular_name'         => $singular,
                'menu_name'             => 'Mapa de ' . $plural_lower,
                'name_admin_bar'        => $singular,
                'add_new'               => 'Añadir ' . $singular_lower,
                'add_new_item'          => 'Añadir ' . $singular_lower,
                'edit_item'             => 'Editar ' . $singular_lower,
                'new_item'              => 'Crear ' . $singular_lower,
                'view_item'             => 'Ver ' . $singular_lower,
                'search_items'          => 'Buscar ' . $plural_lower,
                'not_found'             => 'No hay resultados',
                'not_found_in_trash'    => 'No hay resultados en la papelera',
                'all_items'             => 'Ver ' . $plural_lower,
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => array( 'title' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        )
    );
}
add_action( 'init', 'pcm_register_location_type', 5 );

/**
 * Campos de cada ubicación
 */
function pcm_add_location_metabox() {
    add_meta_box(
        'pcm_location_details',
        'Datos: ' . pcm_label( 'singular', true ),
        'pcm_render_location_metabox',
        PCM_POST_TYPE,
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'pcm_add_location_metabox' );

function pcm_render_location_metabox( $post ) {
    $settings = pcm_get_settings();
    wp_nonce_field( 'pcm_save_location', 'pcm_location_nonce' );
    $address = get_post_meta( $post->ID, 'pcm_address', true );
    $lat = get_post_meta( $post->ID, 'pcm_lat', true );
    $lng = get_post_meta( $post->ID, 'pcm_lng', true );
    $web = get_post_meta( $post->ID, 'pcm_web', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="pcm_address">Dirección</label></th>
            <td>
                <input id="pcm_address" type="text" name="pcm_address" value="<?php echo esc_attr( $address ); ?>" class="large-text">
                <p class="description">Si las coordenadas están vacías, se buscarán automáticamente al guardar.</p>
            </td>
        </tr>
        <tr>
            <th><label for="pcm_lat">Latitud</label></th>
            <td><input id="pcm_lat" type="text" name="pcm_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text" placeholder="40.4168"></td>
        </tr>
        <tr>
            <th><label for="pcm_lng">Longitud</label></th>
            <td><input id="pcm_lng" type="text" name="pcm_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text" placeholder="-3.7038"></td>
        </tr>
        <tr>
            <th><label for="pcm_web">Web</label></th>
            <td>
                <input id="pcm_web" type="url" name="pcm_web" value="<?php echo esc_attr( $web ); ?>" class="large-text" placeholder="https://...">
                <p class="description">Solo aparecerá en el popup si se ha completado.</p>
            </td>
        </tr>
        <?php foreach ( $settings['extra_fields'] as $index => $field ) : ?>
            <?php if ( '1' !== $field['enabled'] || '' === trim( $field['label'] ) ) continue; ?>
            <?php
            $number = $index + 1;
            $name = 'pcm_extra_' . $number;
            $value = get_post_meta( $post->ID, $name, true );
            ?>
            <tr>
                <th><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                <td>
                    <?php if ( 'textarea' === $field['type'] ) : ?>
                        <textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="large-text" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
                    <?php else : ?>
                        <input
                            id="<?php echo esc_attr( $name ); ?>"
                            type="<?php echo esc_attr( $field['type'] ); ?>"
                            name="<?php echo esc_attr( $name ); ?>"
                            value="<?php echo esc_attr( $value ); ?>"
                            class="large-text"
                        >
                    <?php endif; ?>
                    <p class="description">Solo aparecerá en el popup si se ha completado.</p>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}

function pcm_sanitize_extra_value( $value, $type ) {
    if ( 'url' === $type ) {
        return esc_url_raw( $value );
    }
    if ( 'email' === $type ) {
        return sanitize_email( $value );
    }
    if ( 'textarea' === $type ) {
        return sanitize_textarea_field( $value );
    }
    return sanitize_text_field( $value );
}

function pcm_save_location( $post_id ) {
    if ( ! isset( $_POST['pcm_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pcm_location_nonce'] ) ), 'pcm_save_location' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( PCM_POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $address = isset( $_POST['pcm_address'] ) ? sanitize_text_field( wp_unslash( $_POST['pcm_address'] ) ) : '';
    $lat = isset( $_POST['pcm_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['pcm_lat'] ) ) : '';
    $lng = isset( $_POST['pcm_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['pcm_lng'] ) ) : '';
    $web = isset( $_POST['pcm_web'] ) ? esc_url_raw( wp_unslash( $_POST['pcm_web'] ) ) : '';

    if ( $address && ( '' === $lat || '' === $lng ) ) {
        $coordinates = pcm_geocode_address( $address );
        if ( $coordinates ) {
            $lat = $coordinates['lat'];
            $lng = $coordinates['lng'];
        }
    }

    update_post_meta( $post_id, 'pcm_address', $address );
    update_post_meta( $post_id, 'pcm_lat', $lat );
    update_post_meta( $post_id, 'pcm_lng', $lng );
    update_post_meta( $post_id, 'pcm_web', $web );

    $settings = pcm_get_settings();
    foreach ( $settings['extra_fields'] as $index => $field ) {
        $key = 'pcm_extra_' . ( $index + 1 );
        if ( '1' !== $field['enabled'] || '' === trim( $field['label'] ) ) {
            continue;
        }
        $value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
        update_post_meta( $post_id, $key, pcm_sanitize_extra_value( $value, $field['type'] ) );
    }
}
add_action( 'save_post_' . PCM_POST_TYPE, 'pcm_save_location' );

function pcm_geocode_address( $address ) {
    $address = trim( (string) $address );
    if ( '' === $address ) {
        return false;
    }

    $normalized_address = function_exists( 'mb_strtolower' ) ? mb_strtolower( $address ) : strtolower( $address );
    $cache_key = 'pcm_geo_' . md5( $normalized_address );
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['lat'], $cached['lng'] ) ) {
        return $cached;
    }

    $url = add_query_arg(
        array(
            'format' => 'jsonv2',
            'limit'  => 1,
            'q'      => $address,
        ),
        'https://nominatim.openstreetmap.org/search'
    );
    $response = wp_remote_get(
        $url,
        array(
            'timeout'    => 15,
            'user-agent' => 'Premiero-Mapa-Configurable/' . PCM_VERSION . ' (+https://premiero.es)',
            'headers'    => array( 'Accept-Language' => get_locale() ),
        )
    );
    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data[0]['lat'] ) || empty( $data[0]['lon'] ) ) {
        return false;
    }

    $coordinates = array(
        'lat' => sanitize_text_field( $data[0]['lat'] ),
        'lng' => sanitize_text_field( $data[0]['lon'] ),
    );
    set_transient( $cache_key, $coordinates, MONTH_IN_SECONDS );
    return $coordinates;
}

/**
 * Administración: ajustes e importador
 */
function pcm_admin_menu() {
    $parent = 'edit.php?post_type=' . PCM_POST_TYPE;
    add_submenu_page(
        $parent,
        'Ajustes del mapa',
        'Ajustes',
        'manage_options',
        'pcm-settings',
        'pcm_render_settings_page'
    );
}
add_action( 'admin_menu', 'pcm_admin_menu' );

function pcm_plugin_action_links( $links ) {
    $settings_url = admin_url( 'edit.php?post_type=' . PCM_POST_TYPE . '&page=pcm-settings' );
    array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">Ajustes</a>' );
    $links[] = '<a href="' . esc_url( PCM_REPOSITORY_URL ) . '" target="_blank" rel="noopener noreferrer">GitHub</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pcm_plugin_action_links' );

function pcm_admin_assets( $hook ) {
    if ( 'map_location_page_pcm-settings' !== $hook ) {
        return;
    }
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script(
        'pcm-admin-settings',
        plugin_dir_url( __FILE__ ) . 'assets/mapa-admin.js',
        array( 'jquery', 'wp-color-picker' ),
        PCM_VERSION,
        true
    );
    wp_enqueue_style(
        'pcm-admin-settings',
        plugin_dir_url( __FILE__ ) . 'assets/mapa-admin.css',
        array( 'wp-color-picker' ),
        PCM_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'pcm_admin_assets' );

function pcm_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = pcm_get_settings();
    $allowed_tabs = array( 'general', 'colors', 'import', 'premiero' );
    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    $tab = in_array( $tab, $allowed_tabs, true ) ? $tab : 'general';
    $base_url = admin_url( 'edit.php?post_type=' . PCM_POST_TYPE . '&page=pcm-settings' );
    ?>
    <div class="wrap pcm-settings">
        <h1>Ajustes del mapa</h1>
        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $base_url . '&tab=general' ); ?>" class="nav-tab <?php echo 'general' === $tab ? 'nav-tab-active' : ''; ?>">General y campos</a>
            <a href="<?php echo esc_url( $base_url . '&tab=colors' ); ?>" class="nav-tab <?php echo 'colors' === $tab ? 'nav-tab-active' : ''; ?>">Colores y vista previa</a>
            <a href="<?php echo esc_url( $base_url . '&tab=import' ); ?>" class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>">Importar CSV</a>
            <a href="<?php echo esc_url( $base_url . '&tab=premiero' ); ?>" class="nav-tab <?php echo 'premiero' === $tab ? 'nav-tab-active' : ''; ?>">Acerca de</a>
        </nav>

        <?php if ( 'premiero' === $tab ) : ?>
            <?php pcm_render_premiero_tab(); ?>
        <?php elseif ( 'import' === $tab ) : ?>
            <?php pcm_render_import_tab(); ?>
        <?php else : ?>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
                <?php settings_fields( 'pcm_settings_group' ); ?>
                <input type="hidden" name="<?php echo esc_attr( PCM_OPTION ); ?>[settings_tab]" value="<?php echo esc_attr( $tab ); ?>">

                <?php if ( 'general' === $tab ) : ?>
                <h2>Nombre del tipo de mapa</h2>
                <p>Estos nombres se usan automáticamente en el menú, los botones y los mensajes del plugin.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="pcm-singular">Nombre singular</label></th>
                        <td>
                            <input id="pcm-singular" type="text" name="<?php echo esc_attr( PCM_OPTION ); ?>[singular]" value="<?php echo esc_attr( $settings['singular'] ); ?>" class="regular-text" required>
                            <p class="description">Ejemplos: Oficina, Centro, Playa, Local.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pcm-plural">Nombre plural</label></th>
                        <td>
                            <input id="pcm-plural" type="text" name="<?php echo esc_attr( PCM_OPTION ); ?>[plural]" value="<?php echo esc_attr( $settings['plural'] ); ?>" class="regular-text" required>
                            <p class="description">Ejemplos: Oficinas, Centros, Playas, Locales.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pcm-map-title">Título del mapa</label></th>
                        <td>
                            <input id="pcm-map-title" type="text" name="<?php echo esc_attr( PCM_OPTION ); ?>[map_title]" value="<?php echo esc_attr( $settings['map_title'] ); ?>" class="regular-text">
                            <p class="description">Opcional. Si se deja vacío se mostrará “Encuentra tu <?php echo esc_html( pcm_label( 'singular', true ) ); ?>”.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pcm-search-placeholder">Texto del buscador</label></th>
                        <td>
                            <input id="pcm-search-placeholder" type="text" name="<?php echo esc_attr( PCM_OPTION ); ?>[search_placeholder]" value="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" class="regular-text">
                            <p class="description">Opcional. Se genera automáticamente si se deja vacío.</p>
                        </td>
                    </tr>
                </table>

                <h2>Campos del popup</h2>
                <p>Nombre, dirección y web ya están incluidos. Activa hasta tres campos adicionales; solo se mostrarán cuando tengan contenido.</p>
                <table class="widefat striped pcm-fields-table">
                    <thead>
                        <tr><th>Mostrar</th><th>Etiqueta</th><th>Tipo de dato</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $settings['extra_fields'] as $index => $field ) : ?>
                            <tr>
                                <td><input type="checkbox" name="<?php echo esc_attr( PCM_OPTION ); ?>[extra_fields][<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( $field['enabled'], '1' ); ?>></td>
                                <td><input type="text" name="<?php echo esc_attr( PCM_OPTION ); ?>[extra_fields][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" class="regular-text" placeholder="Ej.: Teléfono"></td>
                                <td>
                                    <select name="<?php echo esc_attr( PCM_OPTION ); ?>[extra_fields][<?php echo esc_attr( $index ); ?>][type]">
                                        <option value="text" <?php selected( $field['type'], 'text' ); ?>>Texto corto</option>
                                        <option value="textarea" <?php selected( $field['type'], 'textarea' ); ?>>Texto largo</option>
                                        <option value="url" <?php selected( $field['type'], 'url' ); ?>>Enlace</option>
                                        <option value="email" <?php selected( $field['type'], 'email' ); ?>>Correo electrónico</option>
                                        <option value="tel" <?php selected( $field['type'], 'tel' ); ?>>Teléfono</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pcm-shortcode-help">
                    <h2>Añadir el mapa a una página</h2>
                    <p>Copia este shortcode en cualquier página, entrada o bloque de shortcode:</p>
                    <p><code>[mapa]</code> <button type="button" class="button pcm-copy-shortcode" data-shortcode="[mapa]">Copiar</button></p>
                    <p>El mapa mostrará automáticamente todas las ubicaciones publicadas que tengan latitud y longitud.</p>
                </div>
                <?php else : ?>
                    <?php pcm_render_color_settings( $settings ); ?>
                <?php endif; ?>
                <?php submit_button( 'Guardar ajustes' ); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

function pcm_render_premiero_tab() {
    ?>
    <section class="pcm-about">
        <div class="pcm-about__brand">
            <a class="pcm-about__wordmark" href="https://premiero.es" target="_blank" rel="noopener noreferrer" aria-label="Visitar Premiero">
                <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/premiero-logo.png' ); ?>" alt="Premiero">
            </a>
            <p class="pcm-about__eyebrow">Desarrollo WordPress</p>
            <h2>Premiero Mapa Configurable</h2>
            <p class="pcm-about__lead">
                Plugin de código abierto desarrollado por <strong>Premiero</strong> para crear mapas reutilizables sin depender de un sector o tipo de ubicación concreto.
            </p>
            <div class="pcm-about__actions">
                <a class="button button-primary" href="https://premiero.es" target="_blank" rel="noopener noreferrer">Visitar premiero.es</a>
            </div>
        </div>

        <div class="pcm-about__details">
            <div class="pcm-about__card">
                <h3>Proyecto abierto</h3>
                <p>El código se distribuye bajo licencia GPL v3 o posterior. Puedes estudiarlo, modificarlo y redistribuirlo respetando la licencia y los avisos de autoría.</p>
                <a href="<?php echo esc_url( PCM_REPOSITORY_URL ); ?>" target="_blank" rel="noopener noreferrer">Ver repositorio en GitHub</a>
            </div>
            <div class="pcm-about__card">
                <h3>Actualizaciones</h3>
                <p>Las versiones estables se reciben desde GitHub Releases mediante el actualizador normal de WordPress.</p>
                <p><strong>Versión instalada:</strong> <?php echo esc_html( PCM_VERSION ); ?></p>
            </div>
            <div class="pcm-about__card">
                <h3>Soporte</h3>
                <p>¿Necesitas adaptar el mapa, integrarlo con otro sistema o desarrollar una solución a medida?</p>
                <div class="pcm-about__actions">
                    <a class="button button-primary" href="mailto:hola@premiero.es">Enviar un correo</a>
                    <a class="button" href="https://wa.me/34684774365" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function pcm_render_color_settings( $settings ) {
    $labels = array(
        'sidebar_bg'   => 'Fondo del panel',
        'sidebar_text' => 'Texto del panel',
        'body_text'    => 'Texto general',
        'accent'       => 'Color principal',
        'accent_text'  => 'Texto sobre color principal',
        'marker'       => 'Marcador',
        'popup_title'  => 'Título del popup',
        'button_bg'    => 'Botón web',
        'button_text'  => 'Texto del botón web',
        'map_bg'       => 'Fondo mientras carga el mapa',
    );
    ?>
    <div class="pcm-colors-layout">
        <div>
            <h2>Colores</h2>
            <p>Los cambios se reflejan al instante en la vista previa. Guarda para aplicarlos al mapa público.</p>
            <table class="form-table">
                <?php foreach ( $labels as $key => $label ) : ?>
                    <tr>
                        <th><label for="pcm-color-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                        <td>
                            <input
                                id="pcm-color-<?php echo esc_attr( $key ); ?>"
                                class="pcm-color-field"
                                type="text"
                                name="<?php echo esc_attr( PCM_OPTION ); ?>[colors][<?php echo esc_attr( $key ); ?>]"
                                value="<?php echo esc_attr( $settings['colors'][ $key ] ); ?>"
                                data-color-key="<?php echo esc_attr( $key ); ?>"
                            >
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="pcm-preview-column">
            <h2>Vista previa</h2>
            <div class="pcm-preview" style="<?php echo esc_attr( pcm_color_style( $settings['colors'] ) ); ?>">
                <aside class="pcm-preview-sidebar">
                    <h3><?php echo esc_html( $settings['map_title'] ? $settings['map_title'] : 'Encuentra tu ' . pcm_label( 'singular', true ) ); ?></h3>
                    <div class="pcm-preview-search">Buscar...</div>
                    <div class="pcm-preview-count">3 resultados</div>
                </aside>
                <div class="pcm-preview-map">
                    <span class="pcm-preview-marker"></span>
                    <div class="pcm-preview-popup">
                        <strong><?php echo esc_html( pcm_label() ); ?> de ejemplo</strong>
                        <p>Calle Mayor, 1</p>
                        <a>Web</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function pcm_render_import_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = pcm_get_settings();
    ?>
    <section class="pcm-import">
        <h2>Importar <?php echo esc_html( pcm_label( 'plural', true ) ); ?> desde CSV</h2>
        <p>Cabeceras admitidas: <code>nombre,direccion,lat,lng,web,campo_1,campo_2,campo_3</code>.</p>
        <p><code>nombre</code> es obligatorio. Si faltan latitud o longitud y hay dirección, se intentarán obtener con OpenStreetMap.</p>
        <p>
            <?php foreach ( $settings['extra_fields'] as $index => $field ) : ?>
                <?php if ( '1' === $field['enabled'] && $field['label'] ) : ?>
                    <code>campo_<?php echo esc_html( $index + 1 ); ?></code> = <?php echo esc_html( $field['label'] ); ?>&nbsp;
                <?php endif; ?>
            <?php endforeach; ?>
        </p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'pcm_import_csv', 'pcm_import_nonce' ); ?>
            <input type="file" name="pcm_csv" accept=".csv,text/csv" required>
            <?php submit_button( 'Importar CSV', 'primary', 'pcm_import_submit', false ); ?>
        </form>
        <?php
        if ( isset( $_POST['pcm_import_submit'] ) ) {
            pcm_handle_csv_import();
        }
        ?>
    </section>
    <?php
}

function pcm_handle_csv_import() {
    if ( ! isset( $_POST['pcm_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pcm_import_nonce'] ) ), 'pcm_import_csv' ) ) {
        echo '<div class="notice notice-error"><p>Error de seguridad.</p></div>';
        return;
    }
    if ( ! isset( $_FILES['pcm_csv'] ) || UPLOAD_ERR_OK !== (int) $_FILES['pcm_csv']['error'] ) {
        echo '<div class="notice notice-error"><p>No se pudo subir el archivo CSV.</p></div>';
        return;
    }

    $handle = fopen( $_FILES['pcm_csv']['tmp_name'], 'r' );
    if ( ! $handle ) {
        echo '<div class="notice notice-error"><p>No se pudo leer el archivo CSV.</p></div>';
        return;
    }

    $header = fgetcsv( $handle, 4096, ',' );
    if ( ! $header ) {
        fclose( $handle );
        echo '<div class="notice notice-error"><p>El CSV está vacío.</p></div>';
        return;
    }
    $header = array_map( static function ( $value ) {
        $value = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $value );
        return sanitize_key( trim( $value ) );
    }, $header );
    $columns = array_flip( $header );
    if ( ! isset( $columns['nombre'] ) ) {
        fclose( $handle );
        echo '<div class="notice notice-error"><p>Falta la columna obligatoria <code>nombre</code>.</p></div>';
        return;
    }

    $created = 0;
    $updated = 0;
    $geocoded = 0;
    $geocode_attempts = 0;
    $errors = array();
    $row_number = 1;
    $settings = pcm_get_settings();

    while ( false !== ( $row_values = fgetcsv( $handle, 4096, ',' ) ) ) {
        $row_number++;
        $row = array();
        foreach ( $columns as $key => $position ) {
            $row[ $key ] = isset( $row_values[ $position ] ) ? trim( (string) $row_values[ $position ] ) : '';
        }
        $name = isset( $row['nombre'] ) ? sanitize_text_field( $row['nombre'] ) : '';
        if ( '' === $name ) {
            $errors[] = 'Fila ' . $row_number . ': falta el nombre.';
            continue;
        }

        $existing = get_posts(
            array(
                'post_type'      => PCM_POST_TYPE,
                'post_status'    => 'any',
                'title'          => $name,
                'posts_per_page' => 1,
            )
        );
        if ( $existing ) {
            $post_id = $existing[0]->ID;
            $updated++;
        } else {
            $post_id = wp_insert_post(
                array(
                    'post_title'  => $name,
                    'post_type'   => PCM_POST_TYPE,
                    'post_status' => 'publish',
                ),
                true
            );
            if ( is_wp_error( $post_id ) ) {
                $errors[] = 'Fila ' . $row_number . ': ' . $post_id->get_error_message();
                continue;
            }
            $created++;
        }

        $address = isset( $row['direccion'] ) ? sanitize_text_field( $row['direccion'] ) : '';
        $lat = isset( $row['lat'] ) ? sanitize_text_field( $row['lat'] ) : '';
        $lng = isset( $row['lng'] ) ? sanitize_text_field( $row['lng'] ) : '';
        if ( $address && ( '' === $lat || '' === $lng ) && $geocode_attempts < 25 ) {
            $geocode_attempts++;
            $coordinates = pcm_geocode_address( $address );
            usleep( 1100000 );
            if ( $coordinates ) {
                $lat = $coordinates['lat'];
                $lng = $coordinates['lng'];
                $geocoded++;
            }
        }

        update_post_meta( $post_id, 'pcm_address', $address );
        update_post_meta( $post_id, 'pcm_lat', $lat );
        update_post_meta( $post_id, 'pcm_lng', $lng );
        update_post_meta( $post_id, 'pcm_web', isset( $row['web'] ) ? esc_url_raw( $row['web'] ) : '' );
        foreach ( $settings['extra_fields'] as $index => $field ) {
            $csv_key = 'campo_' . ( $index + 1 );
            if ( isset( $row[ $csv_key ] ) ) {
                update_post_meta( $post_id, 'pcm_extra_' . ( $index + 1 ), pcm_sanitize_extra_value( $row[ $csv_key ], $field['type'] ) );
            }
        }
    }
    fclose( $handle );

    printf(
        '<div class="notice notice-success"><p>Importación completada: %1$d creadas, %2$d actualizadas y %3$d geocodificadas.</p></div>',
        (int) $created,
        (int) $updated,
        (int) $geocoded
    );
    if ( $errors ) {
        echo '<div class="notice notice-warning"><p>' . esc_html( implode( ' ', $errors ) ) . '</p></div>';
    }
}

/**
 * Mapa público
 */
function pcm_color_style( $colors ) {
    $defaults = pcm_default_settings()['colors'];
    $colors = wp_parse_args( $colors, $defaults );
    $variables = array(
        '--pcm-sidebar-bg:' . sanitize_hex_color( $colors['sidebar_bg'] ),
        '--pcm-sidebar-text:' . sanitize_hex_color( $colors['sidebar_text'] ),
        '--pcm-body-text:' . sanitize_hex_color( $colors['body_text'] ),
        '--pcm-accent:' . sanitize_hex_color( $colors['accent'] ),
        '--pcm-accent-text:' . sanitize_hex_color( $colors['accent_text'] ),
        '--pcm-marker:' . sanitize_hex_color( $colors['marker'] ),
        '--pcm-popup-title:' . sanitize_hex_color( $colors['popup_title'] ),
        '--pcm-button-bg:' . sanitize_hex_color( $colors['button_bg'] ),
        '--pcm-button-text:' . sanitize_hex_color( $colors['button_text'] ),
        '--pcm-map-bg:' . sanitize_hex_color( $colors['map_bg'] ),
    );
    return implode( ';', $variables ) . ';';
}

function pcm_get_locations_data() {
    $settings = pcm_get_settings();
    $locations = array();
    $posts = get_posts(
        array(
            'post_type'      => PCM_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
    foreach ( $posts as $post ) {
        $extras = array();
        foreach ( $settings['extra_fields'] as $index => $field ) {
            if ( '1' !== $field['enabled'] || '' === trim( $field['label'] ) ) {
                continue;
            }
            $value = get_post_meta( $post->ID, 'pcm_extra_' . ( $index + 1 ), true );
            if ( '' === trim( (string) $value ) ) {
                continue;
            }
            $extras[] = array(
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => $value,
            );
        }
        $locations[] = array(
            'name'    => get_the_title( $post ),
            'address' => get_post_meta( $post->ID, 'pcm_address', true ),
            'lat'     => get_post_meta( $post->ID, 'pcm_lat', true ),
            'lng'     => get_post_meta( $post->ID, 'pcm_lng', true ),
            'web'     => get_post_meta( $post->ID, 'pcm_web', true ),
            'extras'  => $extras,
        );
    }
    return $locations;
}

function pcm_enqueue_public_assets() {
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
    wp_enqueue_style(
        'pcm-map',
        plugin_dir_url( __FILE__ ) . 'assets/mapa.css',
        array( 'leaflet-css' ),
        PCM_VERSION
    );
    wp_enqueue_script(
        'pcm-map',
        plugin_dir_url( __FILE__ ) . 'assets/mapa.js',
        array( 'leaflet-js' ),
        PCM_VERSION,
        true
    );
}

function pcm_maybe_enqueue_public_assets() {
    if ( ! is_singular() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || ! isset( $post->post_content ) ) {
        return;
    }
    $legacy_shortcode = 'bs' . 'map';
    if ( has_shortcode( $post->post_content, 'mapa' ) || has_shortcode( $post->post_content, $legacy_shortcode ) ) {
        pcm_enqueue_public_assets();
    }
}
add_action( 'wp_enqueue_scripts', 'pcm_maybe_enqueue_public_assets' );

function pcm_print_late_styles() {
    if ( wp_style_is( 'pcm-map', 'enqueued' ) && ! wp_style_is( 'pcm-map', 'done' ) ) {
        wp_print_styles( array( 'leaflet-css', 'pcm-map' ) );
    }
}
add_action( 'wp_footer', 'pcm_print_late_styles', 1 );

function pcm_map_shortcode() {
    pcm_enqueue_public_assets();
    $settings = pcm_get_settings();
    $locations = pcm_get_locations_data();
    $instance = wp_unique_id( 'pcm-map-' );
    $title = $settings['map_title'] ? $settings['map_title'] : 'Encuentra tu ' . pcm_label( 'singular', true );
    $placeholder = $settings['search_placeholder']
        ? $settings['search_placeholder']
        : 'Buscar ' . pcm_label( 'singular', true ) . ' o dirección…';
    $payload = array(
        'locations' => $locations,
        'marker'    => $settings['colors']['marker'],
        'emptyText' => 'No hay resultados',
        'webLabel'  => 'Web',
    );

    ob_start();
    ?>
    <div
        id="<?php echo esc_attr( $instance ); ?>"
        class="pcm-map"
        style="<?php echo esc_attr( pcm_color_style( $settings['colors'] ) ); ?>"
        data-map="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
    >
        <aside class="pcm-map__sidebar">
            <h2 class="pcm-map__title"><?php echo esc_html( $title ); ?></h2>
            <form class="pcm-map__search">
                <label class="screen-reader-text" for="<?php echo esc_attr( $instance ); ?>-search"><?php echo esc_html( $placeholder ); ?></label>
                <input id="<?php echo esc_attr( $instance ); ?>-search" type="search" placeholder="<?php echo esc_attr( $placeholder ); ?>">
                <button type="submit">Buscar</button>
            </form>
            <p class="pcm-map__count" aria-live="polite"></p>
        </aside>
        <div class="pcm-map__canvas" aria-label="<?php echo esc_attr( 'Mapa de ' . pcm_label( 'plural', true ) ); ?>"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mapa', 'pcm_map_shortcode' );

// Mantiene páginas antiguas funcionando durante la migración al shortcode [mapa].
add_shortcode( 'bs' . 'map', 'pcm_map_shortcode' );
