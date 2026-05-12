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

## 🧠 Estructura del Código y Base de Datos

### 🔌 Conexión a la Base de Datos
El proyecto utiliza principalmente el archivo `db.php` para gestionar la conexión a la base de datos MySQL. 
*   Se utiliza la extensión **PDO (PHP Data Objects)** para una conexión más segura y flexible.
*   En algunos scripts específicos (como `calcular.php`), se utiliza la extensión `mysqli` de forma directa.

### 📁 Archivos PHP Utilizados
A continuación se describen los archivos PHP que componen el sistema y su función:

*   **`index.php`**: El panel de control (Dashboard) principal del sistema. Muestra accesos directos a todas las secciones con un diseño moderno.
*   **`db.php`**: Archivo de configuración que establece la conexión PDO con la base de datos.
*   **`header.php` / `footer.php`**: Componentes reutilizables que contienen la estructura HTML común de la cabecera y el pie de página.
*   **`salon_list.php`**: Permite listar y gestionar los salones disponibles.
*   **`evento_list.php`**: Muestra la lista de eventos planificados.
*   **`evento_edit.php`**: Formulario para crear o editar los detalles de un evento.
*   **`evento_menu.php`**: Permite asignar el menú (platillos y porciones) a los salones de un evento.
*   **`evento_salones.php`**: Gestiona los salones asignados a un evento específico.
*   **`platillos.php`**: Módulo para la gestión del catálogo de platillos.
*   **`categoria_list.php`**: Gestión de las categorías para clasificar los platillos.
*   **`ingredientes.php`**: Módulo para administrar el catálogo de ingredientes.
*   **`recetas.php`**: Permite definir las recetas, asociando ingredientes y cantidades a cada platillo.
*   **`receta_api.php`**: Endpoint que devuelve en formato JSON los ingredientes de un platillo específico.
*   **`calcular.php`**: Herramienta interactiva para calcular las cantidades de ingredientes necesarias para un número determinado de porciones de un platillo.
*   **`reporte_evento.php`**: Genera el reporte final del evento con los totales de compra necesarios.
*   **`pedido_cliente.php`**: Gestión o visualización de pedidos de clientes.
*   **`pedido_compra.php` / `pedido_compras.php`**: Gestión de órdenes y pedidos de compra de insumos.

### ⚙️ ¿Cómo funciona el código?
El sistema opera bajo un esquema de PHP tradicional (Server-Side Rendering) donde cada página procesa las peticiones del usuario, consulta la base de datos y renderiza el HTML correspondiente.
1.  **Inclusión de componentes:** La mayoría de los archivos incluyen `db.php` para la conexión y `header.php`/`footer.php` para mantener un diseño consistente.
2.  **Cálculos automatizados:** Mediante consultas SQL avanzadas y vistas (`vw_...`), el sistema multiplica las porciones requeridas en los eventos por las cantidades base definidas en las recetas, generando listas de compras exactas.

---
Desarrollado para el proyecto de estadía por **Angel Luna**.
