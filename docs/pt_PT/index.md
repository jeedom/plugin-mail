# Mail

Plug-in para enviar e-mails.

Configuração do plugin 
===

Depois de instalar o plugin, você só precisa ativá-lo. Este plugin
não tem configuração especial :

![mail1](../images/mail1.PNG)

Configuração do equipamento 
===

A configuração do equipamento Mail é acessível no menu
plugin :

![mail2](../images/mail2.PNG)

É assim que a página do plugin Mail (aqui, com 1 E-mail já,
você pode colocar quantas quiser com o botão "Adicionar") :

![mail3](../images/mail3.PNG)

Depois de clicar em um deles, você obtém :

![mail4](../images/mail4.PNG)

Aqui você encontra toda a configuração do seu equipamento :

-   **Nome do equipamento de correio** : nome do correio do seu equipamento

-   **Objeto pai** : indica o objeto pai ao qual pertence
    o equipamento

-   **Activer** : torna seu equipamento ativo

-   **Visible** : torna seu equipamento visível no painel

-   **Nome do remetente** : nome do remetente do e-mail (ex : Jeedom)

-   **Remetente do correio** : E-mail do remetente (ex : <jeedom@moi.fr>)

-   **Método de envio** : método de envio do E-mail :

    -   SMTP : modo mais comum de envio de correio

    -   Sendmail

    -   Qmail

    -   Mail () \ [Função PHP \] : use a [função de envio padrão
        do PHP,
        window="\_blank"](http://fr.php.net/manual/fr/function.mail.php),
        requer a configuração do sistema operacional

Além da opção SMTP, as outras opções exigem a configuração de
o SO (Linux) para poder trabalhar. Em outras palavras, basicamente apenas o
A função SMTP funciona, os outros são reservados para especialistas que
podem, se desejarem, configurar essas opções eles mesmos.

A guia de configuração SMTP é usada para inserir as informações para o
servidor de E-mail que você deseja usar.

![mail screenshot3](../images/mail_screenshot3.jpg)

Aqui estão alguns exemplos para os principais provedores de serviços
E-mail :

-   **Gmail**

    -   Servidor SMTP : smtp.gmail.com

    -   Porta SMTP : 587

    -   Segurança SMTP : TLS

-   **Hotmail**

    -   Servidor SMTP : smtp.live.com

    -   Porta SMTP : 587

    -   Segurança SMTP : TLS

-   **iCloud**

    -   Servidor SMTP : smtp.me.com

    -   Porta SMTP : 25

-   **Yahoo.com**

    -   Servidor SMTP : smtp.mail.yahoo.com

    -   Porta SMTP : 465

    -   Segurança SMTP : SSL

Les champs « Utilisateur SMTP » et « Mot de passe SMTP » correspondent
para os identificadores da sua conta de E-mail.

Na guia "Pedidos", você pode adicionar comandos que
correspondem aos endereços de e-mail aos quais você deseja poder
envie e-mails com Jeedom :

![mail screenshot4](../images/mail_screenshot4.jpg)

-   **nom** : nome do comando

-   **email** : o endereço de E-mail para enviar a mensagem para. Você pode colocar vários separando-os com ,

-   **Configuração avançada** (pequenas rodas dentadas) : permet
    exibir a configuração avançada do comando (método
    histórico, widget etc.)

-   **tester** : permite testar o comando,

-   **supprimer** (sinal -) : permite excluir o comando.

Este plug-in funciona como um módulo, ou seja, uma vez
salvo, ele aparece na lista de ações ou comandos. Ele é
muito simples de usar ao criar cenários por
exemple.

Em um cenário, uma vez selecionado em uma ação, você precisará inserir
título e mensagem.

![mail5](../images/mail5.jpg)

> **Important**
>
> No Gmail, você precisa fornecer uma senha específica para
> a aplicação : Minha conta ⇒ conexão e segurança ⇒ Conectar-se a
> Google ⇒ senhas de aplicativos

> **Tip**
>
> O formato HTML é suportado pelo editor de cenário para o corpo
> mensagens.

> **Tip**
>
> Lembre-se de salvar todas as alterações.
