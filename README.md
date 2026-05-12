# 🍽️ Cocina Fantasy - Sistema de Gestión de Eventos y Menús

¡Bienvenido al sistema de gestión de **Cocina Fantasy**! Este proyecto es una aplicación web basada en PHP y MySQL diseñada para administrar salones de eventos, la planificación de menús, el cálculo de ingredientes y la generación de reportes de compras para eventos especiales.

## 🚀 Características Principales

El sistema cuenta con un panel de control interactivo que permite acceder a las siguientes funcionalidades:

*   **🏢 Gestión de Salones:** Administra los espacios disponibles para los eventos.
*   **📅 Organización de Eventos:** Planifica eventos especiales asignando salones y fechas.
*   **🍽️ Catálogo de Platillos:** Administra los platillos disponibles agrupados por categorías.
*   **📋 Categorías:** Organiza los platillos para una mejor navegación.
*   **🥘 Control de Ingredientes:** Gestiona el inventario o catálogo de ingredientes necesarios.
*   **📖 Gestión de Recetas:** Vincula platillos con sus respectivos ingredientes y cantidades.
*   **📊 Reportes:** Genera listas de compras totales basadas en el menú del evento.

## 🛠️ Requisitos e Instalación

Para ejecutar este proyecto localmente (por ejemplo, con XAMPP), sigue estos pasos:

1.  **Copiar el proyecto:** Clona o copia esta carpeta completa dentro del directorio de tu servidor local (ej. `C:\xampp\htdocs\SalonFantasy\`).
2.  **Configurar la Base de Datos:**
    *   Abre tu gestor de base de datos (como phpMyAdmin).
    *   Crea una base de datos llamada `cocina_fantasy` (puedes cambiar el nombre si lo prefieres).
    *   Importa el archivo SQL principal (`cocina_fantasy.sql`) incluido en la raíz del proyecto. Este archivo contiene la estructura de las tablas y las vistas necesarias.
    *   Asegúrate también de aplicar los scripts adicionales si es necesario (`migrar_categorias_multiples.sql`, `update_fmt_cant_human.sql`).
3.  **Asegurar las Vistas:** El sistema depende de ciertas vistas en la base de datos:
    *   `vw_evento_salon_header`
    *   `vw_evento_salon_platillo_ingrediente`
    *   `vw_evento_compra_total`
4.  **Configurar la Conexión:** Abre el archivo `db.php` y ajusta las constantes según tu configuración:
    *   `DB_HOST`: Usualmente `127.0.0.1` o `localhost`.
    *   `DB_NAME`: El nombre de la base de datos que creaste.
    *   `DB_USER`: Por defecto en XAMPP es `root`.
    *   `DB_PASS`: Por defecto en XAMPP está vacío (`''`).
5.  **Ejecutar:** Abre tu navegador y accede a: `http://localhost/SalonFantasy/index.php`

## 🔄 Flujo de Trabajo Típico

1.  Crea un evento en la sección de **Eventos**.
2.  Asigna salones al evento y define los menús (sección **Salones & Menú**).
3.  Agrega los platillos requeridos y el número de porciones para cada salón.
4.  Accede a la sección de **Reporte** para visualizar e imprimir los totales de compra calculados automáticamente según las recetas de los platillos.

---
Desarrollado para el proyecto de estadía por **Angel Luna**.
