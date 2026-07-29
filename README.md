# Premiero Mapa Configurable

Plugin de código abierto para crear mapas interactivos de ubicaciones en WordPress. El tipo de ubicación, los campos del popup, los textos y los colores se configuran desde el panel de administración.

## Funciones

- Nombre singular y plural configurable: oficinas, centros, playas, locales o cualquier otro tipo de ubicación.
- Dirección, latitud, longitud y web para cada ubicación.
- Geocodificación automática mediante OpenStreetMap cuando faltan coordenadas.
- Hasta tres campos adicionales configurables.
- Buscador de ubicaciones.
- Colores y marcador configurables con vista previa.
- Importación desde CSV.
- Shortcode `[mapa]`.
- Actualizaciones estables desde GitHub Releases.

## Requisitos

- WordPress 5.8 o posterior.
- PHP 7.4 o posterior.

## Instalación

1. Descarga `premiero-mapa-configurable.zip` desde la última Release.
2. En WordPress, abre `Plugins > Añadir plugin > Subir plugin`.
3. Instala y activa **Premiero Mapa Configurable**.
4. Abre `Mapa de ubicaciones > Ajustes`.
5. Configura el tipo de ubicación y añade `[mapa]` a cualquier página.

Las versiones posteriores aparecerán como actualizaciones normales de WordPress.

## Importación CSV

El importador admite las siguientes cabeceras:

```text
nombre,direccion,lat,lng,web,campo_1,campo_2,campo_3
```

`nombre` es obligatorio. Cuando una fila incluye una dirección pero no coordenadas, el plugin intenta geocodificarla respetando los límites del servicio de OpenStreetMap.

## Publicación de versiones

1. Actualiza `Version` y `PCM_VERSION` en `premiero-mapa-configurable.php`.
2. Actualiza `Stable tag` y el registro de cambios de `readme.txt`.
3. Confirma y sube los cambios a la rama principal.
4. Crea una etiqueta y una Release con el mismo número, por ejemplo `v1.0.1`.
5. Espera a que el workflow **Build WordPress plugin release** genere y adjunte `premiero-mapa-configurable.zip`.

El actualizador requiere ese ZIP adjunto. Los archivos automáticos “Source code” de GitHub no se utilizan porque no garantizan la carpeta que WordPress necesita.

El título, la etiqueta y el texto preparados para la publicación actual están en [`RELEASE_NOTES.md`](RELEASE_NOTES.md).

## Configuración del repositorio

El proyecto está preparado para:

<https://github.com/andres-nmg/premiero-mapa-configurable/>

Si cambia la URL, actualiza los encabezados `Plugin URI` y `Update URI`, además de `PCM_REPOSITORY_URL` y `PCM_RELEASE_API`.

## Licencia

Premiero Mapa Configurable se distribuye bajo **GPL-3.0-or-later**. Puedes usarlo, estudiarlo, modificarlo y redistribuirlo respetando la licencia y los avisos de autoría.

## Servicios y componentes externos

- [Leaflet](https://leafletjs.com/) se carga desde un CDN y se distribuye bajo licencia BSD-2-Clause.
- Los mapas utilizan teselas y atribución de [OpenStreetMap](https://www.openstreetmap.org/copyright).
- La geocodificación utiliza el servicio público [Nominatim](https://nominatim.org/).
- El comprobador de actualizaciones consulta la API pública de GitHub.

Al mostrar el mapa, el navegador del visitante solicita recursos a OpenStreetMap y al CDN de Leaflet. Al geocodificar una ubicación, la dirección introducida se envía a Nominatim. Revisa las políticas de esos servicios antes de utilizar el plugin en producción.

## Soporte

- Web: <https://premiero.es>
- Correo: <hola@premiero.es>
- WhatsApp: <https://wa.me/34684774365>
