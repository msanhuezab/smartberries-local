# Manual de usuario - Configuracion

## Objetivo

El modulo Configuracion centraliza parametros generales de la aplicacion, usuarios, avisos y tareas programadas. Debe usarse con cuidado, porque sus datos afectan a otros modulos.

## Acceso

1. Ingrese al sistema.
2. Seleccione el modulo `Configuracion`.
3. Si el sistema solicita contexto, seleccione empresa, planta o temporada segun corresponda.

## Secciones principales

### Configuracion App

Administra datos base compartidos por la aplicacion.

- Principal: empresa, planta, temporada y folios.
- Fruta: productores, variedades, especies, cuarteles, etiquetas, embalajes y calibres.
- Estandares: reglas o parametros para granel, exportacion, comercial e industrial.
- Ubicacion: ciudad, comuna, provincia, region y pais.
- Transporte: linea aerea, naviera, transporte y conductores.
- Tipo: productores, procesos, reembalajes, contenedores, fletes, monedas, servicios, manejos, inspeccion SAG, tratamientos, categorias y colores.
- Otros: color de calidad, contraparte, inspector y comprador.

### Usuarios

Permite administrar accesos y permisos.

- Crear o editar usuarios.
- Revisar historial de usuario.
- Configurar tipos de usuario.
- Asignar privilegios por tipo de usuario.
- Asociar usuarios a empresas o productores.

### Avisos y tareas

Permite revisar o mantener procesos de notificacion y ejecucion automatica.

- Registro de avisos.
- Cron PT.
- Cron ejecutados.

## Flujo recomendado

1. Revise si el dato ya existe antes de crear uno nuevo.
2. Complete campos obligatorios.
3. Guarde el registro.
4. Vuelva al listado o mantenedor para confirmar que quedo disponible.
5. Si el dato impacta formularios de otros modulos, valide que aparezca en el selector correspondiente.

## Cuidados

- Evite duplicar empresas, plantas, productores, especies o folios.
- Cambios en permisos pueden afectar el acceso inmediato de otros usuarios.
- Los mantenedores compartidos deben modificarse solo cuando exista claridad operacional.
