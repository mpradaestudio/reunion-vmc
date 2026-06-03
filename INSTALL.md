# Guía de Instalación - Programador de Reuniones

## 📋 Requisitos Previos

- **XAMPP** (Apache + MySQL + PHP 7.4 o superior)
- **Composer** (Gestor de dependencias de PHP)
- Navegador web moderno (Chrome, Firefox, Edge)
- Conexión a internet (para extracción de programas)

---

## 🚀 Instalación Paso a Paso

### 1. Instalar XAMPP

1. Descarga XAMPP desde: https://www.apachefriends.org
2. Instala XAMPP en tu computadora (por ejemplo: `C:\xampp\`)
3. Inicia el **Panel de Control de XAMPP**
4. Activa los servicios **Apache** y **MySQL** (clic en "Start")

### 2. Copiar los Archivos del Proyecto

1. Copia toda la carpeta del proyecto a:
   ```
   C:\xampp\htdocs\reunion-programador\
   ```

2. La estructura debe quedar así:
   ```
   C:\xampp\htdocs\reunion-programador\
   ├── api/
   ├── assets/
   ├── config/
   ├── includes/
   ├── pages/
   ├── vendor/
   ├── index.php
   ├── database.sql
   ├── composer.json
   └── README.md
   ```

### 3. Crear la Base de Datos

#### Opción A: Usando phpMyAdmin (Recomendado)

1. Abre tu navegador y ve a: http://localhost/phpmyadmin
2. Haz clic en "**Nueva**" en el panel izquierdo
3. Nombre de la base de datos: `reunion_programador`
4. Cotejamiento: `utf8mb4_spanish_ci`
5. Clic en "**Crear**"
6. Selecciona la base de datos creada
7. Ve a la pestaña "**Importar**"
8. Clic en "**Seleccionar archivo**"
9. Busca y selecciona: `C:\xampp\htdocs\reunion-programador\database.sql`
10. Clic en "**Continuar**" al final de la página
11. ✅ Verás el mensaje: "Importación finalizada correctamente"

#### Opción B: Usando línea de comandos

```bash
cd C:\xampp\htdocs\reunion-programador
mysql -u root -p < database.sql
```

### 4. Instalar Dependencias con Composer

#### 4.1 Instalar Composer (si no lo tienes)

1. Descarga desde: https://getcomposer.org/download/
2. Ejecuta el instalador
3. Sigue las instrucciones (dejar opciones por defecto)

#### 4.2 Instalar las librerías del proyecto

Abre la terminal (CMD o PowerShell) y ejecuta:

```bash
cd C:\xampp\htdocs\reunion-programador
composer install
```

Esto instalará:
- **TCPDF** (para generar PDFs)

Verás una salida similar a:
```
Loading composer repositories with package information
Installing dependencies from lock file
Package operations: 1 install, 0 updates, 0 removals
  - Installing tecnickcom/tcpdf (6.6.x)
Writing lock file
Generating autoload files
```

### 5. Verificar la Instalación

1. Abre tu navegador
2. Ve a: http://localhost/reunion-programador/
3. Deberías ver la pantalla de inicio con:
   - Barra de navegación azul
   - Panel de control con estadísticas
   - Opciones de menú

---

## ⚙️ Configuración Inicial

### 1. Configurar el Nombre de la Congregación

1. Ve a: **Configuración** (en el menú superior)
2. Ingresa el nombre de tu congregación
3. Clic en "**Guardar Configuración**"

### 2. Agregar Personas

1. Ve a: **Personas** → **Agregar Persona**
2. Completa el formulario:
   - Nombre y apellido
   - Selecciona el perfil (Anciano, Siervo Ministerial, Discursante, Ayudante)
   - Marca las partes que puede presentar
3. Clic en "**Guardar**"

### 3. Extraer Programas desde jw.org

1. Ve a: **Programas** → **Extraer Programas**
2. Selecciona el período (ej: Julio-Agosto 2026)
3. Clic en "**Extraer**"
4. Espera a que el sistema descargue los programas (puede tardar unos segundos)
5. ✅ Verás el mensaje: "Se extrajeron X programas correctamente"

### 4. Asignar Personas a las Partes

1. Ve a: **Programas**
2. Selecciona una semana → **Ver / Asignar**
3. Asigna roles generales:
   - Presidente
   - Oración inicial
   - Oración final
4. Asigna personas a cada parte del programa
5. Las asignaciones se guardan automáticamente

### 5. Exportar a PDF

1. Ve a: **Inicio** → **Exportar PDF**
2. Selecciona el mes que deseas exportar
3. Clic en "**Exportar PDF**"
4. El PDF se abrirá en una nueva pestaña
5. Puedes imprimirlo o guardarlo

---

## 🔧 Solución de Problemas

### Error: "No se puede conectar a la base de datos"

**Causa:** MySQL no está corriendo o las credenciales son incorrectas

**Solución:**
1. Verifica que MySQL esté activo en XAMPP
2. Abre: `config/database.php`
3. Verifica las credenciales:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Vacío por defecto en XAMPP
   define('DB_NAME', 'reunion_programador');
   ```

### Error: "TCPDF no está instalado"

**Causa:** No se ejecutó `composer install`

**Solución:**
```bash
cd C:\xampp\htdocs\reunion-programador
composer install
```

### Error: "No se pueden extraer programas"

**Causa:** Problemas de conexión a internet o jw.org no disponible

**Solución:**
1. Verifica tu conexión a internet
2. Intenta acceder manualmente a: https://www.jw.org/es/
3. Espera unos minutos y vuelve a intentar

### El PDF no se genera correctamente

**Causa:** Faltan las librerías de TCPDF

**Solución:**
1. Verifica que la carpeta `vendor/` exista
2. Ejecuta nuevamente: `composer install`
3. Verifica que Apache tenga permisos de escritura

### No aparecen las personas en el select

**Causa:** No hay personas activas registradas

**Solución:**
1. Ve a **Personas** → **Agregar Persona**
2. Asegúrate de marcar "Activo"
3. Marca las partes que puede presentar

---

## 📱 Uso en Red Local

Si quieres acceder desde otros dispositivos en tu red local:

### 1. Obtener tu IP local

**Windows:**
```bash
ipconfig
```
Busca: `Dirección IPv4: 192.168.X.X`

**Mac/Linux:**
```bash
ifconfig
```

### 2. Configurar Apache

1. Abre: `C:\xampp\apache\conf\extra\httpd-xampp.conf`
2. Busca la sección que dice:
   ```apache
   <Directory "C:/xampp/htdocs">
   ```
3. Cambia:
   ```apache
   Require local
   ```
   Por:
   ```apache
   Require all granted
   ```
4. Guarda y reinicia Apache

### 3. Acceder desde otros dispositivos

Desde cualquier dispositivo en la misma red, ve a:
```
http://192.168.X.X/reunion-programador/
```
(Reemplaza `192.168.X.X` con tu IP local)

---

## 🔐 Seguridad (Opcional)

### Agregar contraseña a MySQL

Por defecto, MySQL en XAMPP no tiene contraseña. Para agregar una:

1. Abre phpMyAdmin: http://localhost/phpmyadmin
2. Ve a "Cuentas de usuario"
3. Edita el usuario "root"
4. Establece una contraseña
5. Actualiza `config/database.php`:
   ```php
   define('DB_PASS', 'tu_contraseña_aqui');
   ```

---

## 📚 Recursos Adicionales

- **Manual de Usuario:** Ver `README.md`
- **Estructura de Base de Datos:** Ver `database.sql`
- **Documentación de TCPDF:** https://tcpdf.org/
- **Documentación de Bootstrap 5:** https://getbootstrap.com/docs/5.3/

---

## ✅ Lista de Verificación Final

Antes de comenzar a usar el sistema, verifica:

- [ ] XAMPP instalado y servicios corriendo
- [ ] Base de datos `reunion_programador` creada
- [ ] Archivo `database.sql` importado correctamente
- [ ] Composer instalado
- [ ] Dependencias instaladas (`composer install`)
- [ ] Sistema accesible en http://localhost/reunion-programador/
- [ ] Nombre de la congregación configurado
- [ ] Al menos una persona agregada
- [ ] Programas extraídos desde jw.org

---

## 💡 Consejos

1. **Haz backups regulares** de la base de datos desde phpMyAdmin
2. **Extrae programas** al inicio de cada bimestre
3. **Mantén actualizada** la lista de personas
4. **Exporta el PDF** con anticipación para revisión
5. **Guarda los PDFs generados** como respaldo

---

## 🆘 Soporte

Si necesitas ayuda adicional:

1. Revisa el archivo `README.md`
2. Verifica los logs de Apache en: `C:\xampp\apache\logs\error.log`
3. Verifica los logs de PHP en: `C:\xampp\php\logs\php_error_log`

---

**¡Listo!** Tu sistema de programación de reuniones está instalado y funcionando. 🎉
