# Mail

Plugin zum Versenden von E-Mails.

Plugin Konfiguration 
===

Nach der Installation des Plugins müssen Sie es nur noch aktivieren. Dieses Plugin
hat keine spezielle Konfiguration :

![mail1](../images/mail1.PNG)

Gerätekonfiguration 
===

Auf die Konfiguration der Mail-Geräte kann über das Menü zugegriffen werden
Plugin :

![mail2](../images/mail2.PNG)

So sieht die Mail-Plugin-Seite aus (hier mit bereits 1 Mail,
Mit der Schaltfläche "Hinzufügen" können Sie beliebig viele eingeben :

![mail3](../images/mail3.PNG)

Sobald Sie auf eine davon klicken, erhalten Sie :

![mail4](../images/mail4.PNG)

Hier finden Sie die gesamte Konfiguration Ihrer Geräte :

-   **Name der Postausrüstung** : Name Ihrer Gerätepost

-   **Übergeordnetes Objekt** : gibt das übergeordnete Objekt an, zu dem es gehört
    Ausrüstung

-   **Aktivieren** : macht Ihre Ausrüstung aktiv

-   **Sichtbar** : macht Ihre Ausrüstung auf dem Armaturenbrett sichtbar

-   **Absendername** : Name des Absenders der E-Mail (z : Jeedom)

-   **Post expéditeur** : E-Mail des Absenders (z : <jeedom@moi.fr>)

-   **Versandart** : Methode zum Senden der E-Mail :

    -   SMTP : häufigster Modus zum Senden von E-Mails

    -   Sendmail

    -   Qmail

    -   Mail () \ [PHP-Funktion \] : Verwenden Sie die [Standard-Sendefunktion
        von PHP,
        window="\_blank"](http://fr.php.net/manual/fr/function.mail.php),
        erfordert die Konfiguration des Betriebssystems

Abgesehen von der SMTP-Option erfordern die anderen Optionen die Konfiguration von
das Betriebssystem (Linux), um arbeiten zu können. Mit anderen Worten, im Grunde nur die
SMTP-Funktion funktioniert, die anderen sind Experten vorbehalten, die
Sie können diese Optionen auf Wunsch selbst konfigurieren.

Auf der Registerkarte SMTP-Konfiguration werden die Informationen für die eingegeben
E-Mail-Server, den Sie verwenden möchten.

![mail screenshot3](../images/mail_screenshot3.jpg)

Hier einige Beispiele für große Dienstleister
E-Mail :

-   **Google Mail**

    -   SMTP-Server : smtp.gmail.com

    -   SMTP-Port : 587

    -   SMTP-Sicherheit : TLS

-   **Hotmail**

    -   SMTP-Server : smtp.live.com

    -   SMTP-Port : 587

    -   SMTP-Sicherheit : TLS

-   **iCloud**

    -   SMTP-Server : smtp.me.com

    -   SMTP-Port : 25

-   **Yahoo.com**

    -   SMTP-Server : smtp.mail.yahoo.com

    -   SMTP-Port : 465

    -   SMTP-Sicherheit : SSL

Les champs « Utilisateur SMTP » et « Mot de passe SMTP » correspondent
zu den Kennungen Ihres E-Mail-Kontos.

Auf der Registerkarte "Bestellungen" können Sie Befehle hinzufügen, die
entsprechen den E-Mail-Adressen, an die Sie in der Lage sein möchten
Sende E-Mails mit Jeedom :

![mail screenshot4](../images/mail_screenshot4.jpg)

-   **Name** : Name der Bestellung

-   **E-Mail** : die E-Mail-Adresse, an die die Nachricht gesendet werden soll. Sie können mehrere setzen, indem Sie sie mit trennen ,

-   **Erweiterte Konfiguration** (kleine gekerbte Räder) : permet
    Zeigen Sie die erweiterte Konfiguration des Befehls (Methode) an
    Geschichte, Widget usw.)

-   **Test** : Wird zum Testen des Befehls verwendet,

-   **Löschen** (Zeichen -) : ermöglicht das Löschen des Befehls.

Dieses Plugin arbeitet als Modul, d. H. Einmal
gespeichert, erscheint es in der Liste der Aktionen oder Befehle. Er ist
so sehr einfach zu verwenden beim erstellen von szenarien von
exemple.

In einem Szenario müssen Sie nach Auswahl in einer Aktion eingeben
Titel und Nachricht.

![mail5](../images/mail5.jpg)

> **Wichtig**
>
> Bei Google Mail müssen Sie ein bestimmtes Passwort für angeben
> die Anwendung : Mein Konto ⇒ Verbindung und Sicherheit ⇒ Verbinden mit
> Google ⇒ Anwendungskennwörter

> **Spitze**
>
> Das HTML-Format wird vom Szenario-Editor für den Body unterstützt
> Nachrichten.

> **Spitze**
>
> Denken Sie daran, alle Änderungen zu speichern.
