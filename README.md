# 📅 Programador de Reuniones - Congregación

Sistema web completo para programar y gestionar las asignaciones de las reuniones **Vida y Ministerio Cristianos**, con extracción automática de información desde jw.org.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple)
![License](https://img.shields.io/badge/license-Free-green)

---

## ✨ Características Principales

### 🤖 Extracción Automática
- ✅ **Web Scraping** desde jw.org de las Guías de Actividades
- ✅ Extrae títulos, duraciones, referencias bíblicas y canciones
- ✅ Organiza automáticamente por secciones (TESOROS, SEAMOS MEJORES MAESTROS, NUESTRA VIDA)
- ✅ Soporta múltiples períodos bimestrales

### 👥 Gestión de Personas
- ✅ Perfiles configurables (Anciano, Siervo Ministerial, Discursante, Ayudante)
- ✅ Control de partes que cada persona puede presentar
- ✅ Estado activo/inactivo
- ✅ Información de contacto (teléfono, email)
- ✅ Notas personalizadas

### 📋 Asignación de Roles
- ✅ **Roles generales:** Presidente, Oración inicial, Oración final
- ✅ **Roles específicos:** Conductor/Lector para estudios bíblicos
- ✅ **Asignaciones dobles:** Estudiante/Ayudante para "SEAMOS MEJORES MAESTROS"
- ✅ Asignación flexible sin límites de repetición
- ✅ Guardado automático al seleccionar

### 📄 Exportación a PDF
- ✅ Formato profesional idéntico al modelo oficial
- ✅ Tipografía Google Sans
- ✅ Colores oficiales por sección
- ✅ Exportación mensual completa
- ✅ Incluye nombre de la congregación personalizado

### 🎨 Interfaz Moderna
- ✅ Diseño responsive (funciona en móviles y tablets)
- ✅ Bootstrap 5 con tema personalizado
- ✅ Dashboard con estadísticas en tiempo real
- ✅ Navegación intuitiva y fácil de usar
- ✅ Notificaciones visuales

## 📋 Requisitos

- XAMPP (Apache + MySQL + PHP 7.4+)
- Navegador web moderno
- Conexión a internet (para extracción de programas)

## 🛠️ Instalación

### 1. Instalar XAMPP
Descarga e instala XAMPP desde [https://www.apachefriends.org](https://www.apachefriends.org)

### 2. Configurar el proyecto

1. Copia todos los archivos del proyecto a la carpeta `htdocs` de XAMPP:
   ```
   C:\xampp\htdocs\reunion-programador\
   ```

2. Inicia Apache y MySQL desde el panel de control de XAMPP

### 3. Crear la base de datos

1. Abre phpMyAdmin en tu navegador: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

2. Importa el archivo `database.sql`:
   - Clic en "Nueva" para crear la base de datos
   - Ve a la pestaña "Importar"
   - Selecciona el archivo `database.sql`
   - Clic en "Continuar"

### 4. Configurar la conexión (opcional)

Si necesitas cambiar las credenciales de la base de datos, edita el archivo:
```
config/database.php
```

### 5. Instalar dependencias para PDF y Scraping

**Para generación de PDF (TCPDF):**
```bash
cd C:\xampp\htdocs\reunion-programador
composer require tecnickcom/tcpdf
```

**Para web scraping (Simple HTML DOM):**
```bash
composer require sunra/php-simple-html-dom-parser
```

Si no tienes Composer instalado, descárgalo desde [https://getcomposer.org](https://getcomposer.org)

## 🎯 Uso

### 1. Acceder al sistema
Abre tu navegador y visita:
```
http://localhost/reunion-programador/
```

### 2. Configurar la congregación
1. Ve a **Configuración**
2. Ingresa el nombre de tu congregación
3. Guarda los cambios

### 3. Agregar personas
1. Ve a **Personas**
2. Clic en "Agregar Persona"
3. Completa el formulario con nombre, apellido, perfil, etc.
4. Selecciona qué partes puede presentar

### 4. Extraer programas de jw.org
1. Ve a **Programas**
2. Clic en "Extraer Programas"
3. Selecciona el período (ej: Julio-Agosto 2026)
4. El sistema extraerá automáticamente todos los programas semanales

### 5. Asignar personas a las partes
1. Ve a **Programas** y selecciona una semana
2. Asigna personas a cada rol:
   - Presidente
   - Oración (apertura y cierre)
   - Conductor/Lector
   - Partes de cada sección
3. Para "SEAMOS MEJORES MAESTROS" asigna 2 personas (Estudiante/Ayudante)

### 6. Exportar a PDF
1. Ve a **Exportar PDF**
2. Selecciona el mes
3. Descarga el PDF con formato profesional

## 📁 Estructura del proyecto

```
reunion-programador/
├── assets/
│   ├── css/
│   │   └── style.css          # Estilos personalizados
│   └── js/
│       └── main.js            # JavaScript principal
├── config/
│   ├── config.php             # Configuración general
│   └── database.php           # Conexión a base de datos
├── includes/
│   ├── header.php             # Header HTML
│   └── footer.php             # Footer HTML
├── pages/
│   ├── personas.php           # Gestión de personas
│   ├── programas.php          # Lista de programas
│   ├── programa_detalle.php   # Detalle y asignaciones
│   ├── configuracion.php      # Configuración del sistema
│   └── exportar_pdf.php       # Exportación a PDF
├── api/
│   ├── personas.php           # API REST para personas
│   ├── programas.php          # API REST para programas
│   ├── asignaciones.php       # API REST para asignaciones
│   └── scraper.php            # Web scraping de jw.org
├── vendor/                    # Dependencias de Composer
├── database.sql               # Script de creación de BD
├── index.php                  # Página principal
├── .htaccess                  # Configuración Apache
└── README.md                  # Este archivo
```

## 🎨 Secciones del Programa

El sistema reconoce tres secciones principales con colores específicos:

- **TESOROS DE LA BIBLIA** (Gris: #6c757d)
- **SEAMOS MEJORES MAESTROS** (Dorado: #d4a01e) - Asignación de 2 personas (Estudiante/Ayudante)
- **NUESTRA VIDA CRISTIANA** (Vino: #8b1538)

---

## 📸 Capturas de Pantalla

### Panel de Control
El dashboard muestra estadísticas en tiempo real: personas activas, programas disponibles, asignaciones pendientes.

### Gestión de Personas
Interfaz intuitiva para agregar y administrar personas con sus perfiles y capacidades.

### Asignación de Partes
Vista detallada del programa semanal con selectores para asignar personas a cada parte.

### PDF Generado
Exportación profesional con formato idéntico al modelo oficial de la congregación.

---

## 🎯 Casos de Uso

### Coordinador de Reunión
- Extrae programas al inicio de cada bimestre
- Asigna personas semana a semana
- Exporta PDF mensual para distribuir a la congregación
- Mantiene actualizada la lista de participantes

### Secretario
- Gestiona la base de datos de personas
- Configura perfiles y capacidades
- Genera reportes históricos
- Mantiene registro de asignaciones

### Publicador
- Consulta sus asignaciones próximas
- Revisa el programa semanal completo
- Descarga el PDF del mes

## 🔧 Tecnologías utilizadas

- **Backend:** PHP 7.4+
- **Base de datos:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Framework CSS:** Bootstrap 5
- **Librería JS:** jQuery 3.7
- **Tipografía:** Google Sans
- **PDF:** TCPDF
- **Scraping:** Simple HTML DOM Parser
- **Servidor:** Apache (XAMPP)

## 📝 Notas importantes

- El scraping extrae información únicamente de las "Guías de Actividades" oficiales de jw.org
- Los tiempos de duración de cada parte se extraen automáticamente
- No hay límite de asignaciones por persona
- Las secciones de "SEAMOS MEJORES MAESTROS" siempre requieren 2 personas (opcional asignarlas)
- El PDF exporta todo el mes completo con el formato exacto proporcionado

## 🐛 Solución de problemas

### Error de conexión a la base de datos
**Síntoma:** Mensaje "Error de conexión" al abrir el sistema

**Solución:**
- Verifica que MySQL esté corriendo en el Panel de Control de XAMPP
- Abre `config/database.php` y verifica las credenciales
- Asegúrate de haber importado `database.sql` en phpMyAdmin

### No se extraen los programas
**Síntoma:** Error al intentar extraer programas desde jw.org

**Solución:**
- Verifica tu conexión a internet
- Prueba acceder manualmente a https://www.jw.org/es/
- Espera unos minutos y vuelve a intentar (puede ser carga del servidor)
- Verifica que PHP tenga habilitada la extensión cURL

### El PDF no se genera
**Síntoma:** Error al exportar a PDF o página en blanco

**Solución:**
- Ejecuta: `composer install` para instalar TCPDF
- Verifica que la carpeta `vendor/` exista y contenga archivos
- Comprueba que Apache tenga permisos de escritura
- Revisa el log de errores en: `C:\xampp\apache\logs\error.log`

### Las personas no aparecen en los selectores
**Síntoma:** Listas vacías al asignar partes

**Solución:**
- Verifica que las personas estén marcadas como "Activas"
- Asegúrate de haber seleccionado las partes que pueden presentar
- Ve a Personas → Editar → Marca las casillas de las partes disponibles

### Script de prueba
Ejecuta `test_conexion.php` para verificar la instalación:
```
http://localhost/reunion-programador/test_conexion.php
```
Este script te mostrará qué componentes están funcionando correctamente.

## 📄 Licencia

Este proyecto es de uso libre para congregaciones. No tiene fines comerciales.

## 🤝 Soporte

Para reportar problemas o solicitar mejoras, contacta al administrador del sistema.

---

**Versión:** 1.0.0  
**Última actualización:** 2026
