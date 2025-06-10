# JOB - Módulo CJ20N simplificado

Este proyecto contiene una versión ligera del módulo CJ20N para la gestión de proyectos y registro de actividades.

## Archivos principales

- `estructura_wbs.php` muestra la estructura WBS y permite crear nuevos nodos.
- `registrar_actividad.php` registra actividades sobre cada WBS.
## Instalación
1. Importe las tablas `PROJ`, `PRPS`, `PRHI` y `AFRU` siguiendo la estructura de SAP.
2. Sitúe los archivos en un servidor con PHP 7+ y acceso a MySQL. Los scripts contienen la configuración de conexión directamente en el código.

## Uso
- Abra `estructura_wbs.php` para gestionar la estructura y acceder al registro de actividades.
- Cada nodo permite agregar actividades a través de `registrar_actividad.php`.

Los formularios usan Tailwind CSS para un aspecto similar a SAP.
