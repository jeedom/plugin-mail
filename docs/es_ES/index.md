# Mail

Complemento para enviar correos electrónicos.

Configuración del Plugin 
===

Después de instalar el complemento, solo necesita activarlo. Este complemento
no tiene una configuración especial :

![mail1](../images/mail1.PNG)

Configuración del equipo 
===

Se puede acceder a la configuración del equipo de correo desde el menú
Plugin :

![mail2](../images/mail2.PNG)

Así es como se ve la página del complemento de correo (aquí con 1 correo ya,
puedes poner tantos como quieras con el botón "Agregar") :

![mail3](../images/mail3.PNG)

Una vez que haces clic en uno de ellos, obtienes :

![mail4](../images/mail4.PNG)

Aquí encontrarás toda la configuración de tu equipo :

-   **Nombre del equipo de correo** : nombre del correo de su equipo

-   **Objeto padre** : indica el objeto padre al que pertenece
    equipo

-   **Activer** : activa su equipo

-   **Visible** : hace que su equipo sea visible en el tablero

-   **Nombre del remitente** : nombre del remitente del correo electrónico (ej. : Jeedom)

-   **Remitente de correo** : correo electrónico del remitente (ex : <jeedom@moi.fr>)

-   **Método de envío** : método de enviar el correo electrónico :

    -   SMTP : modo más común para enviar correo

    -   Sendmail

    -   Qmail

    -   Correo () \ [Función PHP \] : use la [función de envío estándar
        de PHP,
        window="\_blank"](http://fr.php.net/manual/fr/function.mail.php),
        requiere configurar el sistema operativo

Además de la opción SMTP, las otras opciones requieren la configuración de
el sistema operativo (Linux) para poder trabajar. En otras palabras, básicamente solo el
La función SMTP funciona, los demás están reservados para expertos que
pueden, si lo desean, configurar estas opciones ellos mismos.

La pestaña de configuración SMTP se utiliza para ingresar la información para
servidor de correo electrónico que desea usar.

![mail screenshot3](../images/mail_screenshot3.jpg)

Aquí hay algunos ejemplos para los principales proveedores de servicios.
E-mail :

-   **Gmail**

    -   Servidor SMTP : smtp.gmail.com

    -   Puerto SMTP : 587

    -   Seguridad SMTP : TLS

-   **Hotmail**

    -   Servidor SMTP : smtp.live.com

    -   Puerto SMTP : 587

    -   Seguridad SMTP : TLS

-   **iCloud**

    -   Servidor SMTP : smtp.me.com

    -   Puerto SMTP : 25

-   **Yahoo.com**

    -   Servidor SMTP : smtp.mail.yahoo.com

    -   Puerto SMTP : 465

    -   Seguridad SMTP : SSL

Les champs « Utilisateur SMTP » et « Mot de passe SMTP » correspondent
a los identificadores de su cuenta de correo electrónico.

Desde la pestaña "Pedidos", puede agregar comandos que
corresponde a las direcciones de correo electrónico a las que desea poder
enviar correos electrónicos con Jeedom :

![mail screenshot4](../images/mail_screenshot4.jpg)

-   **nom** : Nombre de la orden

-   **email** : la dirección de correo electrónico para enviar el mensaje. Puedes poner varios separándolos con ,

-   **Configuración avanzada** (ruedas con muescas pequeñas) : permet
    muestra la configuración avanzada del comando (método
    historia, widget, etc.)

-   **tester** : Se usa para probar el comando,

-   **supprimer** (signo -) : permite eliminar el comando.

Este complemento funciona como un módulo, es decir, una vez
guardado, aparece en la lista de acciones o comandos. El es
muy simple de usar al crear escenarios por
exemple.

En un escenario, una vez seleccionado en una acción, deberá ingresar
título y mensaje.

![mail5](../images/mail5.jpg)

> **Important**
>
> Con Gmail tienes que dar una contraseña específica para
> la aplicación : Mi cuenta ⇒ conexión y seguridad ⇒ Conéctese a
> Google ⇒ Contraseñas de aplicación

> **Tip**
>
> El editor de escenarios del cuerpo admite el formato HTML.
> mensajes.

> **Tip**
>
> Recuerde guardar todos los cambios.
