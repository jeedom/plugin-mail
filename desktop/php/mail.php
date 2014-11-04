<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'mail');
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter Emails}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach (eqLogic::byType('mail') as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName() . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class='row'>
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Général}}</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nom de l'équipement mail}}</label>
                            <div class="col-lg-6">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement mail}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label" >{{Objet parent}}</label>
                            <div class="col-lg-8">
                                <select class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Activer}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-lg-4 control-label">{{Visible}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nom expéditeur}}</label>
                            <div class="col-lg-6">
                                <input class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='fromName' />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Mail expéditeur}}</label>
                            <div class="col-lg-6">
                                <input class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='fromMail' />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Mode d'envoi}}</label>
                            <div class="col-lg-6">
                                <select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='sendMode'>
                                    <option value='smtp'>SMTP</option>
                                    <option value='sendmail' class="expertModeVisible">Sendmail</option>
                                    <option value='qmail' class="expertModeVisible">Qmail</option>
                                    <option value='mail' class="expertModeVisible">Mail() [PHP fonction]</option>
                                </select>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <div class='sendMode sendmail' style="display: none;">
                            <div class="alert alert-danger">Attention cette option nécessite d'avoir correctement configurer le système (OS).</div>
                        </div>
                        <div class='sendMode mail' style="display: none;">
                            <div class="alert alert-danger">Attention cette option nécessite d'avoir correctement configurer le système (OS).</div>
                        </div>
                        <div class='sendMode qmail' style="display: none;">
                            <div class="alert alert-danger">Attention cette option nécessite d'avoir correctement configurer le système (OS).</div>
                        </div>
                        <div class='sendMode smtp' style="display: none;">
                            <legend>{{Configuration SMTP}}</legend>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">{{Serveur SMTP}}</label>
                                <div class="col-lg-6">
                                    <input class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::server' />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">{{Port SMTP}}</label>
                                <div class="col-lg-6">
                                    <input class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::port' />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">{{Securité SMTP}}</label>
                                <div class="col-lg-6">
                                    <select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::security'>
                                        <option value=''>{{Aucune}}</option>
                                        <option value='tls'>TLS</option>
                                        <option value='ssl'>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">{{Uitlisateur SMTP}}</label>
                                <div class="col-lg-6">
                                    <input class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::username' />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">{{Mot de passe SMTP}}</label>
                                <div class="col-lg-6">
                                    <input type="password" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::password' />
                                </div>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
        </div>

        <legend>{{Email}}</legend>
        <a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une commande mail}}</a><br/><br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Email}}</th><th></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php
include_file('desktop', 'mail', 'js', 'mail');
include_file('core', 'plugin.template', 'js');
?>