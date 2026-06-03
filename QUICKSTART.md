# ⚡ Inicio Rápido - 5 Minutos

Guía express para poner en marcha el sistema en menos de 5 minutos.

---

## 📋 Antes de Empezar

Necesitas tener instalado:
- ✅ XAMPP (con Apache y MySQL)
- ✅ Composer

---

## 🚀 Pasos de Instalación

### 1️⃣ Copiar archivos (30 segundos)

```bash
# Copia toda la carpeta a:
C:\xampp\htdocs\reunion-programador\
```

### 2️⃣ Iniciar servicios (10 segundos)

1. Abre **Panel de Control de XAMPP**
2. Clic en **Start** en Apache
3. Clic en **Start** en MySQL

### 3️⃣ Crear base de datos (1 minuto)

1. Abre: http://localhost/phpmyadmin
2. Clic en **"Nueva"**
3. Nombre: `reunion_programador`
4. Clic en **"Crear"**
5. Clic en **"Importar"**
6. Selecciona: `database.sql`
7. Clic en **"Continuar"**

### 4️⃣ Instalar dependencias (1 minuto)

```bash
cd C:\xampp\htdocs\reunion-programador
composer install
```

### 5️⃣ Verificar instalación (30 segundos)

Abre: http://localhost/reunion-programador/test_conexion.php

✅ Si todo está en verde, ¡listo!

---

## 🎯 Primeros Pasos

### A. Configurar nombre de congregación

1. Ve a: http://localhost/reunion-programador/
2. Clic en **"Configuración"**
3. Cambia el nombre
4. Guarda

### B. Agregar personas (opcional: usa datos demo)

**Opción 1: Datos de demostración**
```sql
-- En phpMyAdmin, importa:
database_demo_data.sql
```
Esto agregará 15 personas de ejemplo.

**Opción 2: Agregar manualmente**
1. Clic en **"Personas"** → **"Agregar Persona"**
2. Llena el formulario
3. Marca las partes que puede presentar
4. Guarda

### C. Extraer programas

1. Clic en **"Programas"** → **"Extraer Programas"**
2. Selecciona: **"Julio-Agosto 2026"**
3. Clic en **"Extraer"**
4. Espera 10-20 segundos
5. ✅ Verás los programas extraídos

### D. Asignar personas

1. En **"Programas"**, selecciona una semana
2. Clic en **"Ver / Asignar"**
3. Asigna:
   - Presidente
   - Oración inicial y final
   - Personas a cada parte
4. Las asignaciones se guardan automáticamente

### E. Exportar PDF

1. Clic en **"Inicio"** → **"Exportar PDF"**
2. Selecciona el mes
3. Clic en **"Exportar PDF"**
4. El PDF se abre en nueva pestaña
5. Imprimir o guardar

---

## 🎬 Video Tutorial (Conceptual)

Si existiera un video, seguiría estos pasos:

```
0:00 - Introducción
0:30 - Instalación rápida con XAMPP
1:30 - Importar base de datos
2:00 - Instalar dependencias con Composer
2:30 - Configuración inicial
3:00 - Agregar personas
3:30 - Extraer programas desde jw.org
4:00 - Asignar partes
4:30 - Exportar PDF
5:00 - Conclusión
```

---

## 🔗 URLs Importantes

Una vez instalado:

- **Sistema:** http://localhost/reunion-programador/
- **Verificación:** http://localhost/reunion-programador/test_conexion.php
- **phpMyAdmin:** http://localhost/phpmyadmin
- **Documentación:** Ver archivo `README.md`
- **Instalación detallada:** Ver archivo `INSTALL.md`

---

## ❓ Problemas Comunes

### ❌ "No se puede conectar a la base de datos"
**Solución:** Verifica que MySQL esté corriendo en XAMPP

### ❌ "TCPDF no está instalado"
**Solución:** Ejecuta `composer install` en la carpeta del proyecto

### ❌ "No se extraen los programas"
**Solución:** Verifica tu conexión a internet

### ❌ "Página en blanco"
**Solución:** 
1. Abre: `C:\xampp\apache\logs\error.log`
2. Busca el error al final del archivo
3. Verifica que `database.sql` esté importado

---

## ✅ Checklist de Instalación

```
[ ] XAMPP instalado
[ ] Apache corriendo
[ ] MySQL corriendo
[ ] Archivos copiados a htdocs
[ ] Base de datos creada
[ ] database.sql importado
[ ] composer install ejecutado
[ ] test_conexion.php muestra todo OK
[ ] Sistema accesible
```

---

## 📊 Siguiente Nivel

Una vez que domines lo básico:

1. **Agrega más personas** con diferentes perfiles
2. **Extrae programas** de múltiples períodos
3. **Personaliza el PDF** modificando `pages/exportar_pdf.php`
4. **Exporta backups** de la base de datos regularmente
5. **Comparte en red local** siguiendo instrucciones en `INSTALL.md`

---

## 🎯 Objetivo de los 5 Minutos

Al final de estos 5 minutos deberías poder:

✅ Ver el dashboard con estadísticas  
✅ Tener al menos una persona registrada  
✅ Tener al menos un programa extraído  
✅ Hacer una asignación de prueba  
✅ Exportar tu primer PDF  

---

## 💡 Consejo Pro

**Usa los datos de demostración** para probar el sistema antes de agregar tus datos reales:

```sql
-- En phpMyAdmin:
1. Importa database.sql (estructura)
2. Importa database_demo_data.sql (15 personas de ejemplo)
3. Extrae programas desde el sistema
4. ¡Listo para probar!
```

---

## 🆘 Necesitas Ayuda?

1. **Verificación:** Ejecuta `test_conexion.php`
2. **Documentación completa:** Lee `INSTALL.md`
3. **Características:** Lee `FEATURES.md`
4. **Logs de error:** Revisa `C:\xampp\apache\logs\error.log`

---

**¡Listo! Ya puedes empezar a programar las reuniones de tu congregación.** 🎉

```
┌─────────────────────────────────────┐
│  Sistema de Programación v1.0       │
│  ✓ Instalación completa             │
│  ✓ Base de datos configurada        │
│  ✓ Dependencias instaladas          │
│  ✓ Sistema funcionando               │
│                                      │
│  → http://localhost/reunion-programador/
└─────────────────────────────────────┘
```
