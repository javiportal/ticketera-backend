Sistema de Gestión de Tareas 
Descripción del Proyecto 

El Sistema de Gestión de Tareas  es una aplicación que permite a los usuarios crear, asignar y rastrear tareas de manera efectiva. Está diseñado para facilitar la colaboracion en equipo y asegurar el cumplimiento de las tareas dentro del tiempo asignado.  

Caracteristicas principales: 

    Crear y gestionar tareas.
    Asignar tareas a usuarios especificos.
    Agregar comentarios para actualizar el progreso de una tarea.
    Cambiar el estado de las tareas (por ejemplo: "pendiente", "en progreso", "completada").
    Supervisar la productividad mediante la gestión de tareas y su estado.
     
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Estructura del proyecto 

El proyecto está organizado en los siguientes paquetes: 
 
src/
├── modelo/
│   ├── Tarea.java         - Clase que representa una tarea con atributos como titulo, descripción y estado.
│   ├── Usuario.java       - Clase que representa a un usuario con atributos como nombre, correo y rol.
│   ├── Comentario.java    - Clase que representa un comentario asociado a una tarea.
│   └── Proyecto.java      - Clase que agrupa varias tareas relacionadas.
├── servicio/
│   ├── GestorTareas.java  - Clase que gestiona todas las operaciones relacionadas con las tareas.
│   └── GestorUsuarios.java - Clase que gestiona todas las operaciones relacionadas con los usuarios.
└── principal/
    └── Main.java          - Clase principal que contiene la interfaz de consola para interactuar con el sistema.
 
 
Instrucciones para compilar y ejecutar 
Requisitos: 

    Java Development Kit (JDK) instalado.
    Un editor de texto o IDE (como IntelliJ IDEA, Eclipse o VS Code).
     
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Pasos de instalacion: 

    Compilar el proyecto :
    Opcion 1: Una vez se tenga descargado el achivo, crear un folder llamado "Modelo" SI NO SE CREA SOLO AL DESCOMPRIMIR, y descomprimir el zip dentro de el, luego abre su IDE seleciona nuevo proyecto y se va a la carpeta donde lo         descomprimio y seleciona "open"
	
    Opcion 2(no siempre funciona) : Abre una terminal en la raíz del proyecto y ejecuta el siguiente comando para compilar todas las clases: 
    bash
    
	1
	javac src/modelo/*.java src/servicio/*.java src/utilidades/*.java src/principal/Main.java

Ejecutar el proyecto :
Una vez compilado, ejecuta el programa con el siguiente comando: 
bash
 
    java -cp src principal.Main
     
     
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    Interactuar con el sistema :
    Al ejecutar el programa, veras un menu interactivo con opciones para: 
        Crear tareas.
        Ver tareas asignadas.
        Agregar comentarios a una tarea.
        Cambiar el estado de una tarea.
        Registrar nuevos usuarios.
         
     
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Funcionalidades Implementadas 

    Gestión de Tareas : 
        Crear nuevas tareas con titulo, descripcion y fecha de vencimiento.
        Asignar tareas a usuarios especificos.
        Cambiar el estado de las tareas (pendiente, en progreso, completada).
         

    Comentarios : 
        Agregar comentarios a una tarea para proporcionar actualizaciones o retroalimentacion.
         

    Gestion de usuarios : 
        Registrar nuevos usuarios con roles especificos (administrador, usuario, supervisor).
        Asignar tareas a usuarios desde el rol de supervisor.
         

    Validaciones : 
        Validar correos electronicos y fechas de vencimiento para garantizar datos correctos.
         
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Consideraciones especiales 

    Roles de Usuario : 
        Administrador : Gestiona el acceso de todos los usuarios.
        Usuario : Crea y gestiona sus propias tareas.
        Supervisor : Asigna tareas y monitorea el progreso de los usuarios.
         

    Proyectos (Opcional) : 
        La clase Proyecto permite agrupar tareas relacionadas. Esta funcionalidad puede ser expandida en futuras versiones.
         
     
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  
Este sistema es altamente escalable y puede mejorarse en el futuro agregando características como: 

    Persistencia de datos (guardar tareas y usuarios en archivos o bases de datos).
    Interfaz gráfica para mejorar la experiencia del usuario.
    Notificaciones automáticas para recordar tareas próximas a vencer.
     

Autores 

    Javier Andrés Chávez Portal  - 20245512
    Alí Efraín Chevez Merino  - 20245542
    Sebastián Alberto Dimas Rodríguez  - 20245246
    Erick Daniel Pineda Baires  - 20245510
     