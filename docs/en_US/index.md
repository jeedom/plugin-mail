# Mail

Plugin for sending E-mails.

Plugin configuration 
===

After installing the Plugin, you just need to activate it. This Plugin
has no special configuration :

![mail1](../images/mail1.PNG)

Equipment configuration 
===

The configuration of Mail equipment is accessible from the menu
Plugin :

![mail2](../images/mail2.PNG)

This is what the Mail Plugin page looks like (here with already 1 mail,
you can put as many as you want with the "Add" button) :

![mail3](../images/mail3.PNG)

Once you click on one of them, you get :

![mail4](../images/mail4.PNG)

Here you find all the configuration of your equipment :

-   **Email device name** : name of your equipment mail

-   **Parent object** : indicates the parent object to which belongs
    equipment

-   **Activer** : makes your equipment active

-   **Visible** : makes your equipment visible on the dashboard

-   **Sender name** : name of the sender of the E-mail (ex : Jeedom)

-   **Sender E-mail** : sender's E-mail (ex : <jeedom@moi.fr>)

-   **Send mode** : method of sending the E-mail :

    -   SMTP : most common mode for sending mail

    -   Sendmail

    -   Qmail

    -   Mail () \ [PHP Function \] : use the [standard send function
        from PHP,
        window="\_blank"](http://fr.php.net/manual/fr/function.mail.php),
        requires configuring the operating system

Apart from the SMTP option, the other options require the configuration of
the OS (Linux) to be able to work. In other words, basically only the
SMTP function works, the others are reserved for experts who
can, if they wish, configure these options themselves.

The SMTP configuration tab is used to enter the information for the
E-mail server you want to use.

![mail screenshot3](../images/mail_screenshot3.jpg)

Here are some examples for major service providers
E-mail :

-   **Gmail**

    -   SMTP server : smtp.gmail.com

    -   SMTP port : 587

    -   SMTP security : TLS

-   **Hotmail**

    -   SMTP server : smtp.live.com

    -   SMTP port : 587

    -   SMTP security : TLS

-   **iCloud**

    -   SMTP server : smtp.me.com

    -   SMTP port : 25

-   **Yahoo.com**

    -   SMTP server : smtp.mail.yahoo.com

    -   SMTP port : 465

    -   SMTP security : SSL

Les champs « Utilisateur SMTP » et « Mot de passe SMTP » correspondent
to the identifiers of your E-mail account.

From the "Orders" tab, you can add commands that
correspond to the E-mail addresses to which you wish to be able
send E-mails with Jeedom :

![mail screenshot4](../images/mail_screenshot4.jpg)

-   **nom** : Name of the order

-   **email** : the E-mail address to send the message to. You can put several by separating them with ,

-   **Advanced configuration** (small notched wheels) : allows
    display the advanced configuration of the command (method
    history, widget, etc.)

-   **tester** : Used to test the command,

-   **supprimer** (sign -) : allows to delete the command.

This Plugin works as a module, i.e. once
saved, it appears in the list of actions or commands. It is
so very simple to use when creating scenarios by
exemple.

In a scenario, once selected in an action, you will have to enter
title and message.

![mail5](../images/mail5.jpg)

> **Important**
>
> With Gmail you have to give a specific password for
> the application : My account ⇒ connection and security ⇒ Connect to
> Google ⇒ Application passwords

> **Tip**
>
> HTML format is supported by the scenario editor for the body
> messages.

> **Tip**
>
> Remember to save all changes.
