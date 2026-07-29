=== Premiero Mapa Configurable ===
Contributors: andres-nmg
Tags: map, locations, leaflet, openstreetmap, shortcode
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Crea mapas interactivos de ubicaciones con campos, textos y colores configurables.

== Description ==

Premiero Mapa Configurable permite utilizar el mismo plugin para mapas de oficinas, centros, locales, playas o cualquier otro tipo de ubicación.

Cada ubicación incluye nombre, dirección, latitud, longitud y web. Además, se pueden activar hasta tres campos personalizados que solo aparecen en el popup cuando tienen contenido.

La dirección puede geocodificarse mediante OpenStreetMap y el mapa se añade a cualquier página con el shortcode `[mapa]`.

Las actualizaciones estables se distribuyen mediante GitHub Releases desde:

https://github.com/andres-nmg/premiero-mapa-configurable/

== External services ==

El mapa carga Leaflet desde un CDN y teselas de OpenStreetMap. El navegador del visitante puede comunicar su dirección IP a estos proveedores al solicitar dichos recursos.

Cuando se geocodifica una dirección, esta se envía al servicio público Nominatim de OpenStreetMap. El comprobador de actualizaciones consulta la API pública de GitHub.

Consulta las políticas y condiciones de Leaflet, OpenStreetMap, Nominatim y GitHub antes de utilizar el plugin en producción.

== Installation ==

1. Descarga `premiero-mapa-configurable.zip` desde la última Release.
2. Sube el ZIP desde `Plugins > Añadir plugin > Subir plugin`.
3. Activa Premiero Mapa Configurable.
4. Configura el mapa desde su pestaña Ajustes.
5. Añade `[mapa]` a una página.

== Frequently Asked Questions ==

= ¿Qué tipos de mapa puedo crear? =

Cualquiera basado en ubicaciones: oficinas, centros, locales, playas, instalaciones, delegaciones u otros.

= ¿Cómo se obtienen las coordenadas? =

Puedes introducirlas manualmente. Si están vacías y guardas una dirección, el plugin intenta obtenerlas mediante OpenStreetMap.

= ¿Cómo se reciben las actualizaciones? =

WordPress consulta la última Release estable del repositorio público. Cuando existe una versión superior y contiene el ZIP oficial, aparece como una actualización normal.

= ¿Puedo modificar y redistribuir el plugin? =

Sí. Se distribuye bajo GPLv3 o posterior.

== Changelog ==

= 1.0.0 =

* Primera versión pública como Premiero Mapa Configurable.
* Añadidas ubicaciones, geocodificación, campos configurables y personalización de colores.
* Añadidos importador CSV, shortcode y vista previa.
* Añadidas actualizaciones desde GitHub Releases.
